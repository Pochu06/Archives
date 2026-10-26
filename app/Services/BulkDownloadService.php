<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\DownloadRequest;
use App\Models\Research;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use ZipArchive;

class BulkDownloadService
{
    private const MAX_FILES_KEY = 'bulk_download_max_files';

    private const MAX_SIZE_MB_KEY = 'bulk_download_max_size_mb';

    public function limits(): array
    {
        $settings = SystemSetting::query()
            ->whereIn('key', [self::MAX_FILES_KEY, self::MAX_SIZE_MB_KEY])
            ->pluck('value', 'key');

        return [
            'max_files' => $this->settingValue($settings->get(self::MAX_FILES_KEY), 20, 1, 100),
            'max_size_mb' => $this->settingValue($settings->get(self::MAX_SIZE_MB_KEY), 100, 1, 512),
        ];
    }

    public function saveLimits(int $maxFiles, int $maxSizeMb, int $updatedBy): void
    {
        foreach ([
            self::MAX_FILES_KEY => $maxFiles,
            self::MAX_SIZE_MB_KEY => $maxSizeMb,
        ] as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value, 'updated_by' => $updatedBy]
            );
        }
    }

    public function canDownload(Research $research, int $userId, string $role, ?int $collegeId): bool
    {
        if ($this->hasDirectDownloadAccess($research, $userId, $role, $collegeId)) {
            return true;
        }

        return DownloadRequest::query()
            ->where('user_id', $userId)
            ->where('research_id', $research->id)
            ->where('status', 'approved')
            ->exists();
    }

    public function filterDownloadable(Collection $researchPapers, int $userId, string $role, ?int $collegeId): Collection
    {
        $papersWithoutDirectAccess = $researchPapers->reject(
            fn (Research $research) => $this->hasDirectDownloadAccess($research, $userId, $role, $collegeId)
        );
        if ($papersWithoutDirectAccess->isEmpty()) {
            return $researchPapers->values();
        }

        $approvedResearchIds = DownloadRequest::query()
            ->where('user_id', $userId)
            ->whereIn('research_id', $papersWithoutDirectAccess->pluck('id'))
            ->where('status', 'approved')
            ->pluck('research_id')
            ->all();

        return $researchPapers->filter(
            fn (Research $research) => $this->hasDirectDownloadAccess($research, $userId, $role, $collegeId)
                || in_array($research->id, $approvedResearchIds)
        )->values();
    }

    public function createArchive(Collection $researchPapers, int $maxSizeMb, callable $renderPdf, int $excludedCount = 0): string
    {
        $archivePath = tempnam(storage_path('app'), 'research-bulk-');
        if ($archivePath === false) {
            throw new FileException('Unable to allocate a temporary archive file.');
        }

        $archive = new ZipArchive;
        if ($archive->open($archivePath, ZipArchive::OVERWRITE) !== true) {
            $this->removeTemporaryArchive($archivePath);
            throw new FileException('Unable to create the requested ZIP archive.');
        }

        $maxBytes = $maxSizeMb * 1024 * 1024;
        $totalBytes = 0;

        try {
            foreach ($researchPapers->unique('id') as $research) {
                $pdf = $renderPdf($research);
                $pdfSize = strlen($pdf);
                if ($totalBytes + $pdfSize > $maxBytes) {
                    throw new BulkDownloadLimitExceeded("The generated documents exceed the {$maxSizeMb} MB archive limit.");
                }

                $filename = Str::limit(Str::slug($research->title) ?: 'research-paper', 100, '')
                    .'-'.$research->id.'.pdf';
                if (! $archive->addFromString($filename, $pdf)) {
                    throw new FileException('Unable to add a research report to the ZIP archive.');
                }

                $totalBytes += $pdfSize;
            }

            if ($excludedCount > 0) {
                $notice = "Some selected records were not included because you do not have download permission.\n"
                    ."Documents omitted: {$excludedCount}\n";
                if ($totalBytes + strlen($notice) > $maxBytes) {
                    throw new BulkDownloadLimitExceeded("The generated documents exceed the {$maxSizeMb} MB archive limit.");
                }
                if (! $archive->addFromString('DOWNLOAD-INFO.txt', $notice)) {
                    throw new FileException('Unable to add the ZIP archive download notice.');
                }
            }

            if (! $archive->close()) {
                throw new FileException('Unable to finish the requested ZIP archive.');
            }
        } catch (\Throwable $exception) {
            $archive->close();
            $this->removeTemporaryArchive($archivePath);
            throw $exception;
        }

        return $archivePath;
    }

    public function recordActivity(
        Request $request,
        string $result,
        int $requestedCount,
        int $includedCount,
        int $excludedCount,
        ?string $reason = null
    ): void {
        AdminActionLog::query()->create([
            'user_id' => (int) session('user_id'),
            'role' => (string) session('user_role'),
            'action' => 'research.bulk_download',
            'method' => $request->method(),
            'route_name' => $request->route()?->getName(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_data' => [
                'result' => $result,
                'requested_count' => $requestedCount,
                'included_count' => $includedCount,
                'excluded_count' => $excludedCount,
                'reason' => $reason,
            ],
            'response_status' => match ($result) {
                'completed' => 200,
                'failed' => 500,
                default => 422,
            },
        ]);
    }

    private function removeTemporaryArchive(string $path): void
    {
        if (is_file($path) && ! unlink($path)) {
            Log::warning('Unable to remove a temporary bulk-download archive.', ['path' => $path]);
        }
    }

    private function hasDirectDownloadAccess(Research $research, int $userId, string $role, ?int $collegeId): bool
    {
        return (int) $research->user_id === $userId
            || ($role === 'admin'
                && $collegeId
                && (int) $research->college_id === $collegeId
                && $research->status === Research::STATUS_PENDING_COLLEGE)
            || $role === 'super_admin'
            || ($role === 'admin' && ! $collegeId);
    }

    private function settingValue(?string $value, int $default, int $minimum, int $maximum): int
    {
        if ($value === null) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        if ($parsed === false || $parsed < $minimum || $parsed > $maximum) {
            throw new RuntimeException('The configured bulk-download limit is invalid. Update it from the administrator dashboard.');
        }

        return $parsed;
    }
}

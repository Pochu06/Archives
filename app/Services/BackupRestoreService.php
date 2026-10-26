<?php

namespace App\Services;

use App\Models\AdminActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class BackupRestoreService
{
    private const FORMAT_VERSION = 1;

    private const MAX_ARCHIVE_ENTRIES = 50000;

    private const MAX_ARCHIVE_BYTES = 5368709120;

    private const EXCLUDED_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'sessions',
    ];

    public function listBackups(): array
    {
        $directory = $this->backupDirectory();
        if (! is_dir($directory)) {
            return [];
        }

        $files = glob($directory.DIRECTORY_SEPARATOR.'*.zip') ?: [];
        usort($files, fn (string $left, string $right) => filemtime($right) <=> filemtime($left));

        return array_map(fn (string $path) => [
            'name' => basename($path),
            'size' => filesize($path),
            'created_at' => date('Y-m-d H:i:s', filemtime($path)),
        ], $files);
    }

    public function createBackup(?int $actorId = null, string $role = 'system', ?Request $request = null): string
    {
        $this->assertZipSupport();
        $this->backupKey();

        $backupDirectory = $this->backupDirectory();
        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0700, true) && ! is_dir($backupDirectory)) {
            throw new BackupRestoreException('The private backup directory could not be created.');
        }

        $workDirectory = $backupDirectory.DIRECTORY_SEPARATOR.'.work-'.Str::uuid();
        if (! mkdir($workDirectory, 0700)) {
            throw new BackupRestoreException('A private backup work directory could not be created.');
        }

        $filename = 'archives-backup-'.now()->format('Ymd-His').'-'.Str::uuid().'.zip';
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;
        $zip = new ZipArchive;
        $opened = false;
        $payloadBytes = 0;

        try {
            if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new BackupRestoreException('The encrypted backup archive could not be created.');
            }
            $opened = true;
            $zip->setPassword($this->backupKey());

            $tables = $this->tableSchema();
            $databaseEntries = [];
            foreach ($tables as $table => $columns) {
                if (in_array($table, self::EXCLUDED_TABLES, true)) {
                    continue;
                }

                $archiveName = 'payload/'.Str::uuid().'.jsonl';
                $tablePath = $workDirectory.DIRECTORY_SEPARATOR.Str::uuid().'.jsonl';
                $this->writeTableRows($table, $tablePath);
                $this->assertBackupCapacity($zip, $payloadBytes, $tablePath);
                $this->addEncryptedFile($zip, $archiveName, $tablePath);
                $databaseEntries[] = [
                    'table' => $table,
                    'columns' => $columns,
                    'archive_name' => $archiveName,
                ];
            }

            $fileEntries = $this->addStoredFiles($zip, $payloadBytes);
            $manifest = [
                'format' => self::FORMAT_VERSION,
                'application' => 'ARCHIVES',
                'database_driver' => DB::connection()->getDriverName(),
                'created_at' => now()->toIso8601String(),
                'migrations' => $this->migrationNames(),
                'tables' => $databaseEntries,
                'files' => $fileEntries,
            ];
            $manifestJson = json_encode($manifest, JSON_THROW_ON_ERROR);
            if ($payloadBytes + strlen($manifestJson) > self::MAX_ARCHIVE_BYTES) {
                throw new BackupRestoreException('The backup exceeds the supported 5 GB restore limit.');
            }
            if (! $zip->addFromString('manifest.json', $manifestJson)) {
                throw new BackupRestoreException('The encrypted backup manifest could not be added.');
            }
            $this->encryptEntry($zip, 'manifest.json');

            if (! $zip->close()) {
                throw new BackupRestoreException('The backup archive could not be completed.');
            }
            $opened = false;

            $this->recordActivity(
                $actorId,
                $role,
                'backup.created',
                200,
                [
                    'backup' => $filename,
                    'size_bytes' => filesize($backupPath),
                    'database_tables' => count($databaseEntries),
                    'document_files' => count($fileEntries),
                ],
                $request
            );

            return $backupPath;
        } catch (Throwable $exception) {
            if ($opened) {
                $zip->close();
            }
            $this->removePath($backupPath);
            throw $exception;
        } finally {
            $this->removeDirectory($workDirectory);
        }
    }

    public function restoreUploadedBackup(
        string $uploadedPath,
        ?int $actorId,
        string $role,
        Request $request
    ): void {
        $this->assertZipSupport();
        $key = $this->backupKey();
        $this->validateConfirmationInput($request);

        $zip = new ZipArchive;
        if ($zip->open($uploadedPath) !== true) {
            throw new BackupRestoreException('The uploaded file is not a readable ARCHIVES backup.');
        }
        $zip->setPassword($key);
        $workDirectory = $this->backupDirectory().DIRECTORY_SEPARATOR.'.restore-'.Str::uuid();

        try {
            if (! mkdir($workDirectory, 0700, true)) {
                throw new BackupRestoreException('A private restore staging directory could not be created.');
            }

            $manifest = $this->validatedManifest($zip);
            $stagedTables = $this->stageAndValidateTables($zip, $manifest, $workDirectory);
            $stagedFiles = $this->stageFiles($zip, $manifest, $workDirectory);
            $zip->close();

            $this->createBackup($actorId, $role, $request);
            $fileRollback = $this->stageCurrentFiles($workDirectory);
            try {
                $this->replaceFiles($stagedFiles);
                $this->replaceDatabase($stagedTables);
            } catch (Throwable $restoreException) {
                try {
                    $this->replaceFiles($fileRollback);
                } catch (Throwable $rollbackException) {
                    Log::critical('ARCHIVES restore failed and stored files could not be rolled back.', [
                        'exception_class' => $restoreException::class,
                        'rollback_exception_class' => $rollbackException::class,
                    ]);
                    throw new BackupRestoreException(
                        'Restore failed and some stored files may need recovery from the retained safety backup.'
                    );
                }

                throw $restoreException;
            }

            $this->recordActivity(
                $actorId,
                $role,
                'restore.completed',
                200,
                ['tables_restored' => count($stagedTables), 'files_restored' => count($stagedFiles)],
                $request
            );
        } catch (Throwable $exception) {
            $zip->close();
            throw $exception;
        } finally {
            $this->removeDirectory($workDirectory);
        }
    }

    public function backupPath(string $name): string
    {
        abort_unless(preg_match('/\Aarchives-backup-\d{8}-\d{6}-[0-9a-f-]{36}\.zip\z/i', $name), 404);
        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$name;
        abort_unless(is_file($path), 404);

        return $path;
    }

    public function recordActivity(
        ?int $actorId,
        string $role,
        string $action,
        int $responseStatus,
        array $details = [],
        ?Request $request = null
    ): void {
        AdminActionLog::query()->create([
            'user_id' => $actorId,
            'role' => $role,
            'action' => $action,
            'method' => $request?->method() ?? 'SYSTEM',
            'route_name' => $request?->route()?->getName(),
            'path' => $request?->path() ?? 'scheduled-backup',
            'url' => $request?->fullUrl() ?? 'scheduled-backup',
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_data' => $details,
            'response_status' => $responseStatus,
        ]);
    }

    private function writeTableRows(string $table, string $path): void
    {
        $output = fopen($path, 'wb');
        if ($output === false) {
            throw new BackupRestoreException('A database backup staging file could not be created.');
        }

        try {
            foreach (DB::table($table)->cursor() as $row) {
                $values = [];
                foreach ((array) $row as $column => $value) {
                    $values[$column] = is_string($value)
                        ? ['__archives_base64' => base64_encode($value)]
                        : $value;
                }
                fwrite($output, json_encode($values, JSON_THROW_ON_ERROR)."\n");
            }
        } finally {
            fclose($output);
        }
    }

    private function addStoredFiles(ZipArchive $zip, int &$payloadBytes): array
    {
        $files = [];
        foreach (['local', 'public'] as $diskName) {
            foreach (Storage::disk($diskName)->allFiles() as $relativePath) {
                if ($diskName === 'local'
                    && (str_starts_with($relativePath, 'backups/') || $this->hasHiddenPathSegment($relativePath))) {
                    continue;
                }

                $archiveName = 'payload/'.Str::uuid().'.file';
                $absolutePath = Storage::disk($diskName)->path($relativePath);
                $this->assertBackupCapacity($zip, $payloadBytes, $absolutePath);
                $this->addEncryptedFile($zip, $archiveName, $absolutePath);
                $files[] = [
                    'disk' => $diskName,
                    'path' => $relativePath,
                    'archive_name' => $archiveName,
                ];
            }
        }

        return $files;
    }

    private function assertBackupCapacity(ZipArchive $zip, int &$payloadBytes, string $path): void
    {
        $size = filesize($path);
        if ($size === false || $zip->numFiles >= self::MAX_ARCHIVE_ENTRIES - 1
            || $payloadBytes + $size > self::MAX_ARCHIVE_BYTES) {
            throw new BackupRestoreException('The backup exceeds the supported file-count or 5 GB restore limit.');
        }
        $payloadBytes += $size;
    }

    private function addEncryptedFile(ZipArchive $zip, string $archiveName, string $sourcePath): void
    {
        if (! $zip->addFile($sourcePath, $archiveName)) {
            throw new BackupRestoreException('A database or document file could not be added to the backup.');
        }
        $this->encryptEntry($zip, $archiveName);
    }

    private function encryptEntry(ZipArchive $zip, string $archiveName): void
    {
        if (! $zip->setEncryptionName($archiveName, ZipArchive::EM_AES_256, $this->backupKey())) {
            throw new BackupRestoreException('AES-256 backup encryption is not available in this PHP ZIP installation.');
        }
    }

    private function validatedManifest(ZipArchive $zip): array
    {
        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
            throw new BackupRestoreException('The backup contains an unsupported number of archive entries.');
        }

        $totalBytes = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if (! is_array($stat) || ! isset($stat['name'], $stat['size'])) {
                throw new BackupRestoreException('The backup contains an invalid archive entry.');
            }
            $totalBytes += (int) $stat['size'];
            if ($totalBytes > self::MAX_ARCHIVE_BYTES) {
                throw new BackupRestoreException('The backup is larger than the supported restore limit.');
            }
        }

        $json = $zip->getFromName('manifest.json');
        if ($json === false || strlen($json) > 50 * 1024 * 1024) {
            throw new BackupRestoreException('The encrypted backup manifest is missing or invalid.');
        }
        try {
            $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new BackupRestoreException('The encrypted backup manifest is invalid.');
        }
        if (! is_array($manifest)) {
            throw new BackupRestoreException('The encrypted backup manifest is invalid.');
        }
        if (($manifest['format'] ?? null) !== self::FORMAT_VERSION
            || ($manifest['application'] ?? null) !== 'ARCHIVES'
            || ($manifest['database_driver'] ?? null) !== DB::connection()->getDriverName()) {
            throw new BackupRestoreException('The backup format or database driver is not compatible with this installation.');
        }

        $tables = $this->tableSchema();
        if (($manifest['migrations'] ?? null) !== $this->migrationNames()) {
            throw new BackupRestoreException('The backup migration history does not match this installation.');
        }

        if (! is_array($manifest['tables'] ?? null) || ! is_array($manifest['files'] ?? null)) {
            throw new BackupRestoreException('The backup manifest is incomplete.');
        }

        $seenTables = [];
        $seenEntries = ['manifest.json' => true];
        foreach ($manifest['tables'] as $entry) {
            if (! is_array($entry)) {
                throw new BackupRestoreException('The backup database contents do not match this installation.');
            }
            $table = $entry['table'] ?? null;
            $archiveName = $entry['archive_name'] ?? null;
            if (! is_string($table) || ! isset($tables[$table]) || in_array($table, self::EXCLUDED_TABLES, true)
                || ($entry['columns'] ?? null) !== $tables[$table]
                || ! $this->validPayloadEntryName($archiveName)
                || isset($seenTables[$table]) || isset($seenEntries[$archiveName])) {
                throw new BackupRestoreException('The backup database contents do not match this installation.');
            }
            $seenTables[$table] = true;
            $seenEntries[$archiveName] = true;
        }

        foreach ($tables as $table => $_columns) {
            if (! in_array($table, self::EXCLUDED_TABLES, true) && ! isset($seenTables[$table])) {
                throw new BackupRestoreException('The backup is missing a required database table.');
            }
        }

        $seenStoredPaths = [];
        foreach ($manifest['files'] as $entry) {
            if (! is_array($entry)) {
                throw new BackupRestoreException('The backup contains an invalid uploaded-file path.');
            }
            $disk = $entry['disk'] ?? null;
            $path = $entry['path'] ?? null;
            $archiveName = $entry['archive_name'] ?? null;
            if (! in_array($disk, ['local', 'public'], true)
                || ! $this->validStoredFilePath($disk, $path)
                || ! $this->validPayloadEntryName($archiveName)
                || isset($seenEntries[$archiveName])) {
                throw new BackupRestoreException('The backup contains an invalid uploaded-file path.');
            }
            $pathKey = $disk.':'.$path;
            if (isset($seenStoredPaths[$pathKey])) {
                throw new BackupRestoreException('The backup contains duplicate uploaded-file paths.');
            }
            $seenStoredPaths[$pathKey] = true;
            $seenEntries[$archiveName] = true;
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if (! isset($seenEntries[$stat['name']])
                || ($stat['encryption_method'] ?? null) !== ZipArchive::EM_AES_256) {
                throw new BackupRestoreException('Every backup entry must be referenced and encrypted with AES-256.');
            }
        }

        return $manifest;
    }

    private function stageAndValidateTables(ZipArchive $zip, array $manifest, string $workDirectory): array
    {
        $staged = [];
        foreach ($manifest['tables'] as $entry) {
            $stream = $zip->getStream($entry['archive_name']);
            if ($stream === false) {
                throw new BackupRestoreException('A database table could not be decrypted from the backup.');
            }
            $stagedPath = $workDirectory.DIRECTORY_SEPARATOR.Str::uuid().'.jsonl';
            $output = fopen($stagedPath, 'wb');
            if ($output === false) {
                fclose($stream);
                throw new BackupRestoreException('A database restore staging file could not be created.');
            }

            try {
                while (($line = fgets($stream)) !== false) {
                    $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                    if (! is_array($row) || array_diff(array_keys($row), $entry['columns'])
                        || array_diff($entry['columns'], array_keys($row))) {
                        throw new BackupRestoreException('A database table in the backup has an invalid row format.');
                    }
                    fwrite($output, $line);
                }
            } finally {
                fclose($stream);
                fclose($output);
            }
            $staged[] = ['table' => $entry['table'], 'columns' => $entry['columns'], 'path' => $stagedPath];
        }

        return $staged;
    }

    private function stageFiles(ZipArchive $zip, array $manifest, string $workDirectory): array
    {
        $staged = [];
        foreach ($manifest['files'] as $entry) {
            $stream = $zip->getStream($entry['archive_name']);
            if ($stream === false) {
                throw new BackupRestoreException('A stored research document could not be decrypted from the backup.');
            }
            $stagedPath = $workDirectory.DIRECTORY_SEPARATOR.Str::uuid().'.file';
            $output = fopen($stagedPath, 'wb');
            if ($output === false) {
                fclose($stream);
                throw new BackupRestoreException('A document restore staging file could not be created.');
            }
            try {
                stream_copy_to_stream($stream, $output);
            } finally {
                fclose($stream);
                fclose($output);
            }
            $staged[] = $entry + ['staged_path' => $stagedPath];
        }

        return $staged;
    }

    private function replaceDatabase(array $tables): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($tables) {
                foreach (array_reverse($tables) as $table) {
                    DB::table($table['table'])->delete();
                }

                foreach ($tables as $table) {
                    $input = fopen($table['path'], 'rb');
                    if ($input === false) {
                        throw new BackupRestoreException('A staged database table could not be read during restore.');
                    }

                    $batch = [];
                    $batchSize = max(1, min(100, intdiv(900, max(1, count($table['columns'])))));
                    try {
                        while (($line = fgets($input)) !== false) {
                            $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                            foreach ($row as $column => $value) {
                                if (is_array($value) && array_keys($value) === ['__archives_base64']) {
                                    $decoded = base64_decode($value['__archives_base64'], true);
                                    if ($decoded === false) {
                                        throw new BackupRestoreException('A database value in the backup is invalid.');
                                    }
                                    $row[$column] = $decoded;
                                }
                            }
                            $batch[] = $row;
                            if (count($batch) >= $batchSize) {
                                DB::table($table['table'])->insert($batch);
                                $batch = [];
                            }
                        }
                        if ($batch !== []) {
                            DB::table($table['table'])->insert($batch);
                        }
                    } finally {
                        fclose($input);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function replaceFiles(array $stagedFiles): void
    {
        $expectedPaths = ['local' => [], 'public' => []];
        foreach ($stagedFiles as $file) {
            $disk = Storage::disk($file['disk']);
            $disk->makeDirectory(dirname($file['path']));
            $input = fopen($file['staged_path'], 'rb');
            if ($input === false || ! $disk->put($file['path'], $input)) {
                if (is_resource($input)) {
                    fclose($input);
                }
                throw new BackupRestoreException('A backed-up document could not be restored.');
            }
            fclose($input);
            $expectedPaths[$file['disk']][$file['path']] = true;
        }

        foreach (['local', 'public'] as $diskName) {
            foreach (Storage::disk($diskName)->allFiles() as $path) {
                if ($diskName === 'local' && str_starts_with($path, 'backups/')) {
                    continue;
                }
                if (! isset($expectedPaths[$diskName][$path]) && $this->isBackedUpContent($diskName, $path)) {
                    if (! Storage::disk($diskName)->delete($path)) {
                        throw new BackupRestoreException('A current stored document could not be replaced safely.');
                    }
                }
            }
        }
    }

    private function stageCurrentFiles(string $workDirectory): array
    {
        $staged = [];
        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            foreach ($disk->allFiles() as $path) {
                if (! $this->isBackedUpContent($diskName, $path)) {
                    continue;
                }

                $input = $disk->readStream($path);
                $stagedPath = $workDirectory.DIRECTORY_SEPARATOR.Str::uuid().'.rollback';
                $output = fopen($stagedPath, 'wb');
                if ($input === false || $output === false) {
                    if (is_resource($input)) {
                        fclose($input);
                    }
                    if (is_resource($output)) {
                        fclose($output);
                    }
                    throw new BackupRestoreException('Current stored documents could not be staged safely for restore.');
                }

                try {
                    if (stream_copy_to_stream($input, $output) === false) {
                        throw new BackupRestoreException('Current stored documents could not be staged safely for restore.');
                    }
                } finally {
                    fclose($input);
                    fclose($output);
                }
                $staged[] = ['disk' => $diskName, 'path' => $path, 'staged_path' => $stagedPath];
            }
        }

        return $staged;
    }

    private function isBackedUpContent(string $disk, string $path): bool
    {
        return ($disk === 'local'
                && ! str_starts_with(strtolower($path), 'backups/')
                && ! $this->hasHiddenPathSegment($path))
            || ($disk === 'public' && str_starts_with($path, 'research_files/'));
    }

    private function tableSchema(): array
    {
        $tables = [];
        foreach (Schema::getTableListing() as $table) {
            $tables[$table] = Schema::getColumnListing($table);
        }
        ksort($tables);

        return $tables;
    }

    private function migrationNames(): array
    {
        if (! Schema::hasTable('migrations')) {
            throw new BackupRestoreException('The migration table is not available for backup validation.');
        }

        return DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
    }

    private function validPayloadEntryName(mixed $name): bool
    {
        return is_string($name) && preg_match('/\Apayload\/[0-9a-f-]{36}\.(?:jsonl|file)\z/i', $name) === 1;
    }

    private function validStoredFilePath(string $disk, mixed $path): bool
    {
        if (! is_string($path) || $path === '' || str_contains($path, "\0") || str_starts_with($path, '/')
            || str_contains($path, '\\') || str_contains($path, ':')
            || preg_match('/(^|\/)\.\.?($|\/)/', $path)) {
            return false;
        }

        return $disk !== 'local'
            || (! str_starts_with(strtolower($path), 'backups/') && ! $this->hasHiddenPathSegment($path));
    }

    private function hasHiddenPathSegment(string $path): bool
    {
        foreach (explode('/', $path) as $segment) {
            if (str_starts_with($segment, '.')) {
                return true;
            }
        }

        return false;
    }

    private function validateConfirmationInput(Request $request): void
    {
        if ($request->input('confirmation') !== 'RESTORE') {
            throw new BackupRestoreException('Type RESTORE in the confirmation field to replace current data.');
        }
    }

    private function backupDirectory(): string
    {
        return Storage::disk('local')->path('backups');
    }

    private function backupKey(): string
    {
        $applicationKey = (string) config('app.key');
        if ($applicationKey === '') {
            throw new BackupRestoreException('Set APP_KEY before creating or restoring encrypted backups.');
        }

        if (str_starts_with($applicationKey, 'base64:')) {
            $decoded = base64_decode(substr($applicationKey, 7), true);
            if ($decoded === false) {
                throw new BackupRestoreException('The configured APP_KEY is invalid for encrypted backups.');
            }
            $applicationKey = $decoded;
        }

        return hash_hkdf('sha256', $applicationKey, 32, 'archives-backup-encryption-v1');
    }

    private function assertZipSupport(): void
    {
        if (! class_exists(ZipArchive::class) || ! method_exists(ZipArchive::class, 'setEncryptionName')
            || ! defined(ZipArchive::class.'::EM_AES_256')) {
            throw new BackupRestoreException('Encrypted backups require PHP ZIP support with AES-256 encryption enabled.');
        }
    }

    private function removePath(string $path): void
    {
        if (is_file($path) && ! unlink($path)) {
            Log::warning('Unable to remove an incomplete backup file.');
        }
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $removed = $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            if (! $removed) {
                Log::warning('Unable to remove a private backup staging item.');
            }
        }

        if (! rmdir($path)) {
            Log::warning('Unable to remove a private backup staging directory.');
        }
    }
}

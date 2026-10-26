<?php

namespace App\Http\Controllers;

use App\Models\AdminActionLog;
use App\Services\BackupRestoreException;
use App\Services\BackupRestoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AdminBackupController extends Controller
{
    public function index(BackupRestoreService $backupRestoreService): View|RedirectResponse
    {
        if ($response = $this->requireSuperAdmin()) {
            return $response;
        }

        $backups = $backupRestoreService->listBackups();
        $activities = AdminActionLog::query()
            ->whereIn('action', ['backup.created', 'backup.failed', 'restore.completed', 'restore.failed'])
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return view('super-admin.backups', compact('backups', 'activities'));
    }

    public function create(Request $request, BackupRestoreService $backupRestoreService): RedirectResponse
    {
        if ($response = $this->requireSuperAdmin()) {
            return $response;
        }

        try {
            $backupRestoreService->createBackup((int) session('user_id'), 'super_admin', $request);
        } catch (Throwable $exception) {
            Log::error('ARCHIVES backup creation failed.', [
                'user_id' => (int) session('user_id'),
                'exception_class' => $exception::class,
            ]);
            $backupRestoreService->recordActivity(
                (int) session('user_id'),
                'super_admin',
                'backup.failed',
                500,
                ['reason' => 'Backup creation failed.'],
                $request
            );

            $message = $exception instanceof BackupRestoreException
                ? $exception->getMessage()
                : 'Backup could not be created. Check the server logs and private storage permissions.';

            return back()->with('error', $message);
        }

        return back()->with('success', 'Encrypted database and document backup created successfully.');
    }

    public function download(string $name, BackupRestoreService $backupRestoreService): BinaryFileResponse|RedirectResponse
    {
        if ($response = $this->requireSuperAdmin()) {
            return $response;
        }

        return response()->download($backupRestoreService->backupPath($name), $name, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function restore(Request $request, BackupRestoreService $backupRestoreService): RedirectResponse
    {
        if ($response = $this->requireSuperAdmin()) {
            return $response;
        }

        $validated = $request->validate([
            'backup_file' => ['nullable', 'required_without:backup_name', 'file', 'mimes:zip', 'max:1048576'],
            'backup_name' => ['nullable', 'required_without:backup_file', 'string', 'regex:/\Aarchives-backup-[0-9]{8}-[0-9]{6}-[0-9a-fA-F-]{36}\.zip\z/'],
            'confirmation' => ['required', 'in:RESTORE'],
        ], [
            'backup_file.max' => 'The backup archive must be no larger than 1 GB.',
            'confirmation.in' => 'Type RESTORE exactly to confirm replacing the current data.',
        ]);

        try {
            $backupPath = isset($validated['backup_file'])
                ? $validated['backup_file']->getRealPath()
                : $backupRestoreService->backupPath($validated['backup_name']);
            $backupRestoreService->restoreUploadedBackup(
                $backupPath,
                (int) session('user_id'),
                'super_admin',
                $request
            );
        } catch (Throwable $exception) {
            Log::error('ARCHIVES backup restore failed.', [
                'user_id' => (int) session('user_id'),
                'exception_class' => $exception::class,
            ]);
            try {
                $backupRestoreService->recordActivity(
                    (int) session('user_id'),
                    'super_admin',
                    'restore.failed',
                    500,
                    ['reason' => 'Restore failed validation, compatibility, or data checks.'],
                    $request
                );
            } catch (Throwable $auditException) {
                Log::error('ARCHIVES restore failure could not be recorded.', [
                    'user_id' => (int) session('user_id'),
                    'exception_class' => $auditException::class,
                ]);
            }

            $message = $exception instanceof BackupRestoreException
                ? $exception->getMessage()
                : 'The backup could not be restored because an internal operation failed.';

            return back()->with('error', 'Restore failed: '.$message.' Any pre-restore safety backup is retained.');
        }

        return back()->with('success', 'Backup restored successfully. A safety backup of the previous data was created first.');
    }

    private function requireSuperAdmin(): ?RedirectResponse
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        return null;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\BackupRestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateArchivesBackup extends Command
{
    protected $signature = 'archives:backup';

    protected $description = 'Create an encrypted backup of ARCHIVES database and documents';

    public function handle(BackupRestoreService $backupRestoreService): int
    {
        try {
            $path = $backupRestoreService->createBackup();
            $this->info('Encrypted ARCHIVES backup created: '.basename($path));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Scheduled ARCHIVES backup failed.', ['exception_class' => $exception::class]);
            try {
                $backupRestoreService->recordActivity(
                    null,
                    'system',
                    'backup.failed',
                    500,
                    ['reason' => 'Scheduled backup creation failed.']
                );
            } catch (Throwable $auditException) {
                Log::error('Scheduled ARCHIVES backup failure could not be recorded.', [
                    'exception_class' => $auditException::class,
                ]);
            }
            $this->error('Scheduled backup failed. Check the server logs and private storage permissions.');

            return self::FAILURE;
        }
    }
}

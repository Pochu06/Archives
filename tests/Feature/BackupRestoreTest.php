<?php

namespace Tests\Feature;

use App\Services\BackupRestoreException;
use App\Services\BackupRestoreService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupRestoreTest extends TestCase
{
    private array $createdBackupPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('research', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
        Schema::create('migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->unsignedInteger('batch');
        });
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role', 20);
            $table->string('action');
            $table->string('method', 10);
            $table->string('route_name')->nullable();
            $table->string('path');
            $table->text('url');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->createdBackupPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        Schema::disableForeignKeyConstraints();
        foreach (['notifications', 'admin_action_logs', 'system_settings', 'migrations', 'research', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_backup_encrypts_database_records_and_documents_and_restore_recovers_them(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Backup Administrator',
            'password' => password_hash('not-a-plaintext-password', PASSWORD_BCRYPT),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $researchId = DB::table('research')->insertGetId([
            'title' => 'Original title',
            'file_path' => 'research_files/research.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('migrations')->insert(['migration' => 'test_schema', 'batch' => 1]);
        DB::table('system_settings')->insert([
            'key' => 'ai_features_enabled',
            'value' => '0',
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Storage::disk('public')->put('research_files/research.pdf', 'original private document bytes');

        $service = app(BackupRestoreService::class);
        $backupPath = $service->createBackup($userId, 'super_admin');
        $this->createdBackupPaths[] = $backupPath;

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($backupPath));
        $this->assertGreaterThan(0, $zip->numFiles);
        $this->assertSame(ZipArchive::EM_AES_256, $zip->statName('manifest.json')['encryption_method']);
        $this->assertFalse($zip->getFromName('manifest.json'));
        $zip->setPassword($this->backupPassword());
        $this->assertStringContainsString('"application":"ARCHIVES"', $zip->getFromName('manifest.json'));
        $zip->close();

        DB::table('research')->where('id', $researchId)->update(['title' => 'Changed after backup']);
        DB::table('system_settings')->where('key', 'ai_features_enabled')->update(['value' => '1']);
        Storage::disk('public')->put('research_files/research.pdf', 'changed document bytes');

        $service->restoreUploadedBackup(
            $backupPath,
            $userId,
            'super_admin',
            Request::create('/admin/backups/restore', 'POST', ['confirmation' => 'RESTORE'])
        );

        $this->assertSame('Original title', DB::table('research')->where('id', $researchId)->value('title'));
        $this->assertSame('0', DB::table('system_settings')->where('key', 'ai_features_enabled')->value('value'));
        $this->assertSame('original private document bytes', Storage::disk('public')->get('research_files/research.pdf'));
        $this->assertDatabaseHas('admin_action_logs', [
            'user_id' => $userId,
            'action' => 'restore.completed',
            'response_status' => 200,
        ]);
        $this->assertCount(2, app(BackupRestoreService::class)->listBackups());
        foreach (app(BackupRestoreService::class)->listBackups() as $backup) {
            $this->createdBackupPaths[] = app(BackupRestoreService::class)->backupPath($backup['name']);
        }
    }

    public function test_restore_requires_explicit_confirmation_and_rejects_incompatible_driver(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('migrations')->insert(['migration' => 'test_schema', 'batch' => 1]);
        $service = app(BackupRestoreService::class);
        $backupPath = $service->createBackup($userId, 'super_admin');
        $this->createdBackupPaths[] = $backupPath;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Type RESTORE');
        $service->restoreUploadedBackup(
            $backupPath,
            $userId,
            'super_admin',
            Request::create('/admin/backups/restore', 'POST', ['confirmation' => 'no'])
        );
    }

    public function test_restore_rejects_a_migration_mismatch_before_replacing_any_data(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('migrations')->insert(['migration' => 'test_schema', 'batch' => 1]);
        $researchId = DB::table('research')->insertGetId([
            'title' => 'Keep current data',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $service = app(BackupRestoreService::class);
        $backupPath = $service->createBackup($userId, 'super_admin');
        $this->createdBackupPaths[] = $backupPath;
        DB::table('migrations')->update(['migration' => 'different_schema']);

        try {
            $service->restoreUploadedBackup(
                $backupPath,
                $userId,
                'super_admin',
                Request::create('/admin/backups/restore', 'POST', ['confirmation' => 'RESTORE'])
            );
            $this->fail('A backup from a different migration history should not restore.');
        } catch (BackupRestoreException $exception) {
            $this->assertSame('The backup migration history does not match this installation.', $exception->getMessage());
        }

        $this->assertSame('Keep current data', DB::table('research')->where('id', $researchId)->value('title'));
        $this->assertCount(1, app(BackupRestoreService::class)->listBackups());
    }

    public function test_restore_rolls_back_files_when_database_replacement_fails(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('migrations')->insert(['migration' => 'test_schema', 'batch' => 1]);
        $researchId = DB::table('research')->insertGetId([
            'title' => 'Original title',
            'file_path' => 'research_files/research.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Storage::disk('public')->put('research_files/research.pdf', 'original document');

        $service = app(BackupRestoreService::class);
        $backupPath = $service->createBackup($userId, 'super_admin');
        $this->createdBackupPaths[] = $backupPath;
        DB::table('research')->where('id', $researchId)->update(['title' => 'Current title']);
        Storage::disk('public')->put('research_files/research.pdf', 'current document');
        DB::statement("CREATE TRIGGER reject_research_restore BEFORE INSERT ON research BEGIN SELECT RAISE(ABORT, 'restore failure'); END");

        try {
            $service->restoreUploadedBackup(
                $backupPath,
                $userId,
                'super_admin',
                Request::create('/admin/backups/restore', 'POST', ['confirmation' => 'RESTORE'])
            );
            $this->fail('A database replacement failure should abort the restore.');
        } catch (\Throwable) {
            $this->assertSame('Current title', DB::table('research')->where('id', $researchId)->value('title'));
            $this->assertSame('current document', Storage::disk('public')->get('research_files/research.pdf'));
        }

        foreach ($service->listBackups() as $backup) {
            $path = $service->backupPath($backup['name']);
            if (! in_array($path, $this->createdBackupPaths, true)) {
                $this->createdBackupPaths[] = $path;
            }
        }
    }

    public function test_backup_and_restore_endpoints_are_restricted_to_super_administrators(): void
    {
        $this->withSession(['user_id' => 42, 'user_role' => 'student'])
            ->get(route('admin.backups.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Unauthorized.');

        $this->withSession(['user_id' => 42, 'user_role' => 'student'])
            ->post(route('admin.backups.create'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Unauthorized.');

        $this->withSession(['user_id' => 42, 'user_role' => 'student'])
            ->post(route('admin.backups.restore'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Unauthorized.');
    }

    public function test_super_admin_can_create_list_download_and_restore_a_stored_backup(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'System Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('migrations')->insert(['migration' => 'test_schema', 'batch' => 1]);
        $researchId = DB::table('research')->insertGetId([
            'title' => 'Original archive title',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $session = ['user_id' => $userId, 'user_role' => 'super_admin'];

        $this->withSession($session)->post(route('admin.backups.create'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Encrypted database and document backup created successfully.');

        $backups = app(BackupRestoreService::class)->listBackups();
        $this->assertCount(1, $backups);
        $this->createdBackupPaths[] = app(BackupRestoreService::class)->backupPath($backups[0]['name']);

        $this->withSession($session)->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee($backups[0]['name']);
        $this->withSession($session)->get(route('admin.backups.download', $backups[0]['name']))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        DB::table('research')->where('id', $researchId)->update(['title' => 'Changed current title']);
        $this->withSession($session)->post(route('admin.backups.restore'), [
            'backup_name' => $backups[0]['name'],
            'confirmation' => 'RESTORE',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Backup restored successfully. A safety backup of the previous data was created first.');

        $this->assertSame('Original archive title', DB::table('research')->where('id', $researchId)->value('title'));
        $this->assertDatabaseHas('admin_action_logs', [
            'user_id' => $userId,
            'action' => 'restore.completed',
            'response_status' => 200,
        ]);
        foreach (app(BackupRestoreService::class)->listBackups() as $backup) {
            $path = app(BackupRestoreService::class)->backupPath($backup['name']);
            if (! in_array($path, $this->createdBackupPaths, true)) {
                $this->createdBackupPaths[] = $path;
            }
        }
    }

    public function test_user_guide_is_accessible_and_only_super_admins_see_backup_restore_instructions(): void
    {
        $this->get(route('user-guide'))
            ->assertOk()
            ->assertSee('Students and faculty: submit research')
            ->assertDontSee('Super administrators: backup and restore');

        $this->withSession(['user_id' => 42, 'user_role' => 'student'])
            ->get(route('user-guide'))
            ->assertOk()
            ->assertSee('Students and faculty: submit research')
            ->assertDontSee('Super administrators: backup and restore');

        $this->withSession(['user_id' => 7, 'user_role' => 'super_admin'])
            ->get(route('user-guide'))
            ->assertOk()
            ->assertSee('Super administrators: backup and restore');
    }

    private function backupPassword(): string
    {
        $applicationKey = (string) config('app.key');
        if (str_starts_with($applicationKey, 'base64:')) {
            $applicationKey = base64_decode(substr($applicationKey, 7), true);
        }

        return hash_hkdf('sha256', $applicationKey, 32, 'archives-backup-encryption-v1');
    }
}

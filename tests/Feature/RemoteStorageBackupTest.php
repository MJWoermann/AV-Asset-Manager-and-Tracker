<?php

namespace Tests\Feature;

use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use App\Enums\StorageOperationStatus;
use App\Jobs\BackupApplicationJob;
use App\Jobs\MigrateAttachmentDiskJob;
use App\Jobs\RestoreApplicationJob;
use App\Models\File;
use App\Models\Setting;
use App\Models\StorageOperation;
use App\Models\User;
use App\Services\AppBackupService;
use App\Services\AttachmentDiskMigrator;
use App\Services\FileAttachmentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class RemoteStorageBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('qnap');
        Config::set('attachments.disk', 'local');
        Config::set('attachments.backup_disk', 'qnap');
        Config::set('attachments.allowed_disks', ['local', 'qnap', 'sharepoint']);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    protected function seedUploadOnLocal(string $contents = 'attachment-bytes'): File
    {
        $path = 'attachments/test/manual.pdf';
        Storage::disk('local')->put($path, $contents);

        return File::factory()->create([
            'title' => 'Manual',
            'category' => FileCategory::Manual,
            'source_type' => FileSourceType::Upload,
            'disk' => 'local',
            'path' => $path,
            'content_hash' => hash('sha256', $contents),
            'size_bytes' => strlen($contents),
            'mime_type' => 'application/pdf',
            'original_filename' => 'manual.pdf',
            'thumbnail_path' => null,
        ]);
    }

    public function test_qnap_disk_uses_mount_driver_by_default(): void
    {
        $this->assertSame('local', config('filesystems.disks.qnap.driver'));
        $this->assertNotEmpty(config('filesystems.disks.qnap.root'));
    }

    public function test_qnap_disk_can_resolve_sftp_from_env(): void
    {
        Config::set('filesystems.disks.qnap', [
            'driver' => 'sftp',
            'host' => 'nas.example.test',
            'username' => 'av',
            'password' => 'secret',
            'port' => 22,
            'root' => '/share',
            'throw' => true,
        ]);

        $this->assertSame('sftp', config('filesystems.disks.qnap.driver'));
        $this->assertSame('nas.example.test', config('filesystems.disks.qnap.host'));
    }

    public function test_migrator_copies_blobs_updates_disk_and_can_delete_source(): void
    {
        $file = $this->seedUploadOnLocal('hello-migrate');

        $result = app(AttachmentDiskMigrator::class)->migrate('local', 'qnap', deleteSource: true);

        $this->assertSame(1, $result['migrated']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame('qnap', $file->fresh()->disk);
        Storage::disk('qnap')->assertExists($file->path);
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_migrator_dry_run_does_not_change_disk(): void
    {
        $file = $this->seedUploadOnLocal('dry-run');

        $result = app(AttachmentDiskMigrator::class)->migrate('local', 'qnap', dryRun: true);

        $this->assertSame(1, $result['migrated']);
        $this->assertTrue($result['dry_run']);
        $this->assertSame('local', $file->fresh()->disk);
        Storage::disk('qnap')->assertMissing($file->path);
    }

    public function test_backup_writes_manifest_files_index_and_attachment_without_secrets(): void
    {
        $file = $this->seedUploadOnLocal('backup-blob');

        $result = app(AppBackupService::class)->backup('qnap');

        Storage::disk('qnap')->assertExists($result['path'].'/manifest.json');
        Storage::disk('qnap')->assertExists($result['path'].'/data/files_index.json');
        Storage::disk('qnap')->assertExists($result['path'].'/attachments/'.$file->path);

        $manifest = json_decode(Storage::disk('qnap')->get($result['path'].'/manifest.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame('qnap', $manifest['disk']);
        $this->assertArrayHasKey('created_at', $manifest);

        $index = json_decode(Storage::disk('qnap')->get($result['path'].'/data/files_index.json'), true);
        $this->assertNotEmpty($index['files'] ?? []);

        $blob = Storage::disk('qnap')->get($result['path'].'/attachments/'.$file->path);
        $this->assertSame('backup-blob', $blob);

        $allFiles = Storage::disk('qnap')->allFiles($result['path']);
        foreach ($allFiles as $path) {
            $contents = Storage::disk('qnap')->get($path);
            $this->assertStringNotContainsString('GRAPH_CLIENT_SECRET', $contents);
            $this->assertStringNotContainsString('QNAP_SFTP_PASSWORD', $contents);
            $this->assertStringNotContainsString('APP_KEY=', $contents);
        }

        $lastRun = Setting::getValue(AppBackupService::LAST_RUN_KEY);
        $this->assertSame($result['path'], $lastRun['path'] ?? null);
    }

    public function test_scanner_cannot_access_storage_admin(): void
    {
        $scanner = $this->scanner();

        $this->actingAs($scanner)
            ->get(route('admin.storage.edit'))
            ->assertForbidden();

        $this->actingAs($scanner)
            ->post(route('admin.storage.migrate'), [
                'from' => 'local',
                'to' => 'qnap',
                'dry_run' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($scanner)
            ->post(route('admin.storage.backup'), [
                'disk' => 'qnap',
            ])
            ->assertForbidden();

        $this->actingAs($scanner)
            ->post(route('admin.storage.backup-test'), [
                'disk' => 'qnap',
            ])
            ->assertForbidden();

        $this->actingAs($scanner)
            ->post(route('admin.storage.restore-latest'), [
                'disk' => 'qnap',
                'password' => 'password',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_view_storage_and_queue_migrate_and_backup(): void
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.storage.edit'))
            ->assertOk()
            ->assertSee('Storage')
            ->assertSee('ATTACHMENTS_DISK')
            ->assertSee('Backup now')
            ->assertSee('Restore latest backup')
            ->assertSee('Test backup location')
            ->assertSee('Job log')
            ->assertSee('No storage jobs yet.');

        $this->actingAs($admin)
            ->post(route('admin.storage.migrate'), [
                'from' => 'local',
                'to' => 'qnap',
                'dry_run' => '1',
                'queue' => '1',
            ])
            ->assertRedirect(route('admin.storage.edit'));

        Queue::assertPushed(MigrateAttachmentDiskJob::class, function (MigrateAttachmentDiskJob $job): bool {
            return $job->from === 'local' && $job->to === 'qnap' && $job->dryRun === true;
        });

        $this->actingAs($admin)
            ->post(route('admin.storage.backup'), [
                'disk' => 'qnap',
                'queue' => '1',
            ])
            ->assertRedirect(route('admin.storage.edit'));

        Queue::assertPushed(BackupApplicationJob::class, function (BackupApplicationJob $job): bool {
            return $job->disk === 'qnap' && $job->operationId !== null;
        });

        $queued = StorageOperation::query()->where('status', StorageOperationStatus::Queued)->get();
        $this->assertCount(2, $queued);
        $this->assertTrue($queued->contains(fn (StorageOperation $operation): bool => str_contains($operation->command, 'app:backup') && str_contains($operation->command, '--disk=qnap') && str_contains($operation->command, '--queue')));
        $this->assertTrue($queued->contains(fn (StorageOperation $operation): bool => str_contains($operation->command, 'attachments:migrate-disk local qnap') && str_contains($operation->command, '--dry-run')));
    }

    public function test_artisan_migrate_disk_command_succeeds(): void
    {
        $this->seedUploadOnLocal('cli-migrate');

        $this->artisan('attachments:migrate-disk', [
            'from' => 'local',
            'to' => 'qnap',
        ])->assertSuccessful();

        $this->assertSame('qnap', File::query()->first()->disk);
        Storage::disk('qnap')->assertExists(File::query()->first()->path);
    }

    public function test_artisan_backup_command_succeeds(): void
    {
        $this->seedUploadOnLocal('cli-backup');

        $this->artisan('app:backup', ['--disk' => 'qnap'])
            ->assertSuccessful();

        $dirs = Storage::disk('qnap')->directories('backups');
        $this->assertNotEmpty($dirs);
        Storage::disk('qnap')->assertExists($dirs[0].'/manifest.json');
    }

    public function test_admin_backup_location_probe_writes_marker_file(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->from(route('admin.storage.edit'))
            ->post(route('admin.storage.backup-test'), [
                'disk' => 'qnap',
            ]);

        $contents = Storage::disk('qnap')->get(AppBackupService::WRITE_TEST_PATH);

        $response->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHas('status', function (string $status): bool {
                return str_contains($status, '[qnap]')
                    && str_contains($status, AppBackupService::WRITE_TEST_PATH);
            });
        $this->assertIsString($contents);
        $this->assertStringContainsString('backup location write test', $contents);
        $this->assertStringContainsString('disk: qnap', $contents);

        $operation = StorageOperation::query()->sole();
        $this->assertSame(StorageOperationStatus::Done, $operation->status);
        $this->assertSame('storage:test-backup --disk=qnap', $operation->command);

        $this->actingAs($admin)
            ->get(route('admin.storage.edit'))
            ->assertOk()
            ->assertSee('storage:test-backup --disk=qnap')
            ->assertSee('Done');
    }

    public function test_backup_location_probe_without_disk_returns_error_and_writes_nothing(): void
    {
        Config::set('attachments.backup_disk', null);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.storage.edit'))
            ->post(route('admin.storage.backup-test'), [
                'disk' => '',
            ])
            ->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHasErrors([
                'test' => 'BACKUP_DISK is not configured. Pass --disk= or set BACKUP_DISK.',
            ]);

        Storage::disk('qnap')->assertMissing(AppBackupService::WRITE_TEST_PATH);
        Storage::disk('local')->assertMissing(AppBackupService::WRITE_TEST_PATH);
        $this->assertSame(StorageOperationStatus::Failed, StorageOperation::query()->sole()->status);
    }

    public function test_backup_location_probe_includes_absolute_path_for_local_disk(): void
    {
        Config::set('attachments.backup_disk', 'local');
        Config::set('filesystems.disks.local.driver', 'local');
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->from(route('admin.storage.edit'))
            ->post(route('admin.storage.backup-test'));

        $absolute = Storage::disk('local')->path(AppBackupService::WRITE_TEST_PATH);

        $response->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHas('status', function (string $status) use ($absolute): bool {
                return str_contains($status, '[local]')
                    && str_contains($status, AppBackupService::WRITE_TEST_PATH)
                    && str_contains($status, $absolute);
            });
        Storage::disk('local')->assertExists(AppBackupService::WRITE_TEST_PATH);
        $this->assertFileExists($absolute);
    }

    public function test_backup_job_marks_the_operation_done(): void
    {
        $operation = StorageOperation::factory()->create([
            'status' => StorageOperationStatus::Queued,
        ]);

        (new BackupApplicationJob('qnap', 7, $operation->id))->handle(app(AppBackupService::class));

        $operation->refresh();
        $this->assertSame(StorageOperationStatus::Done, $operation->status);
        $this->assertNotNull($operation->finished_at);
        $this->assertStringContainsString('qnap', (string) $operation->detail);
    }

    public function test_latest_backup_is_the_newest_folder_with_a_manifest(): void
    {
        Storage::disk('qnap')->put('backups/2026-01-01_000000/manifest.json', '{"ok":true}');
        Storage::disk('qnap')->put('backups/2026-06-02_153000/manifest.json', '{"ok":true}');
        Storage::disk('qnap')->put('backups/2026-12-01_000000/readme.txt', 'incomplete');
        Storage::disk('qnap')->put(AppBackupService::WRITE_TEST_PATH, 'marker');

        $latest = app(AppBackupService::class)->latestBackup('qnap');

        $this->assertSame([
            'path' => 'backups/2026-06-02_153000',
            'disk' => 'qnap',
        ], $latest);
    }

    public function test_latest_backup_is_null_when_the_disk_has_no_manifest(): void
    {
        Storage::disk('qnap')->put('backups/2026-01-01_000000/readme.txt', 'incomplete');

        $this->assertNull(app(AppBackupService::class)->latestBackup('qnap'));
    }

    public function test_guest_restore_latest_redirects_to_login(): void
    {
        $this->post(route('admin.storage.restore-latest'), [
            'password' => 'password',
        ])->assertRedirect(route('login'));
    }

    public function test_admin_restore_latest_queues_the_newest_backup_and_ignores_a_posted_path(): void
    {
        Queue::fake();
        Config::set('performance.backup_force_queue', true);
        Storage::disk('qnap')->put('backups/2026-01-01_000000/manifest.json', '{"ok":true}');
        Storage::disk('qnap')->put('backups/2026-06-02_153000/manifest.json', '{"ok":true}');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.storage.restore-latest'), [
                'disk' => 'qnap',
                'password' => 'password',
                'queue' => '0',
                'path' => 'backups/2026-01-01_000000',
            ])
            ->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHas('status', 'Restore of backups/2026-06-02_153000 on [qnap] queued.');

        Queue::assertPushed(RestoreApplicationJob::class, function (RestoreApplicationJob $job): bool {
            return $job->backupPath === 'backups/2026-06-02_153000'
                && $job->disk === 'qnap'
                && $job->operationId !== null;
        });

        $operation = StorageOperation::query()->sole();
        $this->assertSame(StorageOperationStatus::Queued, $operation->status);
        $this->assertSame(
            'app:restore backups/2026-06-02_153000 --disk=qnap --force --queue',
            $operation->command,
        );
    }

    public function test_restore_latest_requires_the_current_password(): void
    {
        Queue::fake();
        Storage::disk('qnap')->put('backups/2026-06-02_153000/manifest.json', '{"ok":true}');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.storage.edit'))
            ->post(route('admin.storage.restore-latest'), [
                'disk' => 'qnap',
            ])
            ->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHasErrors([
                'password' => 'The password field is required.',
            ], null, 'restore');

        $this->actingAs($admin)
            ->from(route('admin.storage.edit'))
            ->post(route('admin.storage.restore-latest'), [
                'disk' => 'qnap',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHasErrors([
                'password' => 'The password is incorrect.',
            ], null, 'restore');

        Queue::assertNothingPushed();
        $this->assertSame(0, StorageOperation::query()->count());
    }

    public function test_restore_latest_reports_when_no_backup_exists(): void
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.storage.edit'))
            ->post(route('admin.storage.restore-latest'), [
                'disk' => 'qnap',
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHasErrors([
                'restore' => 'No backup found to restore.',
            ], null, 'restore');

        Queue::assertNothingPushed();
        $operation = StorageOperation::query()->sole();
        $this->assertSame(StorageOperationStatus::Failed, $operation->status);
        $this->assertSame('No backup found to restore.', $operation->detail);
    }

    public function test_restore_latest_rejects_an_unknown_disk(): void
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.storage.edit'))
            ->post(route('admin.storage.restore-latest'), [
                'disk' => 'not-a-disk',
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHasErrors([
                'disk' => 'The selected disk is invalid.',
            ], null, 'restore');

        Queue::assertNothingPushed();
        $this->assertSame(0, StorageOperation::query()->count());
    }

    public function test_admin_restore_runs_immediately_when_queue_is_off(): void
    {
        Config::set('performance.backup_force_queue', false);
        $admin = $this->admin();
        $backups = $this->mock(AppBackupService::class);
        $backups->shouldReceive('latestBackup')->once()->with('qnap')->andReturn([
            'path' => 'backups/2026-06-02_153000',
            'disk' => 'qnap',
        ]);
        $backups->shouldReceive('restore')->once()->with('backups/2026-06-02_153000', 'qnap')->andReturn([
            'path' => 'backups/2026-06-02_153000',
            'disk' => 'qnap',
            'attachments_restored' => 2,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.storage.restore-latest'), [
                'disk' => 'qnap',
                'password' => 'password',
                'queue' => '0',
            ])
            ->assertRedirect(route('admin.storage.edit'))
            ->assertSessionHas('status', 'Restored [qnap] backups/2026-06-02_153000; attachments restored=2.');

        $operation = StorageOperation::query()->sole();
        $this->assertSame(StorageOperationStatus::Done, $operation->status);
        $this->assertSame('app:restore backups/2026-06-02_153000 --disk=qnap --force', $operation->command);
        $this->assertSame('Restored [qnap] backups/2026-06-02_153000; attachments restored=2.', $operation->detail);
    }

    public function test_restore_job_marks_the_operation_done(): void
    {
        $operation = StorageOperation::factory()->create([
            'status' => StorageOperationStatus::Queued,
        ]);
        $backups = $this->createMock(AppBackupService::class);
        $backups->expects($this->once())
            ->method('restore')
            ->with('backups/2026-06-02_153000', 'qnap')
            ->willReturn([
                'path' => 'backups/2026-06-02_153000',
                'disk' => 'qnap',
                'attachments_restored' => 3,
            ]);

        (new RestoreApplicationJob('backups/2026-06-02_153000', 'qnap', $operation->id))->handle($backups);

        $operation->refresh();
        $this->assertSame(StorageOperationStatus::Done, $operation->status);
        $this->assertSame('[qnap] backups/2026-06-02_153000; attachments restored=3', $operation->detail);
        $this->assertNotNull($operation->finished_at);
    }

    public function test_restore_job_marks_the_operation_failed(): void
    {
        $operation = StorageOperation::factory()->create([
            'status' => StorageOperationStatus::Queued,
        ]);
        $backups = $this->createMock(AppBackupService::class);
        $backups->expects($this->once())
            ->method('restore')
            ->willThrowException(new RuntimeException('Backup manifest not found.'));

        try {
            (new RestoreApplicationJob('backups/missing', 'qnap', $operation->id))->handle($backups);
            $this->fail('Restore job should surface a failed restore.');
        } catch (RuntimeException) {
            $operation->refresh();
            $this->assertSame(StorageOperationStatus::Failed, $operation->status);
            $this->assertSame('Backup manifest not found.', $operation->detail);
            $this->assertNotNull($operation->finished_at);
        }
    }

    public function test_sql_dump_restore_replaces_rows(): void
    {
        Setting::putValue('keep.me', ['no' => true]);
        $sql = "DELETE FROM settings;\nINSERT INTO settings (\"key\", \"value\", \"created_at\", \"updated_at\") VALUES ('restore.probe', '{\"ok\":true}', '2026-11-02 00:00:00', '2026-11-02 00:00:00');\n";
        $this->putGzippedSqlBackup($sql);

        app(AppBackupService::class)->restore('backups/2026-11-02_095818', 'qnap');

        $this->assertNull(Setting::getValue('keep.me'));
        $this->assertSame(['ok' => true], Setting::getValue('restore.probe'));
    }

    public function test_mysql_sql_dump_restore_sends_the_script_to_the_mysql_client(): void
    {
        Process::fake();
        $sql = "DROP TABLE IF EXISTS `widgets`;\nINSERT INTO `widgets` VALUES (1);\n";
        $this->putGzippedSqlBackup($sql);
        $backups = $this->mysqlRestoreDouble();

        $result = $backups->restore('backups/2026-11-02_095818', 'qnap');

        $this->assertSame('backups/2026-11-02_095818', $result['path']);
        $this->assertSame(0, $result['attachments_restored']);
        $this->assertSame($sql, $backups->seenSql);
        Process::assertRan(function (mixed $process): bool {
            $command = $process->command;
            $flat = is_array($command) ? implode("\n", $command) : (string) $command;

            return str_contains($flat, 'mysql')
                && str_contains($flat, '--binary-mode')
                && str_contains($flat, 'av_assets')
                && str_contains($flat, 'av')
                && ($process->environment['MYSQL_PWD'] ?? null) === 'secret';
        });
    }

    public function test_mysql_sql_dump_restore_fails_when_the_client_errors(): void
    {
        Process::fake([
            '*' => Process::result(errorOutput: 'ERROR 2006 (HY000): MySQL server has gone away', exitCode: 1),
        ]);
        $this->putGzippedSqlBackup("DROP TABLE IF EXISTS `widgets`;\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MySQL server has gone away');

        $this->mysqlRestoreDouble()->restore('backups/2026-11-02_095818', 'qnap');
    }

    public function test_mysql_sql_dump_restore_fails_when_the_client_is_missing(): void
    {
        $this->putGzippedSqlBackup("DROP TABLE IF EXISTS `widgets`;\n");
        $backups = new class(app(FileAttachmentService::class), app(AttachmentDiskMigrator::class)) extends AppBackupService
        {
            public string $seenSql = '';

            protected function databaseDriver(): string
            {
                return 'mysql';
            }

            protected function findBinary(string $name): ?string
            {
                return null;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('mysql client is not installed');

        $backups->restore('backups/2026-11-02_095818', 'qnap');
    }

    public function test_backup_job_marks_the_operation_failed(): void
    {
        $operation = StorageOperation::factory()->create([
            'status' => StorageOperationStatus::Queued,
        ]);

        try {
            (new BackupApplicationJob('missing-disk', 7, $operation->id))->handle(app(AppBackupService::class));
            $this->fail('Backup job should reject an unknown disk.');
        } catch (InvalidArgumentException) {
            $operation->refresh();
            $this->assertSame(StorageOperationStatus::Failed, $operation->status);
            $this->assertNotNull($operation->finished_at);
        }
    }

    protected function putGzippedSqlBackup(string $sql): void
    {
        Storage::disk('qnap')->put('backups/2026-11-02_095818/manifest.json', '{"ok":true}');
        Storage::disk('qnap')->put('backups/2026-11-02_095818/database.sql.gz', gzencode($sql));
        Storage::disk('qnap')->put('backups/2026-11-02_095818/data/files_index.json', '{"files":[]}');
    }

    protected function mysqlRestoreDouble(): AppBackupService
    {
        return new class(app(FileAttachmentService::class), app(AttachmentDiskMigrator::class)) extends AppBackupService
        {
            public string $seenSql = '';

            protected function databaseDriver(): string
            {
                return 'mysql';
            }

            /**
             * @return array<string, mixed>
             */
            protected function databaseConnection(): array
            {
                return [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'database' => 'av_assets',
                    'username' => 'av',
                    'password' => 'secret',
                ];
            }

            protected function findBinary(string $name): ?string
            {
                return $name === 'mysql' ? 'mysql' : null;
            }

            protected function runMysqlClient(array $command, $input, string $password): ProcessResult
            {
                $this->seenSql = is_resource($input) ? (stream_get_contents($input) ?: '') : (string) $input;
                if (is_resource($input)) {
                    rewind($input);
                }

                return parent::runMysqlClient($command, $input, $password);
            }
        };
    }
}

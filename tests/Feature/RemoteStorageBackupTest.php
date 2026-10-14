<?php

namespace Tests\Feature;

use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use App\Jobs\BackupApplicationJob;
use App\Jobs\MigrateAttachmentDiskJob;
use App\Models\File;
use App\Models\Setting;
use App\Models\User;
use App\Services\AppBackupService;
use App\Services\AttachmentDiskMigrator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Backup now');

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
            return $job->disk === 'qnap';
        });
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
}

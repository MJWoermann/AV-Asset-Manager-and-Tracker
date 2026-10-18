<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Jobs\BackupApplicationJob;
use App\Jobs\MigrateAttachmentDiskJob;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\User;
use App\Support\PerformanceLimits;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerformanceLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    public function test_artisan_can_show_set_and_reset_performance_limits(): void
    {
        $this->artisan('app:performance-limits', ['action' => 'show'])
            ->assertSuccessful();

        $this->artisan('app:performance-limits', [
            'action' => 'set',
            'key' => 'import_max_rows',
            'value' => '42',
        ])->assertSuccessful();

        $this->assertSame(42, PerformanceLimits::int('import_max_rows'));

        $this->artisan('app:performance-limits', [
            'action' => 'reset',
            'key' => 'import_max_rows',
        ])->assertSuccessful();

        $this->assertSame(
            (int) config('performance.import_max_rows'),
            PerformanceLimits::int('import_max_rows'),
        );
    }

    public function test_import_rejects_spreadsheets_over_row_limit(): void
    {
        Storage::fake('local');
        PerformanceLimits::put(['import_max_rows' => 2]);

        $csv = "Name,Type\nOne,LX Fixture\nTwo,LX Fixture\nThree,LX Fixture\n";
        $file = UploadedFile::fake()->createWithContent('assets.csv', $csv);

        $manager = $this->manager();

        $upload = $this->actingAs($manager)->post(route('import.upload'), [
            'file' => $file,
        ]);
        $upload->assertOk();

        $columns = session('import.columns');
        $mapping = [];
        foreach ($columns as $column) {
            $mapping[$column] = match (strtolower((string) $column)) {
                'name' => 'name',
                'type' => 'item_type',
                default => null,
            };
        }

        $response = $this->actingAs($manager)
            ->from(route('import.create'))
            ->post(route('import.prepare'), [
                'mapping' => $mapping,
            ]);

        $response->assertRedirect(route('import.create'));
        $response->assertSessionHasErrors('file');
        $this->assertStringContainsString('import limit is 2', session('errors')->first('file'));
    }

    public function test_compare_rejects_lists_over_item_limit(): void
    {
        PerformanceLimits::put(['compare_max_list_items' => 1]);

        $user = $this->manager();
        $itemType = ItemType::create(['name' => 'Fixture', 'slug' => 'fixture']);
        $a = Asset::create(['name' => 'A', 'item_type_id' => $itemType->id, 'status' => AssetStatus::Available]);
        $b = Asset::create(['name' => 'B', 'item_type_id' => $itemType->id, 'status' => AssetStatus::Available]);

        $scanned = AssetList::create(['name' => 'Event', 'type' => 'event', 'created_by' => $user->id]);
        $inventory = AssetList::create(['name' => 'Inv', 'type' => 'inventory', 'created_by' => $user->id]);

        AssetListItem::create(['asset_list_id' => $scanned->id, 'asset_id' => $a->id, 'quantity' => 1, 'added_by' => $user->id]);
        AssetListItem::create(['asset_list_id' => $scanned->id, 'asset_id' => $b->id, 'quantity' => 1, 'added_by' => $user->id]);
        AssetListItem::create(['asset_list_id' => $inventory->id, 'asset_id' => $a->id, 'quantity' => 1, 'added_by' => $user->id]);

        $response = $this->actingAs($user)
            ->from(route('reports.compare'))
            ->post(route('reports.compare.run'), [
                'scanned_list_id' => $scanned->id,
                'inventory_list_id' => $inventory->id,
            ]);

        $response->assertRedirect(route('reports.compare'));
        $response->assertSessionHasErrors('scanned_list_id');
    }

    public function test_admin_backup_and_migrate_are_forced_onto_the_queue(): void
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.storage.migrate'), [
                'from' => 'local',
                'to' => 'qnap',
                'dry_run' => '1',
            ])
            ->assertRedirect(route('admin.storage.edit'));

        Queue::assertPushed(MigrateAttachmentDiskJob::class);

        $this->actingAs($admin)
            ->post(route('admin.storage.backup'), [
                'disk' => 'qnap',
            ])
            ->assertRedirect(route('admin.storage.edit'));

        Queue::assertPushed(BackupApplicationJob::class, function (BackupApplicationJob $job): bool {
            return $job->disk === 'qnap'
                && $job->keep === PerformanceLimits::int('backup_default_keep');
        });
    }

    public function test_import_prepare_stores_prepared_rows_outside_session(): void
    {
        Storage::fake('local');
        ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);

        $csv = "Name,Type\nOne,LX Fixture\n";
        $file = UploadedFile::fake()->createWithContent('assets.csv', $csv);
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('import.upload'), [
            'file' => $file,
        ])->assertOk();

        $columns = session('import.columns');
        $mapping = [];
        foreach ($columns as $column) {
            $mapping[$column] = match (strtolower((string) $column)) {
                'name' => 'name',
                'type' => 'item_type',
                default => null,
            };
        }

        $this->actingAs($manager)->post(route('import.prepare'), [
            'mapping' => $mapping,
        ])->assertOk();

        $this->assertNull(session('import.prepared'));
        $preparedPath = session('import.prepared_path');
        $this->assertIsString($preparedPath);
        Storage::assertExists($preparedPath);
    }
}

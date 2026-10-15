<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Enums\MaintenanceLogType;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetMaintenanceLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake((string) config('attachments.disk', 'local'));
    }

    protected function manager(): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('inventory_manager');

        return $user;
    }

    protected function scanner(): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('scanner');

        return $user;
    }

    protected function makeAsset(AssetStatus $status = AssetStatus::Available): Asset
    {
        $itemType = ItemType::query()->create([
            'name' => 'Type '.fake()->unique()->numerify('###'),
            'slug' => 'type-'.fake()->unique()->numerify('###'),
        ]);

        return Asset::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'Asset '.fake()->unique()->numerify('###'),
            'status' => $status,
        ]);
    }

    public function test_scanner_can_create_fault_service_and_note_entries(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        foreach ([
            MaintenanceLogType::Fault,
            MaintenanceLogType::Service,
            MaintenanceLogType::Note,
        ] as $type) {
            $this->actingAs($user)
                ->post(route('assets.maintenance-logs.store', $asset), [
                    'type' => $type->value,
                    'summary' => $type->label().' summary',
                    'body' => 'Details for '.$type->value,
                ])
                ->assertRedirect(route('assets.show', [$asset, 'tab' => 'maintenance']));
        }

        $this->assertDatabaseCount('asset_maintenance_logs', 3);
        $this->assertSame(
            [
                MaintenanceLogType::Fault->value,
                MaintenanceLogType::Service->value,
                MaintenanceLogType::Note->value,
            ],
            AssetMaintenanceLog::query()->orderBy('id')->pluck('type')->map->value->all(),
        );
    }

    public function test_fault_with_status_updates_asset_and_stores_before_after(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset(AssetStatus::Available);

        $this->actingAs($user)
            ->post(route('assets.maintenance-logs.store', $asset), [
                'type' => MaintenanceLogType::Fault->value,
                'summary' => 'Fan noise',
                'status' => AssetStatus::Maintenance->value,
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'maintenance']));

        $asset->refresh();
        $log = AssetMaintenanceLog::query()->first();

        $this->assertSame(AssetStatus::Maintenance, $asset->status);
        $this->assertSame(AssetStatus::Available->value, $log->status_before);
        $this->assertSame(AssetStatus::Maintenance->value, $log->status_after);
    }

    public function test_service_and_note_cannot_set_status(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset(AssetStatus::Available);

        foreach ([MaintenanceLogType::Service, MaintenanceLogType::Note] as $type) {
            $this->actingAs($user)
                ->from(route('assets.show', [$asset, 'tab' => 'maintenance']))
                ->post(route('assets.maintenance-logs.store', $asset), [
                    'type' => $type->value,
                    'summary' => $type->label().' entry',
                    'status' => AssetStatus::Damaged->value,
                ])
                ->assertRedirect(route('assets.show', [$asset, 'tab' => 'maintenance']))
                ->assertSessionHasErrors('status');
        }

        $this->assertDatabaseCount('asset_maintenance_logs', 0);
        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
    }

    public function test_files_can_be_attached_on_create_and_via_upload_endpoint(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->post(route('assets.maintenance-logs.store', $asset), [
                'type' => MaintenanceLogType::Fault->value,
                'summary' => 'Broken latch',
                'files' => [
                    UploadedFile::fake()->createWithContent('fault-note.pdf', '%PDF-1.4 fault'),
                ],
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'maintenance']));

        $log = AssetMaintenanceLog::query()->first();
        $this->assertNotNull($log);
        $this->assertCount(1, $log->files);
        $this->assertSame(FileCategory::Maintenance, $log->files->first()->category);

        $this->actingAs($user)
            ->from(route('assets.show', [$asset, 'tab' => 'maintenance']))
            ->post(route('files.upload'), [
                'title' => 'Extra note',
                'category' => FileCategory::Maintenance->value,
                'file' => UploadedFile::fake()->createWithContent('extra-note.pdf', '%PDF-1.4 extra'),
                'linkable_type' => 'maintenance_log',
                'linkable_id' => $log->id,
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'maintenance']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('files', 2);
        $this->assertDatabaseCount('file_links', 2);
        $this->assertCount(2, $log->fresh()->files);
    }

    public function test_manager_can_delete_and_scanner_cannot(): void
    {
        $manager = $this->manager();
        $scanner = $this->scanner();
        $asset = $this->makeAsset();
        $log = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $manager->id,
            'summary' => 'Delete me',
        ]);

        $this->actingAs($scanner)
            ->delete(route('assets.maintenance-logs.destroy', [$asset, $log]))
            ->assertForbidden();

        $this->assertDatabaseHas('asset_maintenance_logs', ['id' => $log->id]);

        $this->actingAs($manager)
            ->delete(route('assets.maintenance-logs.destroy', [$asset, $log]))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'maintenance']));

        $this->assertDatabaseMissing('asset_maintenance_logs', ['id' => $log->id]);
    }

    public function test_maintenance_tab_renders_for_authenticated_users_without_feature_flag(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'type' => MaintenanceLogType::Service,
            'summary' => 'Annual service',
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'maintenance']))
            ->assertOk()
            ->assertSee('Maintenance', false)
            ->assertSee('Log entry', false)
            ->assertSee('Annual service', false)
            ->assertSee('Add entry', false);
    }

    public function test_maintenance_tab_filters_by_type(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'type' => MaintenanceLogType::Fault,
            'summary' => 'Fault only entry',
        ]);
        AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'type' => MaintenanceLogType::Service,
            'summary' => 'Service only entry',
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', [
                $asset,
                'tab' => 'maintenance',
                'log_type' => MaintenanceLogType::Fault->value,
            ]))
            ->assertOk()
            ->assertSee('Fault only entry', false)
            ->assertDontSee('Service only entry');
    }

    public function test_maintenance_tab_filters_by_user(): void
    {
        $user = $this->scanner();
        $other = $this->scanner();
        $asset = $this->makeAsset();

        AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'type' => MaintenanceLogType::Note,
            'summary' => 'Logged by current user',
        ]);
        AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $other->id,
            'type' => MaintenanceLogType::Note,
            'summary' => 'Logged by other user',
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', [
                $asset,
                'tab' => 'maintenance',
                'user_id' => $other->id,
            ]))
            ->assertOk()
            ->assertSee('Logged by other user', false)
            ->assertDontSee('Logged by current user');
    }

    public function test_maintenance_tab_filters_by_date_range(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'type' => MaintenanceLogType::Note,
            'summary' => 'In range maintenance',
            'occurred_at' => '2025-06-15 10:00:00',
        ]);
        AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'type' => MaintenanceLogType::Note,
            'summary' => 'Out of range maintenance',
            'occurred_at' => '2024-01-01 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', [
                $asset,
                'tab' => 'maintenance',
                'date_from' => '2025-06-01',
                'date_to' => '2025-06-30',
            ]))
            ->assertOk()
            ->assertSee('In range maintenance', false)
            ->assertDontSee('Out of range maintenance');
    }

    public function test_maintenance_tab_shows_empty_filter_message(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'type' => MaintenanceLogType::Note,
            'summary' => 'Existing note',
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', [
                $asset,
                'tab' => 'maintenance',
                'log_type' => MaintenanceLogType::Fault->value,
            ]))
            ->assertOk()
            ->assertSee('No maintenance entries match these filters.', false)
            ->assertDontSee('Existing note')
            ->assertDontSee('No maintenance entries yet.');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\InboundEmailActionStatus;
use App\Enums\LocationType;
use App\Enums\RepairStatus;
use App\Enums\SparePartLifecycleStatus;
use App\Enums\SparePartSeverity;
use App\Enums\SparePartStockStatus;
use App\Enums\StockMovementType;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetMaintenanceLog;
use App\Models\InboundEmailAction;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\Repair;
use App\Models\ScanSession;
use App\Models\SparePart;
use App\Models\SparePartMovement;
use App\Models\StockMovement;
use App\Models\UnknownScan;
use App\Models\User;
use App\Support\DashboardModules;
use App\Support\StockTracking;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    protected function withEmailFeature(User $user): User
    {
        return $this->enableFeature($user, 'email_integration');
    }

    protected function admin(bool $emailFeature = false): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $emailFeature ? $this->withEmailFeature($user) : $user;
    }

    protected function inventoryManager(bool $emailFeature = false): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $emailFeature ? $this->withEmailFeature($user) : $user;
    }

    /**
     * @param  list<string>  $enabled
     */
    protected function withDashboardModules(User $user, array $enabled): User
    {
        $available = DashboardModules::availableFor($user);
        $user->preferences = array_merge($user->preferences ?? [], [
            DashboardModules::PREFERENCE_KEY => [
                'order' => $available,
                'enabled' => $enabled,
            ],
        ]);
        $user->save();

        return $user->fresh();
    }

    protected function withStockFeature(User $user): User
    {
        return $this->enableFeature($user, StockTracking::FEATURE_KEY);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_status_and_type_counts_with_filter_links(): void
    {
        $user = $this->withDashboardModules($this->scanner(), [
            DashboardModules::ASSETS_BY_STATUS,
            DashboardModules::ASSETS_BY_TYPE,
            DashboardModules::WARRANTY_EXPIRY,
            DashboardModules::TEST_TAG_EXPIRY,
        ]);
        $fixture = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);
        $rack = ItemType::create(['name' => 'Rack/RoadCase', 'slug' => 'rack-roadcase']);

        Asset::create([
            'name' => 'Available Fixture',
            'item_type_id' => $fixture->id,
            'status' => AssetStatus::Available,
        ]);
        Asset::create([
            'name' => 'Lost Fixture',
            'item_type_id' => $fixture->id,
            'status' => AssetStatus::Lost,
        ]);
        Asset::create([
            'name' => 'Available Rack',
            'item_type_id' => $rack->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Customize modules');
        $response->assertSee('Assets by status');
        $response->assertSee('Assets by type');
        $response->assertSee('Warranty expiry');
        $response->assertSee('Test & Tag expiry');

        $byStatus = collect($response->viewData('byStatus'))->keyBy('value');
        $this->assertSame(2, $byStatus[AssetStatus::Available->value]['count']);
        $this->assertSame(1, $byStatus[AssetStatus::Lost->value]['count']);
        $this->assertSame(0, $byStatus[AssetStatus::Maintenance->value]['count']);

        $byType = collect($response->viewData('byType'))->keyBy('name');
        $this->assertSame(2, $byType['LX Fixture']['count']);
        $this->assertSame(1, $byType['Rack/RoadCase']['count']);

        $response->assertSee(
            route('assets.index', ['status' => AssetStatus::Available->value], absolute: false),
            false
        );
        $response->assertSee(
            route('assets.index', ['item_type_id' => $fixture->id], absolute: false),
            false
        );
    }

    public function test_dashboard_shows_latest_asset_changes(): void
    {
        $user = $this->scanner();
        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        $asset = Asset::create([
            'name' => 'Mic Pack',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-OLD',
        ]);

        $this->actingAs($user);
        $asset->update(['tp_barcode' => 'TP-NEW']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Latest changes');
        $response->assertSee('Mic Pack');
        $response->assertSee('updated');
        $response->assertSee('tp barcode');
        $response->assertSee('TP-OLD');
        $response->assertSee('TP-NEW');
        $response->assertSee($user->name);
        $response->assertSee(
            route('assets.show', [$asset, 'tab' => 'history'], absolute: false),
            false
        );
    }

    public function test_dashboard_shows_pending_inbound_email_count_for_reviewers_with_feature(): void
    {
        $user = $this->withDashboardModules($this->admin(emailFeature: true), [
            DashboardModules::INBOUND_EMAILS,
        ]);

        InboundEmailAction::factory()->count(2)->create([
            'status' => InboundEmailActionStatus::Pending,
        ]);
        InboundEmailAction::factory()->create([
            'status' => InboundEmailActionStatus::Applied,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Inbound emails');
        $response->assertSee('Pending review');
        $this->assertSame(2, $response->viewData('pendingInboundEmails'));
        $response->assertSee(
            route('admin.email.inbound.index', ['status' => 'pending'], absolute: false),
            false
        );
    }

    public function test_dashboard_hides_inbound_email_module_without_feature_or_review_role(): void
    {
        InboundEmailAction::factory()->create([
            'status' => InboundEmailActionStatus::Pending,
        ]);

        $scannerWithFeature = $this->withEmailFeature($this->scanner());
        $adminWithoutFeature = $this->admin(emailFeature: false);

        $this->actingAs($scannerWithFeature)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Inbound emails', false)
            ->assertDontSee(
                route('admin.email.inbound.index', ['status' => 'pending'], absolute: false),
                false
            );

        $this->assertNull(
            $this->actingAs($scannerWithFeature)
                ->get(route('dashboard'))
                ->viewData('pendingInboundEmails')
        );

        $this->actingAs($adminWithoutFeature)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Inbound emails', false);

        $this->assertNull(
            $this->actingAs($adminWithoutFeature)
                ->get(route('dashboard'))
                ->viewData('pendingInboundEmails')
        );
    }

    public function test_inventory_manager_with_feature_sees_pending_inbound_module(): void
    {
        $user = $this->withDashboardModules($this->inventoryManager(emailFeature: true), [
            DashboardModules::INBOUND_EMAILS,
        ]);

        InboundEmailAction::factory()->create([
            'status' => InboundEmailActionStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Inbound emails');
        $this->assertSame(1, $response->viewData('pendingInboundEmails'));
    }

    public function test_dashboard_shows_warranty_and_test_tag_expiry_buckets(): void
    {
        $this->travelTo('2026-06-15 12:00:00');

        $user = $this->withDashboardModules($this->scanner(), [
            DashboardModules::WARRANTY_EXPIRY,
            DashboardModules::TEST_TAG_EXPIRY,
        ]);
        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);

        Asset::create([
            'name' => 'Overdue Tag',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'test_tag_expiry' => '2026-06-01',
            'warranty_expiry' => '2026-05-01',
        ]);
        Asset::create([
            'name' => 'Soon Tag',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'test_tag_expiry' => '2026-06-20',
            'warranty_expiry' => '2026-06-25',
        ]);
        Asset::create([
            'name' => 'Mid Tag',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'test_tag_expiry' => '2026-07-20',
            'warranty_expiry' => '2026-07-25',
        ]);
        Asset::create([
            'name' => 'Far Tag',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'test_tag_expiry' => '2026-08-20',
            'warranty_expiry' => '2026-08-25',
        ]);
        Asset::create([
            'name' => 'Beyond Window',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'test_tag_expiry' => '2026-12-01',
            'warranty_expiry' => '2026-12-01',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $testTag = collect($response->viewData('testTagExpiry'))->keyBy('key');
        $this->assertSame(1, $testTag['overdue']['count']);
        $this->assertSame(1, $testTag['30']['count']);
        $this->assertSame(1, $testTag['60']['count']);
        $this->assertSame(1, $testTag['90']['count']);

        $warranty = collect($response->viewData('warrantyExpiry'))->keyBy('key');
        $this->assertSame(1, $warranty['overdue']['count']);
        $this->assertSame(1, $warranty['30']['count']);
        $this->assertSame(1, $warranty['60']['count']);
        $this->assertSame(1, $warranty['90']['count']);

        $response->assertSee(
            route('assets.index', [
                'test_tag_expiry_to' => '2026-06-14',
            ], absolute: false),
            false
        );
        $this->assertSeeHtmlAssetIndexUrl($response, [
            'warranty_expiry_from' => '2026-06-15',
            'warranty_expiry_to' => '2026-07-15',
        ]);
    }

    public function test_assets_index_filters_by_expiry_date_range(): void
    {
        $this->travelTo('2026-06-15 12:00:00');

        $user = $this->scanner();
        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);

        Asset::create([
            'name' => 'In Range',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'test_tag_expiry' => '2026-06-20',
        ]);
        Asset::create([
            'name' => 'Out Of Range',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'test_tag_expiry' => '2026-08-01',
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', [
            'test_tag_expiry_from' => '2026-06-15',
            'test_tag_expiry_to' => '2026-07-15',
        ]));

        $response->assertOk();
        $response->assertSee('In Range');
        $response->assertDontSee('>Out Of Range</a>', false);
    }

    public function test_disabled_expiry_modules_skip_bucket_queries_payload(): void
    {
        $user = $this->scanner();
        $available = DashboardModules::availableFor($user);

        $user->preferences = [
            DashboardModules::PREFERENCE_KEY => [
                'order' => $available,
                'enabled' => [DashboardModules::QUICK_LINKS],
            ],
        ];
        $user->save();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame([], $response->viewData('warrantyExpiry'));
        $this->assertSame([], $response->viewData('testTagExpiry'));
        $this->assertSame([], $response->viewData('maintenanceActivities')['buckets']);
        $this->assertTrue($response->viewData('unrepairedFaults')->isEmpty());
        $this->assertTrue($response->viewData('damagedAssets')->isEmpty());
        $this->assertTrue($response->viewData('activeRepairs')->isEmpty());
        $this->assertSame([], $response->viewData('sparesStats'));
        $response->assertSee('Scan to event');
        $response->assertDontSee('Within 30 days', false);
        $response->assertDontSee('No recent changes.', false);
        $response->assertDontSee('Assets in maintenance', false);
        $response->assertDontSee('Below minimum', false);
    }

    public function test_dashboard_shows_maintenance_activities_module(): void
    {
        $this->travelTo('2026-06-15 12:00:00');

        $user = $this->withDashboardModules($this->scanner(), [
            DashboardModules::MAINTENANCE_ACTIVITIES,
        ]);
        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);

        Asset::create([
            'name' => 'Desk Mic',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Maintenance,
        ]);

        $open = Repair::factory()->create([
            'summary' => 'Open fan repair',
            'status' => RepairStatus::Open,
            'started_at' => now()->subDay(),
        ]);
        Repair::factory()->inProgress()->create([
            'summary' => 'In progress board',
            'started_at' => now()->subHours(2),
        ]);
        Repair::factory()->completed()->create([
            'summary' => 'Recent complete',
            'completed_at' => now()->subDays(5),
        ]);
        Repair::factory()->completed()->create([
            'summary' => 'Old complete',
            'completed_at' => now()->subDays(45),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Assets in maintenance');
        $response->assertSee('Open repairs');
        $response->assertDontSee('Open fan repair', false);
        $response->assertDontSee('In progress board', false);
        $response->assertDontSee('Recent complete', false);

        $buckets = collect($response->viewData('maintenanceActivities')['buckets'])->keyBy('key');
        $this->assertSame(1, $buckets['assets_maintenance']['count']);
        $this->assertSame(1, $buckets['open']['count']);
        $this->assertSame(1, $buckets['in_progress']['count']);
        $this->assertSame(1, $buckets['completed_30']['count']);
        $this->assertArrayNotHasKey('recent', $response->viewData('maintenanceActivities'));

        $response->assertSee(
            route('repairs.index', ['status' => RepairStatus::Open->value], absolute: false),
            false
        );
        $response->assertDontSee(
            route('repairs.show', $open, absolute: false),
            false
        );
        $response->assertSee(route('maintenance.index', absolute: false), false);
    }

    public function test_dashboard_lists_faults_without_an_open_repair_and_damaged_assets(): void
    {
        $user = $this->withDashboardModules($this->scanner(), [
            DashboardModules::MAINTENANCE_ACTIVITIES,
            DashboardModules::UNREPAIRED_FAULTS,
            DashboardModules::DAMAGED_ASSETS,
            DashboardModules::ACTIVE_REPAIRS,
        ]);
        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio-faults']);

        $waiting = Asset::create([
            'name' => 'Waiting fault mic',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);
        AssetMaintenanceLog::factory()->fault()->create([
            'asset_id' => $waiting->id,
            'user_id' => $user->id,
            'summary' => 'Dead channel',
        ]);

        $finished = Asset::create([
            'name' => 'Finished repair mic',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);
        $finishedFault = AssetMaintenanceLog::factory()->fault()->create([
            'asset_id' => $finished->id,
            'user_id' => $user->id,
            'summary' => 'Old crackle',
        ]);
        Repair::factory()->completed()->create([
            'asset_id' => $finished->id,
            'maintenance_log_id' => $finishedFault->id,
            'summary' => 'Closed crackle repair',
        ]);

        $openAsset = Asset::create([
            'name' => 'Open repair mic',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Maintenance,
        ]);
        $openFault = AssetMaintenanceLog::factory()->fault()->create([
            'asset_id' => $openAsset->id,
            'user_id' => $user->id,
            'summary' => 'Open fault',
        ]);
        Repair::factory()->create([
            'asset_id' => $openAsset->id,
            'maintenance_log_id' => $openFault->id,
            'status' => RepairStatus::Open,
            'summary' => 'Open crackle repair',
        ]);

        Asset::create([
            'name' => 'Cracked lens',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Damaged,
        ]);
        Asset::create([
            'name' => 'Healthy desk',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Faults without repair');
        $response->assertSee('Waiting fault mic');
        $response->assertSee('Finished repair mic');
        $response->assertSee('Cracked lens');
        $response->assertDontSee('Healthy desk', false);

        $this->assertSame(
            ['Finished repair mic', 'Waiting fault mic'],
            $response->viewData('unrepairedFaults')->pluck('name')->all(),
        );
        $this->assertSame(['Cracked lens'], $response->viewData('damagedAssets')->pluck('name')->all());
        $this->assertSame(
            ['Open crackle repair'],
            $response->viewData('activeRepairs')->pluck('summary')->all(),
        );

        $buckets = collect($response->viewData('maintenanceActivities')['buckets'])->keyBy('key');
        $this->assertSame(2, $buckets['unrepaired_faults']['count']);
        $this->assertSame(1, $buckets['damaged']['count']);

        $this->actingAs($user)
            ->get(route('assets.index', ['unrepaired_fault' => 1]))
            ->assertOk()
            ->assertSee('Waiting fault mic')
            ->assertSee('Finished repair mic')
            ->assertSee('Showing assets with a fault and no open repair.')
            ->assertDontSee('Open repair mic', false)
            ->assertDontSee('Cracked lens', false);

        $this->actingAs($user)
            ->get(route('assets.index', ['status' => AssetStatus::Damaged->value]))
            ->assertOk()
            ->assertSee('Cracked lens')
            ->assertDontSee('Waiting fault mic', false);
    }

    public function test_dashboard_shows_spares_statistics_module(): void
    {
        $user = $this->withDashboardModules($this->scanner(), [
            DashboardModules::SPARES_STATS,
        ]);

        SparePart::factory()->create([
            'component' => 'Healthy part',
            'quantity_on_hand' => 5,
            'min_holding' => 1,
            'stock_status' => SparePartStockStatus::InStock,
            'severity' => SparePartSeverity::Moderate,
        ]);
        SparePart::factory()->create([
            'component' => 'Low stock part',
            'quantity_on_hand' => 0,
            'min_holding' => 2,
            'stock_status' => SparePartStockStatus::InStock,
            'severity' => SparePartSeverity::Critical,
        ]);
        SparePart::factory()->create([
            'component' => 'Ordered part',
            'quantity_on_hand' => 1,
            'min_holding' => 1,
            'stock_status' => SparePartStockStatus::OnOrder,
            'severity' => SparePartSeverity::Severe,
        ]);
        SparePart::factory()->create([
            'component' => 'Quarantine part',
            'quantity_on_hand' => 3,
            'min_holding' => 1,
            'stock_status' => SparePartStockStatus::Quarantine,
            'severity' => SparePartSeverity::Minor,
        ]);
        SparePart::factory()->create([
            'component' => 'EOL part',
            'quantity_on_hand' => 10,
            'min_holding' => 1,
            'lifecycle_status' => SparePartLifecycleStatus::EndOfLife,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Below minimum');
        $response->assertSee('Units on hand');

        $stats = collect($response->viewData('sparesStats'))->keyBy('key');
        $this->assertSame(4, $stats['active']['count']);
        $this->assertSame(1, $stats['below_min']['count']);
        $this->assertSame(1, $stats['on_order']['count']);
        $this->assertSame(1, $stats['quarantine']['count']);
        $this->assertSame(1, $stats['critical']['count']);
        $this->assertSame(9, $stats['units_on_hand']['count']);

        $response->assertSee(route('spares.index', ['below_min' => 1], absolute: false), false);
        $response->assertSee(
            route('spares.index', ['stock_status' => SparePartStockStatus::OnOrder->value], absolute: false),
            false
        );
        $response->assertSee(
            route('spares.index', ['severity' => SparePartSeverity::Critical->value], absolute: false),
            false
        );
    }

    public function test_scanner_quick_links_include_unknown_scans_and_my_repairs(): void
    {
        $user = $this->withDashboardModules($this->scanner(), [DashboardModules::QUICK_LINKS]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Unknown scans');
        $response->assertSee('My repairs');
        $response->assertSee(route('reports.unknown-scans', absolute: false), false);
        $response->assertSee(route('repairs.index', ['assignee' => 'me'], absolute: false), false);
        $response->assertDontSee('Compare lists', false);
    }

    public function test_manager_quick_links_include_operational_hubs(): void
    {
        $user = $this->withDashboardModules($this->inventoryManager(), [DashboardModules::QUICK_LINKS]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Compare lists');
        $response->assertSee('Import');
        $response->assertSee(route('import.create', absolute: false), false);
        $response->assertSee(route('maintenance.index', absolute: false), false);
    }

    public function test_dashboard_shows_stocktake_pulse_and_data_quality_modules(): void
    {
        $user = $this->withDashboardModules($this->scanner(), [
            DashboardModules::STOCKTAKE_PULSE,
            DashboardModules::DATA_QUALITY,
            DashboardModules::ASSETS_BY_LOCATION,
        ]);

        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        $location = Location::create(['name' => 'Hall A', 'type' => LocationType::Room]);

        Asset::create([
            'name' => 'Unlocated Mic',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);
        Asset::create([
            'name' => 'Hall Mic',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'location_id' => $location->id,
            'tp_barcode' => 'TP-1',
        ]);

        $eventList = AssetList::create([
            'name' => 'Event',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        ScanSession::create([
            'user_id' => $user->id,
            'status' => 'active',
            'event_list_id' => $eventList->id,
            'asset_status' => AssetStatus::OnEvent,
        ]);
        UnknownScan::create([
            'scanned_code' => 'UNKNOWN-1',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Stocktake pulse');
        $response->assertSee('Open unknown scans');
        $response->assertSee('Catalogue completeness');
        $response->assertSee('Missing scan identifiers');
        $response->assertSee('Hall A');
        $response->assertSee(route('assets.index', ['missing_identifiers' => 1], absolute: false), false);
    }

    public function test_dashboard_consumables_module_requires_stock_feature(): void
    {
        $type = ItemType::factory()->create(['supports_stock_tracking' => true]);
        $asset = Asset::factory()->create([
            'item_type_id' => $type->id,
            'quantity' => 1,
            'min_stock_level' => 5,
            'max_stock_level' => 20,
        ]);

        StockMovement::factory()->create([
            'asset_id' => $asset->id,
            'type' => StockMovementType::Restock,
            'quantity_delta' => 5,
            'recorded_at' => now(),
            'external_po_number' => 'PO-123',
        ]);

        $withoutFeature = $this->withDashboardModules($this->scanner(), [
            DashboardModules::CONSUMABLES_STATS,
        ]);

        $this->actingAs($withoutFeature)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Consumables statistics', false);

        $withFeature = $this->withStockFeature($this->withDashboardModules($this->scanner(), [
            DashboardModules::CONSUMABLES_STATS,
        ]));

        $response = $this->actingAs($withFeature)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Consumables statistics');
        $response->assertSee('Below minimum');
        $response->assertSee('PO-123');
        $this->assertSeeHtmlAssetIndexUrl($response, [
            'stock_tracked' => 1,
            'stock_below_min' => 1,
        ]);
    }

    public function test_new_user_gets_planned_default_enabled_modules(): void
    {
        $user = $this->scanner();

        $enabled = collect($this->actingAs($user)->get(route('dashboard'))->viewData('modules'))
            ->filter(fn (array $module): bool => $module['enabled'])
            ->pluck('id')
            ->all();

        $this->assertSame([
            DashboardModules::QUICK_LINKS,
            DashboardModules::STOCKTAKE_PULSE,
            DashboardModules::ASSETS_NEEDING_ATTENTION,
            DashboardModules::MAINTENANCE_ACTIVITIES,
            DashboardModules::UNREPAIRED_FAULTS,
            DashboardModules::DAMAGED_ASSETS,
            DashboardModules::ACTIVE_REPAIRS,
            DashboardModules::SPARES_STATS,
            DashboardModules::ASSETS_BY_STATUS,
            DashboardModules::ASSETS_BY_LOCATION,
            DashboardModules::LATEST_CHANGES,
        ], $enabled);
    }

    public function test_my_repairs_module_lists_assignee_queue(): void
    {
        $user = $this->withDashboardModules($this->scanner(), [DashboardModules::MY_REPAIRS]);

        Repair::factory()->create([
            'summary' => 'Assigned to me',
            'assignee_user_id' => $user->id,
            'status' => RepairStatus::Open,
        ]);
        Repair::factory()->create([
            'summary' => 'Someone else',
            'status' => RepairStatus::Open,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('My repairs');
        $response->assertSee('Assigned to me');
        $response->assertDontSee('Someone else', false);
        $this->assertSame(1, $response->viewData('myRepairs')['count']);
    }

    public function test_spare_movements_recent_module_shows_usage(): void
    {
        $user = $this->withDashboardModules($this->scanner(), [DashboardModules::SPARE_MOVEMENTS_RECENT]);

        SparePartMovement::factory()->create([
            'quantity_delta' => -1,
            'recorded_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Recent spare usage');
        $this->assertSame(1, $response->viewData('spareMovementsRecent')['usage7Days']);
    }

    /**
     * @param  TestResponse<Response>  $response
     * @param  array<string, mixed>  $parameters
     */
    protected function assertSeeHtmlAssetIndexUrl($response, array $parameters): void
    {
        $path = route('assets.index', $parameters, absolute: false);
        $response->assertSee(htmlspecialchars($path, ENT_QUOTES, 'UTF-8'), false);
    }
}

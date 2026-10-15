<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\InboundEmailActionStatus;
use App\Models\Asset;
use App\Models\InboundEmailAction;
use App\Models\ItemType;
use App\Models\User;
use App\Support\DashboardModules;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user->preferences = [
            'features' => [
                'email_integration' => true,
            ],
        ];
        $user->save();

        return $user->fresh();
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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_status_and_type_counts_with_filter_links(): void
    {
        $user = $this->scanner();
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
        $user = $this->admin(emailFeature: true);

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
        $user = $this->inventoryManager(emailFeature: true);

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

        $user = $this->scanner();
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
        $response->assertSee(
            route('assets.index', [
                'warranty_expiry_from' => '2026-06-15',
                'warranty_expiry_to' => '2026-07-15',
            ], absolute: false),
            false
        );
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
        $response->assertDontSee('Out Of Range');
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
        $response->assertSee('Scan to event');
        $response->assertDontSee('Within 30 days', false);
        $response->assertDontSee('No recent changes.', false);
    }
}

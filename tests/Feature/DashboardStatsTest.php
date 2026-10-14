<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_status_and_type_counts_with_filter_links(): void
    {
        $user = User::factory()->create();
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
        $response->assertSee('Overview');
        $response->assertSee('Assets by status');
        $response->assertSee('Assets by type');

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
        $user = User::factory()->create();
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
}

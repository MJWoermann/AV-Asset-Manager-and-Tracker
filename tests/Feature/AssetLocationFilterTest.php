<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetLocationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_assets_index_filters_by_location_including_descendants(): void
    {
        $user = User::factory()->create();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        $site = Location::create([
            'name' => 'Main Warehouse',
            'type' => LocationType::Site,
        ]);
        $room = Location::create([
            'name' => 'Store A',
            'type' => LocationType::Room,
            'parent_id' => $site->id,
        ]);
        $other = Location::create([
            'name' => 'Portable Pool',
            'type' => LocationType::Site,
            'is_portable' => true,
        ]);

        Asset::create([
            'name' => 'In Room Asset',
            'item_type_id' => $itemType->id,
            'location_id' => $room->id,
            'status' => AssetStatus::Available,
        ]);
        Asset::create([
            'name' => 'Other Site Asset',
            'item_type_id' => $itemType->id,
            'location_id' => $other->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', [
            'location_id' => $site->id,
        ]));

        $response->assertOk();
        $response->assertSee('In Room Asset');
        $response->assertDontSee('Other Site Asset');
        $response->assertSee('searchableSelect(', false);
        $response->assertSee('placeholder="Search…"', false);
        $response->assertSee('name="location_id"', false);
        $response->assertSee('id="filter_location_id"', false);
        $response->assertSee('All locations');
        $response->assertSee($site->breadcrumb());
    }

    public function test_assets_index_exact_location_filter_excludes_siblings(): void
    {
        $user = User::factory()->create();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        $site = Location::create([
            'name' => 'Main Warehouse',
            'type' => LocationType::Site,
        ]);
        $roomA = Location::create([
            'name' => 'Store A',
            'type' => LocationType::Room,
            'parent_id' => $site->id,
        ]);
        $roomB = Location::create([
            'name' => 'Store B',
            'type' => LocationType::Room,
            'parent_id' => $site->id,
        ]);

        Asset::create([
            'name' => 'Room A Asset',
            'item_type_id' => $itemType->id,
            'location_id' => $roomA->id,
            'status' => AssetStatus::Available,
        ]);
        Asset::create([
            'name' => 'Room B Asset',
            'item_type_id' => $itemType->id,
            'location_id' => $roomB->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', [
            'location_id' => $roomA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Room A Asset');
        $response->assertDontSee('Room B Asset');
    }
}

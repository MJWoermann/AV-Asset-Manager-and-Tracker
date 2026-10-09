<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_locations_index_nests_children_under_parents_and_links_to_filtered_assets(): void
    {
        $user = User::factory()->create();

        $site = Location::create([
            'name' => 'Main Warehouse',
            'type' => LocationType::Site,
        ]);
        $level = Location::create([
            'name' => 'Ground Floor',
            'type' => LocationType::Level,
            'parent_id' => $site->id,
        ]);
        $room = Location::create([
            'name' => 'Store A',
            'type' => LocationType::Room,
            'parent_id' => $level->id,
        ]);

        $response = $this->actingAs($user)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Main Warehouse',
            'Ground Floor',
            'Store A',
        ]);
        $response->assertSee(route('assets.index', ['location_id' => $site->id]), false);
        $response->assertSee(route('assets.index', ['location_id' => $level->id]), false);
        $response->assertSee(route('assets.index', ['location_id' => $room->id]), false);
        $response->assertDontSee('Parent:');
    }
}

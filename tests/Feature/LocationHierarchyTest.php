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

    public function test_locations_index_alternates_row_shades_for_adjacent_same_type_siblings(): void
    {
        $user = User::factory()->create();

        Location::create(['name' => 'Site Alpha', 'type' => LocationType::Site]);
        Location::create(['name' => 'Site Beta', 'type' => LocationType::Site]);

        $response = $this->actingAs($user)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSee('bg-brand-silver dark:bg-brand-slate border-l-[3px] border-l-brand-charcoal', false);
        $response->assertSee('bg-[#dcdcdc] dark:bg-[#3d4043] border-l-[3px] border-l-brand-charcoal', false);
    }
}

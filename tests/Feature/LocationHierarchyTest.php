<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationHierarchyTest extends TestCase
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

    public function test_locations_index_nests_children_under_parents_and_links_to_filtered_assets(): void
    {
        $user = $this->scanner();

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
        $response->assertDontSee('duplicate name', false);
    }

    public function test_locations_index_sorts_siblings_by_room_code_then_name(): void
    {
        $user = $this->scanner();

        $site = Location::create([
            'name' => 'Campus',
            'room_code' => 'C',
            'type' => LocationType::Site,
        ]);
        Location::create([
            'name' => 'Beta',
            'room_code' => 'B-02',
            'type' => LocationType::Room,
            'parent_id' => $site->id,
        ]);
        Location::create([
            'name' => 'Alpha',
            'room_code' => 'A-01',
            'type' => LocationType::Room,
            'parent_id' => $site->id,
        ]);
        Location::create([
            'name' => 'Zulu',
            'room_code' => 'A-01',
            'type' => LocationType::Room,
            'parent_id' => $site->id,
        ]);

        $response = $this->actingAs($user)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Campus',
            'Alpha',
            'Zulu',
            'Beta',
        ]);
    }

    public function test_locations_index_includes_hierarchy_expand_controls(): void
    {
        $user = $this->scanner();

        $room = Location::create([
            'name' => 'Store A',
            'type' => LocationType::Room,
        ]);
        Location::create([
            'name' => 'Rack 01',
            'type' => LocationType::Rack,
            'parent_id' => $room->id,
        ]);

        $response = $this->actingAs($user)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSee('bulkLocations(', false);
        $response->assertSee('Expand all', false);
        $response->assertSee('Collapse all', false);
        $response->assertSee('Hide racks', false);
        $response->assertSee('hideRacks(', false);
        $response->assertSee('data-location-has-racks="1"', false);
        $response->assertSee('data-location-collapsible="1"', false);
    }

    public function test_locations_index_alternates_row_shades_for_adjacent_same_type_siblings(): void
    {
        $user = $this->scanner();

        Location::create(['name' => 'Site Alpha', 'type' => LocationType::Site]);
        Location::create(['name' => 'Site Beta', 'type' => LocationType::Site]);

        $response = $this->actingAs($user)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSee('bg-brand-silver dark:bg-brand-slate border-l-[3px] border-l-brand-charcoal', false);
        $response->assertSee('bg-[#dcdcdc] dark:bg-[#3d4043] border-l-[3px] border-l-brand-charcoal', false);
    }

    public function test_create_form_uses_searchable_parent_and_excludes_racks(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $room = Location::create(['name' => 'Store A', 'type' => LocationType::Room]);
        $rack = Location::create([
            'name' => 'Rack 01',
            'type' => LocationType::Rack,
            'parent_id' => $room->id,
        ]);

        $response = $this->actingAs($user)->get(route('locations.create'));

        $response->assertOk();
        $response->assertSee('searchableSelect(', false);
        $response->assertSee('Store A', false);
        $response->assertDontSee('Rack 01', false);
        $this->assertTrue(
            $response->viewData('parents')->pluck('id')->contains($room->id)
        );
        $this->assertFalse(
            $response->viewData('parents')->pluck('id')->contains($rack->id)
        );
    }

    public function test_store_rejects_rack_as_parent_but_allows_room_under_room(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $parentRoom = Location::create(['name' => 'Store A', 'type' => LocationType::Room]);
        $rack = Location::create([
            'name' => 'Rack 01',
            'type' => LocationType::Rack,
            'parent_id' => $parentRoom->id,
        ]);

        $this->actingAs($user)->post(route('locations.store'), [
            'name' => 'Store B',
            'type' => LocationType::Room->value,
            'parent_id' => $rack->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('locations', ['name' => 'Store B']);

        $this->actingAs($user)->post(route('locations.store'), [
            'name' => 'Store B',
            'type' => LocationType::Room->value,
            'parent_id' => $parentRoom->id,
        ])->assertRedirect(route('locations.index'));

        $this->assertDatabaseHas('locations', [
            'name' => 'Store B',
            'type' => LocationType::Room->value,
            'parent_id' => $parentRoom->id,
        ]);
    }

    public function test_update_rejects_rack_as_parent(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $room = Location::create(['name' => 'Store A', 'type' => LocationType::Room]);
        $rack = Location::create([
            'name' => 'Rack 01',
            'type' => LocationType::Rack,
            'parent_id' => $room->id,
        ]);
        $child = Location::create(['name' => 'Store B', 'type' => LocationType::Room]);

        $this->actingAs($user)->put(route('locations.update', $child), [
            'name' => 'Store B',
            'type' => LocationType::Room->value,
            'parent_id' => $rack->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($child->fresh()->parent_id);
    }
}

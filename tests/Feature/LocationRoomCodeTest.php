<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationRoomCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    public function test_create_form_shows_room_code_next_to_name(): void
    {
        $user = $this->manager();

        $response = $this->actingAs($user)->get(route('locations.create'));

        $response->assertOk();
        $response->assertSee('name="name"', false);
        $response->assertSee('name="room_code"', false);
        $response->assertSee('Room code', false);
    }

    public function test_store_persists_room_code(): void
    {
        $user = $this->manager();

        $response = $this->actingAs($user)->post(route('locations.store'), [
            'name' => 'Control Room',
            'room_code' => 'CR-12',
            'type' => LocationType::Room->value,
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', [
            'name' => 'Control Room',
            'room_code' => 'CR-12',
        ]);
    }

    public function test_update_persists_and_clears_room_code(): void
    {
        $user = $this->manager();
        $location = Location::create([
            'name' => 'Store A',
            'room_code' => 'SA-01',
            'type' => LocationType::Room,
        ]);

        $this->actingAs($user)->put(route('locations.update', $location), [
            'name' => 'Store A',
            'room_code' => 'SA-02',
            'type' => LocationType::Room->value,
        ])->assertRedirect(route('locations.index'));

        $this->assertSame('SA-02', $location->fresh()->room_code);

        $this->actingAs($user)->put(route('locations.update', $location), [
            'name' => 'Store A',
            'room_code' => '',
            'type' => LocationType::Room->value,
        ])->assertRedirect(route('locations.index'));

        $this->assertNull($location->fresh()->room_code);
    }

    public function test_index_shows_room_code_beside_name(): void
    {
        $user = $this->manager();
        Location::create([
            'name' => 'Foyer',
            'room_code' => 'F-101',
            'type' => LocationType::Room,
        ]);

        $response = $this->actingAs($user)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSee('Foyer', false);
        $response->assertSee('(F-101)', false);
    }
}

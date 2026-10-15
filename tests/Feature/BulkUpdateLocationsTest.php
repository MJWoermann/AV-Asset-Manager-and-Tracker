<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUpdateLocationsTest extends TestCase
{
    use RefreshDatabase;

    protected ItemType $itemType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    protected function makeAsset(array $attributes = []): Asset
    {
        return Asset::create(array_merge([
            'name' => 'Test Asset',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
        ], $attributes));
    }

    public function test_index_shows_duplicate_names_and_bulk_controls_for_managers(): void
    {
        $user = $this->manager();
        Location::create(['name' => 'Warehouse', 'type' => LocationType::Site]);
        Location::create(['name' => 'warehouse', 'type' => LocationType::Site]);

        $response = $this->actingAs($user)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSee('duplicate name', false);
        $response->assertSee('Select duplicates', false);
        $response->assertSee('bulkLocations(', false);
        $response->assertSee('Delete', false);
    }

    public function test_individual_location_can_be_deleted(): void
    {
        $user = $this->manager();
        $location = Location::create(['name' => 'Temp Site', 'type' => LocationType::Site]);
        $asset = $this->makeAsset(['location_id' => $location->id]);

        $response = $this->actingAs($user)->delete(route('locations.destroy', $location));

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertNull($asset->fresh()->location_id);
    }

    public function test_bulk_delete_removes_selected_locations(): void
    {
        $user = $this->manager();
        $a = Location::create(['name' => 'Site A', 'type' => LocationType::Site]);
        $b = Location::create(['name' => 'Site B', 'type' => LocationType::Site]);
        $keep = Location::create(['name' => 'Keep', 'type' => LocationType::Site]);

        $response = $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'delete',
            'confirm_delete' => '1',
            'location_ids' => [$a->id, $b->id],
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('locations', ['id' => $a->id]);
        $this->assertDatabaseMissing('locations', ['id' => $b->id]);
        $this->assertDatabaseHas('locations', ['id' => $keep->id]);
    }

    public function test_bulk_assign_parent_to_selected_locations(): void
    {
        $user = $this->manager();
        $parent = Location::create(['name' => 'Main Site', 'type' => LocationType::Site]);
        $a = Location::create(['name' => 'Room A', 'type' => LocationType::Room]);
        $b = Location::create(['name' => 'Room B', 'type' => LocationType::Room]);

        $response = $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'update',
            'location_ids' => [$a->id, $b->id],
            'update_parent' => '1',
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertSame($parent->id, $a->fresh()->parent_id);
        $this->assertSame($parent->id, $b->fresh()->parent_id);
    }

    public function test_bulk_clear_parent_on_selected_locations(): void
    {
        $user = $this->manager();
        $parent = Location::create(['name' => 'Main Site', 'type' => LocationType::Site]);
        $child = Location::create([
            'name' => 'Room A',
            'type' => LocationType::Room,
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'update',
            'location_ids' => [$child->id],
            'clear_parent' => '1',
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_merge_same_name_locations_reassigns_assets(): void
    {
        $user = $this->manager();
        $keep = Location::create(['name' => 'Store A', 'type' => LocationType::Room]);
        $dup = Location::create(['name' => 'store a', 'type' => LocationType::Room]);
        $other = Location::create(['name' => 'Store B', 'type' => LocationType::Room]);

        $assetOnKeep = $this->makeAsset(['name' => 'On Keep', 'location_id' => $keep->id]);
        $assetOnDup = $this->makeAsset(['name' => 'On Dup', 'location_id' => $dup->id]);

        $response = $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'merge_duplicates',
            'location_ids' => [$keep->id, $dup->id, $other->id],
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('locations', ['id' => $keep->id]);
        $this->assertDatabaseMissing('locations', ['id' => $dup->id]);
        $this->assertDatabaseHas('locations', ['id' => $other->id]);
        $this->assertSame($keep->id, $assetOnKeep->fresh()->location_id);
        $this->assertSame($keep->id, $assetOnDup->fresh()->location_id);
    }

    public function test_merge_moves_child_locations_to_survivor(): void
    {
        $user = $this->manager();
        $keep = Location::create(['name' => 'Warehouse', 'type' => LocationType::Site]);
        $dup = Location::create(['name' => 'Warehouse', 'type' => LocationType::Site]);
        $child = Location::create([
            'name' => 'Bay 1',
            'type' => LocationType::Room,
            'parent_id' => $dup->id,
        ]);

        $response = $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'merge_duplicates',
            'location_ids' => [$keep->id, $dup->id],
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertSame($keep->id, $child->fresh()->parent_id);
        $this->assertDatabaseMissing('locations', ['id' => $dup->id]);
    }

    public function test_merge_prefers_location_with_room_code_and_site(): void
    {
        $user = $this->manager();
        $site = Location::create(['name' => 'Main Site', 'type' => LocationType::Site]);
        $orphan = Location::create([
            'name' => 'Control Room',
            'type' => LocationType::Room,
        ]);
        $preferred = Location::create([
            'name' => 'Control Room',
            'type' => LocationType::Room,
            'room_code' => 'CR-12',
            'parent_id' => $site->id,
        ]);
        $assetOnOrphan = $this->makeAsset(['name' => 'On Orphan', 'location_id' => $orphan->id]);
        $assetOnPreferred = $this->makeAsset(['name' => 'On Preferred', 'location_id' => $preferred->id]);

        $response = $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'merge_duplicates',
            'location_ids' => [$orphan->id, $preferred->id],
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', ['id' => $preferred->id]);
        $this->assertDatabaseMissing('locations', ['id' => $orphan->id]);
        $this->assertSame($preferred->id, $assetOnOrphan->fresh()->location_id);
        $this->assertSame($preferred->id, $assetOnPreferred->fresh()->location_id);
    }

    public function test_merge_without_duplicate_names_fails_validation(): void
    {
        $user = $this->manager();
        $a = Location::create(['name' => 'Alpha', 'type' => LocationType::Site]);
        $b = Location::create(['name' => 'Beta', 'type' => LocationType::Site]);

        $response = $this->actingAs($user)->from(route('locations.index'))->post(route('locations.bulk-update'), [
            'action' => 'merge_duplicates',
            'location_ids' => [$a->id, $b->id],
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHasErrors('location_ids');
        $this->assertDatabaseHas('locations', ['id' => $a->id]);
        $this->assertDatabaseHas('locations', ['id' => $b->id]);
    }

    public function test_scanner_cannot_bulk_update_locations(): void
    {
        $user = $this->scanner();
        $location = Location::create(['name' => 'Site', 'type' => LocationType::Site]);

        $response = $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'delete',
            'confirm_delete' => '1',
            'location_ids' => [$location->id],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_bulk_parent_cannot_be_a_descendant_of_selected_location(): void
    {
        $user = $this->manager();
        $site = Location::create(['name' => 'Site', 'type' => LocationType::Site]);
        $room = Location::create([
            'name' => 'Room',
            'type' => LocationType::Room,
            'parent_id' => $site->id,
        ]);

        $response = $this->actingAs($user)->from(route('locations.index'))->post(route('locations.bulk-update'), [
            'action' => 'update',
            'location_ids' => [$site->id],
            'update_parent' => '1',
            'parent_id' => $room->id,
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($site->fresh()->parent_id);
    }
}

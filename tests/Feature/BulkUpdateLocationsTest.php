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
        $response->assertSee('bulkSelection(', false);
        $response->assertSee('Delete', false);
    }

    public function test_deleting_a_location_moves_assets_to_the_chosen_replacement(): void
    {
        $user = $this->manager();
        $location = Location::create(['name' => 'Temp Site', 'type' => LocationType::Site]);
        $replacement = Location::create(['name' => 'Keep Site', 'type' => LocationType::Site]);
        $child = Location::create([
            'name' => 'Empty Room',
            'type' => LocationType::Room,
            'parent_id' => $location->id,
        ]);
        $asset = $this->makeAsset(['location_id' => $location->id]);

        $response = $this->actingAs($user)->delete(route('locations.destroy', $location), [
            'replacement_location_id' => $replacement->id,
            'deleting_location_id' => $location->id,
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertSame($replacement->id, $asset->fresh()->location_id);
        $this->assertDatabaseHas('locations', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
        $this->assertDatabaseHas('location_aliases', [
            'former_name' => 'Temp Site',
            'type' => LocationType::Site->value,
            'location_id' => $replacement->id,
        ]);
    }

    public function test_location_with_no_assets_stays_on_the_list(): void
    {
        $user = $this->manager();
        $empty = Location::create(['name' => 'Unused Room', 'type' => LocationType::Room]);
        $replacement = Location::create(['name' => 'Keep Site', 'type' => LocationType::Site]);

        $response = $this->actingAs($user)->from(route('locations.index'))->delete(route('locations.destroy', $empty), [
            'replacement_location_id' => $replacement->id,
            'deleting_location_id' => $empty->id,
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHasErrors([
            'location' => 'Locations with no assets stay on the list.',
        ]);
        $this->assertDatabaseHas('locations', ['id' => $empty->id]);
    }

    public function test_location_delete_requires_a_replacement_location(): void
    {
        $user = $this->manager();
        $location = Location::create(['name' => 'Temp Site', 'type' => LocationType::Site]);
        $this->makeAsset(['location_id' => $location->id]);

        $response = $this->actingAs($user)->from(route('locations.index'))->delete(route('locations.destroy', $location), [
            'deleting_location_id' => $location->id,
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHasErrors([
            'replacement_location_id' => 'Choose a replacement location for assets at this location.',
        ]);
        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_location_delete_rejects_the_same_location_as_replacement(): void
    {
        $user = $this->manager();
        $location = Location::create(['name' => 'Temp Site', 'type' => LocationType::Site]);
        $this->makeAsset(['location_id' => $location->id]);

        $response = $this->actingAs($user)->from(route('locations.index'))->delete(route('locations.destroy', $location), [
            'replacement_location_id' => $location->id,
            'deleting_location_id' => $location->id,
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHasErrors([
            'replacement_location_id' => 'Choose a valid replacement location.',
        ]);
        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_location_index_lists_empty_locations_without_a_delete_action(): void
    {
        $user = $this->manager();
        $empty = Location::create([
            'name' => 'Unused Room',
            'room_code' => 'A12',
            'type' => LocationType::Room,
        ]);
        $busy = Location::create(['name' => 'Loaded Site', 'type' => LocationType::Site]);
        $this->makeAsset(['location_id' => $busy->id]);

        $response = $this->actingAs($user)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSee($empty->name, false);
        $response->assertSee('A12', false);
        $response->assertSee('Search by name or room code…', false);
        $response->assertSee('0 assets', false);
        $response->assertSee('Locations with no assets stay on this list.', false);
        $response->assertSee("name: '{$busy->name}'", false);
        $response->assertDontSee("name: '{$empty->name}'", false);
    }

    public function test_bulk_delete_keeps_locations_with_no_assets(): void
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
        $response->assertSessionHas('status', '2 locations with no assets kept.');
        $this->assertDatabaseHas('locations', ['id' => $a->id]);
        $this->assertDatabaseHas('locations', ['id' => $b->id]);
        $this->assertDatabaseHas('locations', ['id' => $keep->id]);
    }

    public function test_bulk_delete_moves_assets_and_keeps_empty_locations(): void
    {
        $user = $this->manager();
        $busy = Location::create(['name' => 'Busy Site', 'type' => LocationType::Site]);
        $empty = Location::create(['name' => 'Empty Room', 'type' => LocationType::Room]);
        $keep = Location::create(['name' => 'Keep', 'type' => LocationType::Site]);
        $asset = $this->makeAsset(['location_id' => $busy->id]);

        $response = $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'delete',
            'confirm_delete' => '1',
            'replacement_location_id' => $keep->id,
            'location_ids' => [$busy->id, $empty->id],
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseMissing('locations', ['id' => $busy->id]);
        $this->assertDatabaseHas('locations', ['id' => $empty->id]);
        $this->assertSame($keep->id, $asset->fresh()->location_id);
    }

    public function test_bulk_delete_of_locations_with_assets_requires_a_replacement(): void
    {
        $user = $this->manager();
        $busy = Location::create(['name' => 'Busy Site', 'type' => LocationType::Site]);
        $this->makeAsset(['location_id' => $busy->id]);

        $response = $this->actingAs($user)->from(route('locations.index'))->post(route('locations.bulk-update'), [
            'action' => 'delete',
            'confirm_delete' => '1',
            'location_ids' => [$busy->id],
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHasErrors([
            'replacement_location_id' => 'Choose a replacement location for assets.',
        ]);
        $this->assertDatabaseHas('locations', ['id' => $busy->id]);
    }

    public function test_bulk_delete_rejects_a_selected_location_as_the_replacement(): void
    {
        $user = $this->manager();
        $busy = Location::create(['name' => 'Busy Site', 'type' => LocationType::Site]);
        $other = Location::create(['name' => 'Other Site', 'type' => LocationType::Site]);
        $this->makeAsset(['location_id' => $busy->id]);
        $this->makeAsset(['location_id' => $other->id]);

        $response = $this->actingAs($user)->from(route('locations.index'))->post(route('locations.bulk-update'), [
            'action' => 'delete',
            'confirm_delete' => '1',
            'replacement_location_id' => $busy->id,
            'location_ids' => [$busy->id, $other->id],
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHasErrors([
            'replacement_location_id' => 'Choose a location that is not being deleted.',
        ]);
        $this->assertDatabaseHas('locations', ['id' => $busy->id]);
        $this->assertDatabaseHas('locations', ['id' => $other->id]);
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

    public function test_bulk_parent_cannot_be_a_rack(): void
    {
        $user = $this->manager();
        $room = Location::create(['name' => 'Store A', 'type' => LocationType::Room]);
        $rack = Location::create([
            'name' => 'Rack 01',
            'type' => LocationType::Rack,
            'parent_id' => $room->id,
        ]);
        $child = Location::create(['name' => 'Store B', 'type' => LocationType::Room]);

        $response = $this->actingAs($user)->from(route('locations.index'))->post(route('locations.bulk-update'), [
            'action' => 'update',
            'location_ids' => [$child->id],
            'update_parent' => '1',
            'parent_id' => $rack->id,
        ]);

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($child->fresh()->parent_id);
    }
}

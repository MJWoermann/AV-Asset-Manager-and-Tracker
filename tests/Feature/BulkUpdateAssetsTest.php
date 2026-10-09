<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUpdateAssetsTest extends TestCase
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

    public function test_bulk_update_changes_status_location_and_test_tag_expiry(): void
    {
        $user = $this->manager();
        $location = Location::create([
            'name' => 'Warehouse A',
            'type' => LocationType::Site,
        ]);
        $a = $this->makeAsset(['name' => 'Asset A']);
        $b = $this->makeAsset(['name' => 'Asset B']);

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'update',
            'asset_ids' => [$a->id, $b->id],
            'update_status' => '1',
            'status' => AssetStatus::OnEvent->value,
            'update_location' => '1',
            'location_id' => $location->id,
            'update_test_tag_expiry' => '1',
            'test_tag_expiry' => '2027-06-15',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertSame(AssetStatus::OnEvent, $a->fresh()->status);
        $this->assertSame(AssetStatus::OnEvent, $b->fresh()->status);
        $this->assertSame($location->id, $a->fresh()->location_id);
        $this->assertSame('2027-06-15', $a->fresh()->test_tag_expiry?->format('Y-m-d'));
    }

    public function test_bulk_update_sets_parent_asset(): void
    {
        $user = $this->manager();
        $parent = $this->makeAsset(['name' => 'Rack 1']);
        $child = $this->makeAsset(['name' => 'Amp 1']);

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'update',
            'asset_ids' => [$child->id],
            'update_parent' => '1',
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect();
        $this->assertSame($parent->id, $child->fresh()->parent_id);
    }

    public function test_bulk_update_adds_assets_to_list(): void
    {
        $user = $this->manager();
        $list = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $a = $this->makeAsset(['name' => 'Asset A']);
        $b = $this->makeAsset(['name' => 'Asset B']);

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'update',
            'asset_ids' => [$a->id, $b->id],
            'add_to_list_id' => $list->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $a->id,
        ]);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $b->id,
        ]);
    }

    public function test_bulk_update_removes_assets_from_current_list(): void
    {
        $user = $this->manager();
        $list = AssetList::create([
            'name' => 'Inventory',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset();
        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'update',
            'asset_ids' => [$asset->id],
            'current_list_id' => $list->id,
            'remove_from_list' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
        ]);
    }

    public function test_bulk_delete_requires_confirmation(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();

        $response = $this->actingAs($user)->from(route('assets.index'))->post(route('assets.bulk-update'), [
            'action' => 'delete',
            'asset_ids' => [$asset->id],
        ]);

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHasErrors('confirm_delete');
        $this->assertModelExists($asset);
    }

    public function test_bulk_delete_removes_assets_after_confirmation(): void
    {
        $user = $this->manager();
        $a = $this->makeAsset(['name' => 'Delete me']);
        $b = $this->makeAsset(['name' => 'Delete me too']);

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'delete',
            'asset_ids' => [$a->id, $b->id],
            'confirm_delete' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('assets', ['id' => $a->id]);
        $this->assertDatabaseMissing('assets', ['id' => $b->id]);
    }

    public function test_scanner_cannot_bulk_delete_assets(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'delete',
            'asset_ids' => [$asset->id],
            'confirm_delete' => '1',
        ]);

        $response->assertForbidden();
        $this->assertModelExists($asset);
    }

    public function test_bulk_update_rejects_empty_field_selection(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();

        $response = $this->actingAs($user)->from(route('assets.index'))->post(route('assets.bulk-update'), [
            'action' => 'update',
            'asset_ids' => [$asset->id],
        ]);

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHasErrors('action');
    }

    public function test_assets_index_renders_bulk_controls(): void
    {
        $user = $this->manager();
        $this->makeAsset();

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('bulkAssets(', false);
        $response->assertSee('Bulk edit');
        $response->assertSee('data-bulk-asset-id', false);
        $response->assertSee('toggleAll(!allPageSelected)', false);
        $response->assertSee('setSelected(', false);
    }

    public function test_list_show_renders_bulk_controls(): void
    {
        $user = $this->manager();
        $list = AssetList::create([
            'name' => 'Stocktake',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset();
        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('lists.show', $list));

        $response->assertOk();
        $response->assertSee('bulkAssets(', false);
        $response->assertSee('Remove from this list');
    }
}

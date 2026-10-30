<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\CheckoutPurpose;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\Department;
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

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

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
        $response->assertSee('bulkSelection(', false);
        $response->assertSee('Bulk edit');
        $response->assertSee('Merge selected');
        $response->assertSee('Delete selected');
        $response->assertDontSee('>Remove from list</button>', false);
        $response->assertSee('data-bulk-asset-id', false);
        $response->assertSee('data-bulk-asset-label', false);
        $response->assertSee('toggleAll($event.target.checked)', false);
        $response->assertSee('setSelected(', false);
    }

    public function test_bulk_merge_moves_list_membership_children_and_fills_blank_barcode(): void
    {
        $user = $this->manager();
        $list = AssetList::create([
            'name' => 'Inventory merge',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $keep = $this->makeAsset(['name' => 'Keep me', 'tp_barcode' => null]);
        $duplicate = $this->makeAsset(['name' => 'Remove me', 'tp_barcode' => 'TP-MERGE-001']);
        $child = $this->makeAsset(['name' => 'Child gear', 'parent_id' => $duplicate->id]);

        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $duplicate->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'merge',
            'asset_ids' => [$keep->id, $duplicate->id],
            'keep_asset_id' => $keep->id,
            'confirm_merge' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('assets', ['id' => $duplicate->id]);
        $this->assertSame('TP-MERGE-001', $keep->fresh()->tp_barcode);
        $this->assertSame($keep->id, $child->fresh()->parent_id);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $keep->id,
        ]);
        $this->assertDatabaseMissing('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $duplicate->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $keep->id,
            'action' => 'merged',
        ]);
    }

    public function test_bulk_merge_requires_confirmation_and_keep_asset_in_selection(): void
    {
        $user = $this->manager();
        $a = $this->makeAsset(['name' => 'A']);
        $b = $this->makeAsset(['name' => 'B']);

        $response = $this->actingAs($user)->from(route('assets.index'))->post(route('assets.bulk-update'), [
            'action' => 'merge',
            'asset_ids' => [$a->id, $b->id],
            'keep_asset_id' => $a->id,
        ]);

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHasErrors('confirm_merge');
        $this->assertModelExists($a);
        $this->assertModelExists($b);

        $other = $this->makeAsset(['name' => 'Outside']);

        $response = $this->actingAs($user)->from(route('assets.index'))->post(route('assets.bulk-update'), [
            'action' => 'merge',
            'asset_ids' => [$a->id, $b->id],
            'keep_asset_id' => $other->id,
            'confirm_merge' => '1',
        ]);

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHasErrors('keep_asset_id');
    }

    public function test_scanner_cannot_bulk_merge_assets(): void
    {
        $user = $this->scanner();
        $a = $this->makeAsset();
        $b = $this->makeAsset();

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'merge',
            'asset_ids' => [$a->id, $b->id],
            'keep_asset_id' => $a->id,
            'confirm_merge' => '1',
        ]);

        $response->assertForbidden();
        $this->assertModelExists($a);
        $this->assertModelExists($b);
    }

    public function test_bulk_merge_rejects_open_checkouts_on_keep_and_duplicate(): void
    {
        $user = $this->manager();
        $keep = $this->makeAsset(['name' => 'Checked out keep']);
        $duplicate = $this->makeAsset(['name' => 'Checked out duplicate']);

        AssetCheckout::query()->create([
            'asset_id' => $keep->id,
            'checked_out_by_user_id' => $user->id,
            'purpose' => CheckoutPurpose::Hire,
            'asset_status_before' => $keep->status->value,
        ]);

        AssetCheckout::query()->create([
            'asset_id' => $duplicate->id,
            'checked_out_by_user_id' => $user->id,
            'purpose' => CheckoutPurpose::Hire,
            'asset_status_before' => $duplicate->status->value,
        ]);

        $response = $this->actingAs($user)->from(route('assets.index'))->post(route('assets.bulk-update'), [
            'action' => 'merge',
            'asset_ids' => [$keep->id, $duplicate->id],
            'keep_asset_id' => $keep->id,
            'confirm_merge' => '1',
        ]);

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHasErrors('asset_ids');
        $this->assertModelExists($duplicate);
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
        $response->assertSee('bulkSelection(', false);
        $response->assertSee('Remove from this list');
        $response->assertSee('>Remove from list</button>', false);
        $response->assertSee('border border-red-700 text-red-700', false);
        $response->assertDontSee('Delete selected');
        $response->assertDontSee('Delete permanently');
    }

    public function test_bulk_delete_is_rejected_when_viewing_a_list(): void
    {
        $user = $this->admin();
        $list = AssetList::create([
            'name' => 'Inventory',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset(['name' => 'Stay in catalogue']);
        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->from(route('lists.show', $list))->post(route('assets.bulk-update'), [
            'action' => 'delete',
            'asset_ids' => [$asset->id],
            'confirm_delete' => '1',
            'current_list_id' => $list->id,
        ]);

        $response->assertRedirect(route('lists.show', $list));
        $response->assertSessionHasErrors([
            'action' => 'Assets can only be permanently deleted from the Assets page.',
        ]);
        $this->assertModelExists($asset);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
        ]);
    }

    public function test_admin_can_bulk_update_department_and_cascades_to_children(): void
    {
        $user = $this->admin();
        $target = Department::factory()->create([
            'name' => 'Lighting',
            'slug' => 'lighting',
            'is_active' => true,
        ]);

        $parent = $this->makeAsset(['name' => 'Rack']);
        $child = $this->makeAsset(['name' => 'Amp', 'parent_id' => $parent->id]);
        $originalDepartmentId = (int) $parent->department_id;

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'update',
            'asset_ids' => [$parent->id],
            'update_department' => '1',
            'department_id' => $target->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertSame($target->id, (int) $parent->fresh()->department_id);
        $this->assertSame($target->id, (int) $child->fresh()->department_id);
        $this->assertSame($parent->id, (int) $child->fresh()->parent_id);
        $this->assertNotSame($originalDepartmentId, $target->id);
    }

    public function test_admin_bulk_department_update_detaches_child_when_parent_not_selected(): void
    {
        $user = $this->admin();
        $target = Department::factory()->create([
            'name' => 'Audio',
            'slug' => 'audio',
            'is_active' => true,
        ]);

        $parent = $this->makeAsset(['name' => 'Parent rack']);
        $child = $this->makeAsset(['name' => 'Child amp', 'parent_id' => $parent->id]);

        $response = $this->actingAs($user)->post(route('assets.bulk-update'), [
            'action' => 'update',
            'asset_ids' => [$child->id],
            'update_department' => '1',
            'department_id' => $target->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertSame($target->id, (int) $child->fresh()->department_id);
        $this->assertNull($child->fresh()->parent_id);
        $this->assertNotSame($target->id, (int) $parent->fresh()->department_id);
    }

    public function test_non_admin_cannot_bulk_update_department(): void
    {
        $user = $this->manager();
        $target = Department::factory()->create([
            'name' => 'Video',
            'slug' => 'video',
            'is_active' => true,
        ]);
        $asset = $this->makeAsset();
        $originalDepartmentId = (int) $asset->department_id;

        $response = $this->actingAs($user)->from(route('assets.index'))->post(route('assets.bulk-update'), [
            'action' => 'update',
            'asset_ids' => [$asset->id],
            'update_department' => '1',
            'department_id' => $target->id,
        ]);

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHasErrors('update_department');
        $this->assertSame($originalDepartmentId, (int) $asset->fresh()->department_id);
    }

    public function test_admin_sees_department_bulk_control_on_assets_index(): void
    {
        $user = $this->admin();
        $this->makeAsset();

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('Update department');
        $response->assertSee('name="update_department"', false);
    }

    public function test_manager_does_not_see_department_bulk_control(): void
    {
        $user = $this->manager();
        $this->makeAsset();

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertDontSee('Update department');
        $response->assertDontSee('name="update_department"', false);
    }
}

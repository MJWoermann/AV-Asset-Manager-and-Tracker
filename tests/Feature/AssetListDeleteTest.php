<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\ScanSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetListDeleteTest extends TestCase
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
            'tp_barcode' => 'TP-LIST-DEL-1',
        ], $attributes));
    }

    public function test_manager_can_delete_list(): void
    {
        $user = $this->manager();
        $list = AssetList::create([
            'name' => 'Disposable List',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('lists.destroy', $list));

        $response->assertRedirect(route('lists.index'));
        $response->assertSessionHas('status', 'List deleted.');
        $this->assertDatabaseMissing('asset_lists', ['id' => $list->id]);
    }

    public function test_scanner_cannot_delete_list(): void
    {
        $user = $this->scanner();
        $list = AssetList::create([
            'name' => 'Protected List',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('lists.destroy', $list));

        $response->assertForbidden();
        $this->assertDatabaseHas('asset_lists', ['id' => $list->id]);
    }

    public function test_deleting_event_list_removes_items_and_sessions_but_keeps_assets(): void
    {
        $user = $this->manager();
        $event = AssetList::create([
            'name' => 'Event To Delete',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset(['name' => 'Kept Asset', 'tp_barcode' => 'TP-KEEP-1']);
        AssetListItem::create([
            'asset_list_id' => $event->id,
            'asset_id' => $asset->id,
        ]);
        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->delete(route('lists.destroy', $event));

        $response->assertRedirect(route('lists.index'));
        $this->assertDatabaseMissing('asset_lists', ['id' => $event->id]);
        $this->assertDatabaseMissing('asset_list_items', ['asset_list_id' => $event->id]);
        $this->assertDatabaseMissing('scan_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }

    public function test_deleting_inventory_list_nulls_session_inventory_reference(): void
    {
        $user = $this->manager();
        $event = AssetList::create([
            'name' => 'Event Keep',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $inventory = AssetList::create([
            'name' => 'Inventory Delete',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'inventory_list_id' => $inventory->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->delete(route('lists.destroy', $inventory));

        $response->assertRedirect(route('lists.index'));
        $this->assertDatabaseMissing('asset_lists', ['id' => $inventory->id]);
        $this->assertDatabaseHas('asset_lists', ['id' => $event->id]);
        $this->assertDatabaseHas('scan_sessions', [
            'id' => $session->id,
            'event_list_id' => $event->id,
            'inventory_list_id' => null,
        ]);
    }

    public function test_edit_and_show_pages_include_delete_control_for_managers(): void
    {
        $user = $this->manager();
        $list = AssetList::create([
            'name' => 'Visible Delete',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $edit = $this->actingAs($user)->get(route('lists.edit', $list));
        $edit->assertOk();
        $edit->assertSee('Delete list', false);
        $edit->assertSee(route('lists.destroy', $list), false);

        $show = $this->actingAs($user)->get(route('lists.show', $list));
        $show->assertOk();
        $show->assertSee('>Delete</button>', false);
        $show->assertSee(route('lists.destroy', $list), false);
    }
}

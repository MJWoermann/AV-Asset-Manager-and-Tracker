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

class ScanSessionTest extends TestCase
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
            'tp_barcode' => 'TP-SCAN-1',
        ], $attributes));
    }

    public function test_scan_page_shows_event_and_inventory_list_dropdowns(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $inventory = AssetList::create([
            'name' => 'Inventory A',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('scan.index'));

        $response->assertOk();
        $response->assertSee('Event list (add items to)');
        $response->assertSee('Inventory list (search against)');
        $response->assertSee($event->name);
        $response->assertSee($inventory->name);
    }

    public function test_starting_session_requires_event_list_type(): void
    {
        $user = $this->scanner();
        $inventory = AssetList::create([
            'name' => 'Inventory Only',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('scan.start'), [
            'event_list_id' => $inventory->id,
            'inventory_list_id' => null,
        ]);

        $response->assertSessionHasErrors('event_list_id');
        $this->assertDatabaseCount('scan_sessions', 0);
    }

    public function test_scan_adds_asset_and_warns_when_missing_from_inventory_list(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $inventory = AssetList::create([
            'name' => 'Inventory A',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset([
            'name' => 'Moving Head',
            'tp_barcode' => 'TP-MH-1',
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'inventory_list_id' => $inventory->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('scan.scan', $session), [
                'code' => 'TP-MH-1',
            ]);

        $response->assertOk();
        $response->assertJsonPath('duplicate', false);
        $response->assertJsonPath('inventory_status', 'not_on_inventory');
        $response->assertJsonPath('asset.name', 'Moving Head');
        $this->assertNotEmpty($response->json('warning'));
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $event->id,
            'asset_id' => $asset->id,
        ]);
    }

    public function test_scan_matches_inventory_list_without_warning(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $inventory = AssetList::create([
            'name' => 'Inventory A',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset([
            'name' => 'Console',
            'tp_barcode' => 'TP-CON-1',
        ]);
        AssetListItem::create([
            'asset_list_id' => $inventory->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'inventory_list_id' => $inventory->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('scan.scan', $session), [
                'code' => 'TP-CON-1',
            ]);

        $response->assertOk();
        $response->assertJsonPath('inventory_status', 'matched');
        $response->assertJsonPath('warning', null);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $event->id,
            'asset_id' => $asset->id,
        ]);
    }

    public function test_duplicate_scan_warns_without_creating_second_item(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset(['tp_barcode' => 'TP-DUP-1']);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        AssetListItem::create([
            'asset_list_id' => $event->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('scan.scan', $session), [
                'code' => 'TP-DUP-1',
            ]);

        $response->assertOk();
        $response->assertJsonPath('duplicate', true);
        $this->assertSame(1, AssetListItem::query()->where('asset_list_id', $event->id)->count());
    }

    public function test_active_session_can_update_event_and_inventory_lists(): void
    {
        $user = $this->scanner();
        $eventA = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $eventB = AssetList::create(['name' => 'Event B', 'type' => 'event', 'created_by' => $user->id]);
        $inventory = AssetList::create(['name' => 'Inventory B', 'type' => 'inventory', 'created_by' => $user->id]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $eventA->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->patch(route('scan.lists', $session), [
            'event_list_id' => $eventB->id,
            'inventory_list_id' => $inventory->id,
        ]);

        $response->assertRedirect(route('scan.index'));
        $session->refresh();
        $this->assertSame($eventB->id, $session->event_list_id);
        $this->assertSame($inventory->id, $session->inventory_list_id);
    }
}

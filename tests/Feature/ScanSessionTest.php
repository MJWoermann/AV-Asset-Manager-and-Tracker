<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\Location;
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
        $response->assertSee('Location for next items');
        $response->assertSee('+ Add new location…');
        $response->assertSee('Status for next items');
        $response->assertSee('On Event');
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

    public function test_scan_applies_session_asset_status_to_scanned_asset(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset([
            'name' => 'Moving Head',
            'tp_barcode' => 'TP-STATUS-1',
            'status' => AssetStatus::Available,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'asset_status' => AssetStatus::OnEvent,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('scan.scan', $session), [
                'code' => 'TP-STATUS-1',
            ]);

        $response->assertOk();
        $response->assertJsonPath('duplicate', false);
        $this->assertSame(AssetStatus::OnEvent, $asset->fresh()->status);
    }

    public function test_duplicate_scan_still_updates_asset_status_from_session(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $asset = $this->makeAsset([
            'name' => 'Console',
            'tp_barcode' => 'TP-STATUS-DUP',
            'status' => AssetStatus::OnEvent,
        ]);

        AssetListItem::create([
            'asset_list_id' => $event->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'asset_status' => AssetStatus::Available,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('scan.scan', $session), [
                'code' => 'TP-STATUS-DUP',
            ]);

        $response->assertOk();
        $response->assertJsonPath('duplicate', true);
        $this->assertStringContainsString('Status updated to Available', $response->json('message'));
        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
        $this->assertSame(1, AssetListItem::query()->where('asset_list_id', $event->id)->count());
    }

    public function test_active_session_can_update_asset_status_for_next_scans(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->patch(route('scan.lists', $session), [
            'event_list_id' => $event->id,
            'asset_status' => AssetStatus::OnEvent->value,
        ]);

        $response->assertRedirect(route('scan.index'));
        $session->refresh();
        $this->assertSame(AssetStatus::OnEvent, $session->asset_status);
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

    public function test_starting_session_can_create_a_new_location(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $parent = Location::create([
            'name' => 'Main Site',
            'type' => LocationType::Site,
        ]);

        $response = $this->actingAs($user)->post(route('scan.start'), [
            'event_list_id' => $event->id,
            'inventory_list_id' => null,
            'location_id' => '__new__',
            'new_location' => [
                'name' => 'Stage Left',
                'type' => LocationType::Room->value,
                'parent_id' => $parent->id,
            ],
        ]);

        $response->assertRedirect(route('scan.index'));
        $this->assertDatabaseHas('locations', [
            'name' => 'Stage Left',
            'type' => LocationType::Room->value,
            'parent_id' => $parent->id,
        ]);

        $location = Location::query()->where('name', 'Stage Left')->first();
        $this->assertNotNull($location);
        $this->assertDatabaseHas('scan_sessions', [
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'location_id' => $location->id,
            'status' => 'active',
        ]);
    }

    public function test_creating_location_from_scan_requires_name(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('scan.start'), [
            'event_list_id' => $event->id,
            'location_id' => '__new__',
            'new_location' => [
                'name' => '',
                'type' => LocationType::Site->value,
            ],
        ]);

        $response->assertSessionHasErrors('new_location.name');
        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('scan_sessions', 0);
    }

    public function test_active_session_can_create_location_when_updating_lists(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->patch(route('scan.lists', $session), [
            'event_list_id' => $event->id,
            'location_id' => '__new__',
            'new_location' => [
                'name' => 'Temp Store',
                'type' => LocationType::Site->value,
            ],
        ]);

        $response->assertRedirect(route('scan.index'));
        $location = Location::query()->where('name', 'Temp Store')->first();
        $this->assertNotNull($location);
        $session->refresh();
        $this->assertSame($location->id, $session->location_id);
    }

    public function test_scan_page_shows_event_list_items_and_session_sections(): void
    {
        $user = $this->scanner();
        $eventA = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $eventB = AssetList::create(['name' => 'Event B', 'type' => 'event', 'created_by' => $user->id]);
        $assetOnA = $this->makeAsset([
            'name' => 'Rack A Fixture',
            'tp_barcode' => 'TP-EVENT-A',
        ]);
        $assetOnB = $this->makeAsset([
            'name' => 'Rack B Fixture',
            'tp_barcode' => 'TP-EVENT-B',
        ]);

        AssetListItem::create([
            'asset_list_id' => $eventA->id,
            'asset_id' => $assetOnA->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);
        AssetListItem::create([
            'asset_list_id' => $eventB->id,
            'asset_id' => $assetOnB->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $eventA->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('scan.index'));

        $response->assertOk();
        $response->assertSee('Added this session');
        $response->assertSee('Reset');
        $response->assertSee('On event list');
        $response->assertSee('Rack A Fixture');
        $response->assertDontSee('Rack B Fixture');
        $response->assertSee('barcodeScanner(', false);
        $response->assertSee((string) $eventA->id);

        $this->actingAs($user)->patch(route('scan.lists', $session), [
            'event_list_id' => $eventB->id,
        ]);

        $switched = $this->actingAs($user)->get(route('scan.index'));
        $switched->assertOk();
        $switched->assertSee('Rack B Fixture');
        $switched->assertDontSee('Rack A Fixture');
        $switched->assertSee((string) $eventB->id);
    }
}

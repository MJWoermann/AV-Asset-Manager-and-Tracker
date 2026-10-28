<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\Department;
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
        $response->assertSee('searchableSelect(', false);
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
        $response->assertSessionHas('scroll_to', 'scan-section');
        $session->refresh();
        $this->assertSame($eventB->id, $session->event_list_id);
        $this->assertSame($inventory->id, $session->inventory_list_id);

        $page = $this->followRedirects($response);
        $page->assertOk();
        $page->assertSee('id="scan-section"', false);
        $page->assertSee('data-scroll-into-view', false);

        $again = $this->actingAs($user)->get(route('scan.index'));
        $again->assertOk();
        $again->assertDontSee('data-scroll-into-view', false);
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
        $response->assertSee('Use camera');
        $response->assertSee('Use hardware scanner');
        $response->assertSee('inputModeStatus', false);
        $response->assertSee('useCamera', false);
        $response->assertSee('useHardware', false);
        $response->assertSee('data-scan-input-mode-status', false);
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

    public function test_scan_assigns_asset_as_child_and_copies_parent_location(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $parentLocation = Location::create(['name' => 'Rack Bay', 'type' => LocationType::Room]);
        $sessionLocation = Location::create(['name' => 'Stage', 'type' => LocationType::Room]);
        $parent = $this->makeAsset([
            'name' => 'Road Case',
            'tp_barcode' => 'TP-PARENT-1',
            'location_id' => $parentLocation->id,
        ]);
        $child = $this->makeAsset([
            'name' => 'Fixture',
            'tp_barcode' => 'TP-CHILD-1',
            'location_id' => $sessionLocation->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'location_id' => $sessionLocation->id,
            'parent_asset_id' => $parent->id,
            'asset_status' => AssetStatus::OnEvent,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(route('scan.scan', $session), [
            'code' => 'TP-CHILD-1',
        ]);

        $response->assertOk();
        $response->assertJsonPath('parent_assignment', 'assigned');
        $this->assertStringContainsString('Assigned Fixture as a child of Road Case.', $response->json('message'));
        $child->refresh();
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame($parentLocation->id, $child->location_id);
        $this->assertSame(AssetStatus::OnEvent, $child->status);
    }

    public function test_scan_reports_when_asset_is_already_a_child_of_the_session_parent(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $parent = $this->makeAsset(['name' => 'Road Case', 'tp_barcode' => 'TP-PARENT-ALREADY']);
        $child = $this->makeAsset([
            'name' => 'Fixture',
            'tp_barcode' => 'TP-CHILD-ALREADY',
            'parent_id' => $parent->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'parent_asset_id' => $parent->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(route('scan.scan', $session), [
            'code' => 'TP-CHILD-ALREADY',
        ]);

        $response->assertOk();
        $response->assertJsonPath('parent_assignment', 'already');
        $this->assertSame($parent->id, $child->fresh()->parent_id);
    }

    public function test_scan_keeps_a_different_parent_until_that_scan_is_confirmed(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $currentParent = $this->makeAsset(['name' => 'Old Case', 'tp_barcode' => 'TP-OLD-CASE']);
        $nextParent = $this->makeAsset(['name' => 'New Case', 'tp_barcode' => 'TP-NEW-CASE']);
        $child = $this->makeAsset([
            'name' => 'Fixture',
            'tp_barcode' => 'TP-MOVE-1',
            'parent_id' => $currentParent->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'parent_asset_id' => $nextParent->id,
            'status' => 'active',
        ]);

        $held = $this->actingAs($user)->postJson(route('scan.scan', $session), [
            'code' => 'TP-MOVE-1',
        ]);

        $held->assertOk();
        $held->assertJsonPath('parent_assignment', 'needs_confirm');
        $held->assertJsonPath('current_parent.name', 'Old Case');
        $held->assertJsonPath('session_parent.name', 'New Case');
        $this->assertSame($currentParent->id, $child->fresh()->parent_id);

        $confirmed = $this->actingAs($user)->postJson(route('scan.scan', $session), [
            'code' => 'TP-MOVE-1',
            'confirm_parent_change' => true,
        ]);

        $confirmed->assertOk();
        $confirmed->assertJsonPath('duplicate', true);
        $confirmed->assertJsonPath('parent_assignment', 'assigned');
        $this->assertSame($nextParent->id, $child->fresh()->parent_id);
    }

    public function test_scan_replaces_existing_parent_when_the_session_option_is_on(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $currentParent = $this->makeAsset(['name' => 'Old Case', 'tp_barcode' => 'TP-OLD-REPLACE']);
        $nextParent = $this->makeAsset(['name' => 'New Case', 'tp_barcode' => 'TP-NEW-REPLACE']);
        $child = $this->makeAsset([
            'name' => 'Fixture',
            'tp_barcode' => 'TP-REPLACE-1',
            'parent_id' => $currentParent->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'parent_asset_id' => $nextParent->id,
            'replace_existing_parent' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(route('scan.scan', $session), [
            'code' => 'TP-REPLACE-1',
        ]);

        $response->assertOk();
        $response->assertJsonPath('parent_assignment', 'assigned');
        $this->assertStringContainsString('Moved Fixture from Old Case to New Case.', $response->json('message'));
        $this->assertSame($nextParent->id, $child->fresh()->parent_id);
    }

    public function test_scan_does_not_parent_an_asset_under_itself_or_create_a_cycle(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $grandparent = $this->makeAsset(['name' => 'Rack', 'tp_barcode' => 'TP-RACK-CYCLE']);
        $middle = $this->makeAsset([
            'name' => 'Case',
            'tp_barcode' => 'TP-CASE-CYCLE',
            'parent_id' => $grandparent->id,
        ]);
        $child = $this->makeAsset([
            'name' => 'Fixture',
            'tp_barcode' => 'TP-FIXTURE-CYCLE',
            'parent_id' => $middle->id,
        ]);

        $selfSession = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'parent_asset_id' => $grandparent->id,
            'status' => 'active',
        ]);

        $self = $this->actingAs($user)->postJson(route('scan.scan', $selfSession), [
            'code' => 'TP-RACK-CYCLE',
        ]);

        $self->assertOk();
        $self->assertJsonPath('parent_assignment', 'rejected');
        $this->assertNull($grandparent->fresh()->parent_id);

        $selfSession->update(['status' => 'closed']);

        $cycleSession = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'parent_asset_id' => $child->id,
            'status' => 'active',
        ]);

        $cycle = $this->actingAs($user)->postJson(route('scan.scan', $cycleSession), [
            'code' => 'TP-RACK-CYCLE',
        ]);

        $cycle->assertOk();
        $cycle->assertJsonPath('parent_assignment', 'rejected');
        $this->assertNull($grandparent->fresh()->parent_id);
        $this->assertSame($middle->id, $child->fresh()->parent_id);
    }

    public function test_duplicate_scan_can_still_assign_the_session_parent(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $parent = $this->makeAsset(['name' => 'Road Case', 'tp_barcode' => 'TP-DUP-PARENT']);
        $child = $this->makeAsset(['name' => 'Fixture', 'tp_barcode' => 'TP-DUP-CHILD']);

        AssetListItem::create([
            'asset_list_id' => $event->id,
            'asset_id' => $child->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'parent_asset_id' => $parent->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(route('scan.scan', $session), [
            'code' => 'TP-DUP-CHILD',
        ]);

        $response->assertOk();
        $response->assertJsonPath('duplicate', true);
        $response->assertJsonPath('parent_assignment', 'assigned');
        $this->assertSame($parent->id, $child->fresh()->parent_id);
    }

    public function test_unknown_scan_create_link_includes_the_session_parent(): void
    {
        $user = $this->scanner();
        $parent = $this->makeAsset(['name' => 'Road Case', 'tp_barcode' => 'TP-UNKNOWN-PARENT']);
        $session = ScanSession::create([
            'user_id' => $user->id,
            'parent_asset_id' => $parent->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(route('scan.scan', $session), [
            'code' => 'TP-NOT-IN-SYSTEM',
        ]);

        $response->assertOk();
        $response->assertJsonPath('unknown', true);
        $this->assertStringContainsString('parent_id='.$parent->id, (string) $response->json('unknown_scan.create_url'));
    }

    public function test_scan_does_not_assign_a_parent_from_another_department(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $parent = $this->makeAsset(['name' => 'Road Case', 'tp_barcode' => 'TP-DEPT-PARENT']);
        $other = Department::factory()->create();
        $child = $this->makeAsset([
            'name' => 'Other Fixture',
            'tp_barcode' => 'TP-DEPT-CHILD',
            'department_id' => $other->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'parent_asset_id' => $parent->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(route('scan.scan', $session), [
            'code' => 'TP-DEPT-CHILD',
        ]);

        $response->assertOk();
        $response->assertJsonPath('parent_assignment', 'rejected');
        $this->assertNull($child->fresh()->parent_id);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $event->id,
            'asset_id' => $child->id,
        ]);
    }

    public function test_asset_page_sets_parent_on_the_active_scan_session(): void
    {
        $user = $this->scanner();
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);
        $parent = $this->makeAsset(['name' => 'Road Case', 'tp_barcode' => 'TP-PREPARE-PARENT']);
        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'replace_existing_parent' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('scan.prepare-parent', $parent));

        $response->assertRedirect(route('scan.index'));
        $session->refresh();
        $this->assertSame($parent->id, $session->parent_asset_id);
        $this->assertTrue($session->replace_existing_parent);

        $page = $this->actingAs($user)->get(route('scan.index'));
        $page->assertOk();
        $page->assertSee('Scanned assets will be assigned as children of');
        $page->assertSee('Road Case');
    }

    public function test_asset_page_prefills_scan_start_when_there_is_no_session(): void
    {
        $user = $this->scanner();
        $parent = $this->makeAsset(['name' => 'Road Case', 'tp_barcode' => 'TP-PREFILL-PARENT']);
        $event = AssetList::create(['name' => 'Event A', 'type' => 'event', 'created_by' => $user->id]);

        $show = $this->actingAs($user)->get(route('assets.show', $parent));
        $show->assertOk();
        $show->assertSee('Scan children');
        $show->assertSee(route('scan.prepare-parent', $parent), false);

        $response = $this->actingAs($user)->post(route('scan.prepare-parent', $parent));

        $response->assertRedirect(route('scan.index', ['parent_asset_id' => $parent->id]));
        $this->assertDatabaseCount('scan_sessions', 0);

        $page = $this->actingAs($user)->get(route('scan.index', ['parent_asset_id' => $parent->id]));
        $page->assertOk();
        $page->assertSee('Assign scanned assets as children of');
        $page->assertSee('Replace existing parent');
        $page->assertSee('Road Case');

        $started = $this->actingAs($user)->post(route('scan.start'), [
            'event_list_id' => $event->id,
            'parent_asset_id' => $parent->id,
            'replace_existing_parent' => '1',
        ]);

        $started->assertRedirect(route('scan.index'));
        $session = ScanSession::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($session);
        $this->assertSame($parent->id, $session->parent_asset_id);
        $this->assertTrue($session->replace_existing_parent);
    }
}

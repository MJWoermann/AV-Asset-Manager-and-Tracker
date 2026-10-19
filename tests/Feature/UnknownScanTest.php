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
use App\Models\UnknownScan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UnknownScanTest extends TestCase
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

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function inventoryManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    public function test_unknown_barcode_is_logged_and_returned_in_scan_response(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $location = Location::create([
            'name' => 'Stage Left',
            'type' => LocationType::Site,
            'is_portable' => false,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'location_id' => $location->id,
            'asset_status' => AssetStatus::OnEvent,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('scan.scan', $session), [
                'code' => 'MISSING-BARCODE-99',
                'quantity' => 2,
            ]);

        $response->assertOk();
        $response->assertJsonPath('unknown', true);
        $response->assertJsonPath('unknown_scan.scanned_code', 'MISSING-BARCODE-99');
        $response->assertJsonPath('message', 'No asset found for "MISSING-BARCODE-99". Logged for follow-up.');

        $this->assertDatabaseHas('unknown_scans', [
            'scanned_code' => 'MISSING-BARCODE-99',
            'user_id' => $user->id,
            'scan_session_id' => $session->id,
            'event_list_id' => $event->id,
            'location_id' => $location->id,
            'quantity' => 2,
            'asset_status' => AssetStatus::OnEvent->value,
        ]);
    }

    public function test_unknown_scan_note_can_be_updated(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        $unknown = UnknownScan::create([
            'scanned_code' => 'NOTE-CODE-1',
            'user_id' => $user->id,
            'scan_session_id' => $session->id,
            'event_list_id' => $event->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('unknown-scans.update', $unknown), [
                'note' => 'Silver road case near FOH',
            ]);

        $response->assertOk();
        $response->assertJsonPath('unknown_scan.note', 'Silver road case near FOH');
        $this->assertSame('Silver road case near FOH', $unknown->fresh()->note);
    }

    public function test_creating_asset_from_unknown_scan_marks_it_resolved(): void
    {
        $user = $this->scanner();
        $unknown = UnknownScan::create([
            'scanned_code' => 'CREATE-ME-1',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Found Fixture',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available->value,
            'tp_barcode' => 'CREATE-ME-1',
            'unknown_scan_id' => $unknown->id,
        ]);

        $asset = Asset::query()->where('tp_barcode', 'CREATE-ME-1')->first();
        $this->assertNotNull($asset);
        $response->assertRedirect(route('assets.show', $asset));

        $unknown->refresh();
        $this->assertTrue($unknown->isResolved());
        $this->assertSame($asset->id, $unknown->resolved_asset_id);
        $this->assertNotNull($unknown->resolved_at);
    }

    public function test_creating_asset_with_return_to_scan_redirects_there(): void
    {
        $user = $this->scanner();
        $unknown = UnknownScan::create([
            'scanned_code' => 'RETURN-SCAN-1',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $returnTo = route('scan.index', absolute: false);

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Returned Fixture',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available->value,
            'tp_barcode' => 'RETURN-SCAN-1',
            'unknown_scan_id' => $unknown->id,
            'return_to' => $returnTo,
        ]);

        $asset = Asset::query()->where('tp_barcode', 'RETURN-SCAN-1')->first();
        $this->assertNotNull($asset);
        $response->assertRedirect($returnTo);
        $response->assertSessionHas('status', 'Asset created.');
        $this->assertTrue($unknown->fresh()->isResolved());
        $this->assertSame($asset->id, $unknown->fresh()->resolved_asset_id);
    }

    public function test_creating_asset_with_return_to_unknown_scans_report_redirects_there(): void
    {
        $user = $this->scanner();
        $unknown = UnknownScan::create([
            'scanned_code' => 'RETURN-REPORT-1',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $returnTo = route('reports.unknown-scans', ['status' => 'open', 'q' => 'RETURN'], absolute: false);

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Report Return Fixture',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available->value,
            'tp_barcode' => 'RETURN-REPORT-1',
            'unknown_scan_id' => $unknown->id,
            'return_to' => $returnTo,
        ]);

        $this->assertNotNull(Asset::query()->where('tp_barcode', 'RETURN-REPORT-1')->first());
        $response->assertRedirect($returnTo);
        $this->assertTrue($unknown->fresh()->isResolved());
    }

    public function test_creating_asset_ignores_malicious_return_to_and_falls_back_to_show(): void
    {
        $user = $this->scanner();

        foreach (['https://evil.example/phish', '//evil.example/phish'] as $index => $malicious) {
            $code = 'RETURN-EVIL-'.$index;
            $unknown = UnknownScan::create([
                'scanned_code' => $code,
                'user_id' => $user->id,
                'quantity' => 1,
            ]);

            $response = $this->actingAs($user)->post(route('assets.store'), [
                'name' => 'Safe Fixture '.$index,
                'item_type_id' => $this->itemType->id,
                'status' => AssetStatus::Available->value,
                'tp_barcode' => $code,
                'unknown_scan_id' => $unknown->id,
                'return_to' => $malicious,
            ]);

            $asset = Asset::query()->where('tp_barcode', $code)->first();
            $this->assertNotNull($asset);
            $response->assertRedirect(route('assets.show', $asset));
            $this->assertTrue($unknown->fresh()->isResolved());
        }
    }

    public function test_create_form_includes_return_to_from_scan_and_cancel_uses_it(): void
    {
        $user = $this->scanner();
        $unknown = UnknownScan::create([
            'scanned_code' => 'FORM-RETURN-1',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);
        $returnTo = route('scan.index', absolute: false);

        $this->actingAs($user)
            ->get(route('assets.create', [
                'tp_barcode' => $unknown->scanned_code,
                'unknown_scan_id' => $unknown->id,
                'return_to' => $returnTo,
            ]))
            ->assertOk()
            ->assertSee('name="return_to"', false)
            ->assertSee('value="'.$returnTo.'"', false)
            ->assertSee('href="'.$returnTo.'"', false);
    }

    public function test_unknown_scans_report_lists_open_entries_and_exports(): void
    {
        $user = $this->scanner();
        UnknownScan::create([
            'scanned_code' => 'REPORT-OPEN-1',
            'user_id' => $user->id,
            'quantity' => 1,
            'note' => 'Behind amp rack',
        ]);

        $page = $this->actingAs($user)->get(route('reports.unknown-scans'));
        $page->assertOk();
        $page->assertSee('Unknown barcode scans');
        $page->assertSee('REPORT-OPEN-1');
        $page->assertSee('Behind amp rack');

        $export = $this->actingAs($user)->get(route('reports.unknown-scans.export', [
            'format' => 'csv',
            'status' => 'open',
        ]));
        $export->assertOk();
        $export->assertHeader('content-disposition');
    }

    public function test_scan_page_includes_unknown_scans_for_active_session(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        UnknownScan::create([
            'scanned_code' => 'SESSION-UNKNOWN-1',
            'user_id' => $user->id,
            'scan_session_id' => $session->id,
            'event_list_id' => $event->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('scan.index'));
        $response->assertOk();
        $response->assertSee('SESSION-UNKNOWN-1');
        $response->assertSee('Unknown scan report');
        $response->assertSee('Create asset', false);
        $response->assertSee('Link item', false);
        $response->assertSee('Link to existing asset');
        $response->assertSee('openLink', false);
        $response->assertSee('Write scanned code to');
    }

    public function test_admin_can_clear_all_unknown_scans(): void
    {
        $admin = $this->admin();
        UnknownScan::create([
            'scanned_code' => 'CLEAR-1',
            'user_id' => $admin->id,
            'quantity' => 1,
        ]);
        UnknownScan::create([
            'scanned_code' => 'CLEAR-2',
            'user_id' => $admin->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($admin)->delete(route('reports.unknown-scans.destroy-all'));

        $response->assertRedirect(route('reports.unknown-scans'));
        $response->assertSessionHas('status', 'Cleared 2 unknown scans.');
        $this->assertDatabaseCount('unknown_scans', 0);
    }

    public function test_admin_can_remove_duplicate_unknown_scans_keeping_latest(): void
    {
        $admin = $this->admin();

        $olderDup = UnknownScan::create([
            'scanned_code' => 'DUP-CODE',
            'user_id' => $admin->id,
            'quantity' => 1,
            'note' => 'older',
        ]);
        $latestDup = UnknownScan::create([
            'scanned_code' => 'DUP-CODE',
            'user_id' => $admin->id,
            'quantity' => 2,
            'note' => 'latest',
        ]);
        $unique = UnknownScan::create([
            'scanned_code' => 'UNIQUE-CODE',
            'user_id' => $admin->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('reports.unknown-scans.deduplicate'));

        $response->assertRedirect(route('reports.unknown-scans'));
        $response->assertSessionHas('status', 'Removed 1 duplicate unknown scan.');
        $this->assertDatabaseMissing('unknown_scans', ['id' => $olderDup->id]);
        $this->assertDatabaseHas('unknown_scans', [
            'id' => $latestDup->id,
            'note' => 'latest',
        ]);
        $this->assertDatabaseHas('unknown_scans', ['id' => $unique->id]);
        $this->assertDatabaseCount('unknown_scans', 2);
    }

    public function test_non_admin_cannot_clear_or_deduplicate_unknown_scans(): void
    {
        $manager = $this->inventoryManager();
        UnknownScan::create([
            'scanned_code' => 'KEEP-ME',
            'user_id' => $manager->id,
            'quantity' => 1,
        ]);

        $this->actingAs($manager)
            ->delete(route('reports.unknown-scans.destroy-all'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('reports.unknown-scans.deduplicate'))
            ->assertForbidden();

        $this->assertDatabaseCount('unknown_scans', 1);
    }

    public function test_admin_sees_maintenance_actions_on_unknown_scans_report(): void
    {
        $admin = $this->admin();
        $scanner = $this->scanner();

        $this->actingAs($admin)
            ->get(route('reports.unknown-scans'))
            ->assertOk()
            ->assertSee('Remove duplicates')
            ->assertSee('Clear all');

        $this->actingAs($scanner)
            ->get(route('reports.unknown-scans'))
            ->assertOk()
            ->assertDontSee('Remove duplicates')
            ->assertDontSee('Clear all');
    }

    public function test_unknown_scans_report_offers_link_existing_for_open_rows(): void
    {
        $user = $this->scanner();
        UnknownScan::create([
            'scanned_code' => 'LINK-UI-1',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.unknown-scans'))
            ->assertOk()
            ->assertSee('Add asset')
            ->assertSee('Link existing')
            ->assertSee('Link to existing asset')
            ->assertSee('Write scanned code to')
            ->assertSee('TP Barcode')
            ->assertSee('Serial Number')
            ->assertSee('FMI AST#');
    }

    public function test_linking_unknown_scan_to_existing_asset_marks_it_resolved(): void
    {
        $user = $this->scanner();
        $asset = Asset::factory()->create([
            'name' => 'Existing Fixture',
            'item_type_id' => $this->itemType->id,
            'tp_barcode' => 'ALREADY-SET',
        ]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'LINK-ME-1',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('unknown-scans.link', $unknown), [
            'asset_id' => $asset->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Unknown scan linked to Existing Fixture.');

        $unknown->refresh();
        $this->assertTrue($unknown->isResolved());
        $this->assertSame($asset->id, $unknown->resolved_asset_id);
        $this->assertNotNull($unknown->resolved_at);
        $this->assertSame('ALREADY-SET', $asset->fresh()->tp_barcode);
    }

    public function test_linking_can_add_asset_to_event_list_without_duplicating(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event Link',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
        ]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'LINK-EVENT-1',
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user)->post(route('unknown-scans.link', $unknown), [
            'asset_id' => $asset->id,
            'add_to_event_list' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $event->id,
            'asset_id' => $asset->id,
            'quantity' => 3,
            'added_by' => $user->id,
        ]);
        $this->assertSame(1, AssetListItem::query()->where('asset_list_id', $event->id)->where('asset_id', $asset->id)->count());

        $unknown2 = UnknownScan::create([
            'scanned_code' => 'LINK-EVENT-2',
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('unknown-scans.link', $unknown2), [
            'asset_id' => $asset->id,
            'add_to_event_list' => true,
        ])->assertRedirect();

        $this->assertSame(1, AssetListItem::query()->where('asset_list_id', $event->id)->where('asset_id', $asset->id)->count());
    }

    public function test_linking_can_set_tp_barcode_when_empty_and_not_colliding(): void
    {
        $user = $this->scanner();
        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'tp_barcode' => null,
        ]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'NEW-BARCODE-9',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('unknown-scans.link', $unknown), [
            'asset_id' => $asset->id,
            'set_identifier' => true,
        ])->assertRedirect();

        $this->assertSame('NEW-BARCODE-9', $asset->fresh()->tp_barcode);
    }

    public function test_linking_can_write_chosen_identifier_field_and_overwrite(): void
    {
        $user = $this->scanner();
        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'serial_number' => 'OLD-SERIAL',
        ]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'NEW-SERIAL-9',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('unknown-scans.link', $unknown), [
            'asset_id' => $asset->id,
            'identifier_field' => 'serial_number',
        ])->assertRedirect();

        $this->assertSame('NEW-SERIAL-9', $asset->fresh()->serial_number);
        $this->assertTrue($unknown->fresh()->isResolved());
    }

    public function test_linking_rejects_identifier_when_another_asset_already_uses_code(): void
    {
        $user = $this->scanner();
        Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'tp_barcode' => 'TAKEN-CODE',
        ]);
        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'tp_barcode' => null,
        ]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'TAKEN-CODE',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->from(route('reports.unknown-scans'))
            ->post(route('unknown-scans.link', $unknown), [
                'asset_id' => $asset->id,
                'identifier_field' => 'tp_barcode',
            ])
            ->assertRedirect(route('reports.unknown-scans'))
            ->assertSessionHasErrors('identifier_field');

        $this->assertFalse($unknown->fresh()->isResolved());
        $this->assertNull($asset->fresh()->tp_barcode);
    }

    public function test_linking_applies_scan_location_to_asset_without_parent(): void
    {
        $user = $this->scanner();
        $scanLocation = Location::create([
            'name' => 'Scan Bay',
            'type' => LocationType::Room,
        ]);
        $oldLocation = Location::create([
            'name' => 'Old Shelf',
            'type' => LocationType::Room,
        ]);
        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'location_id' => $oldLocation->id,
            'parent_id' => null,
        ]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'LOC-LINK-1',
            'user_id' => $user->id,
            'location_id' => $scanLocation->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('unknown-scans.link', $unknown), [
            'asset_id' => $asset->id,
        ])->assertRedirect();

        $this->assertSame($scanLocation->id, $asset->fresh()->location_id);
    }

    public function test_linking_does_not_override_child_asset_location(): void
    {
        $user = $this->scanner();
        $scanLocation = Location::create([
            'name' => 'Scan Bay 2',
            'type' => LocationType::Room,
        ]);
        $parentLocation = Location::create([
            'name' => 'Parent Bay',
            'type' => LocationType::Room,
        ]);
        $parent = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'location_id' => $parentLocation->id,
        ]);
        $child = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'parent_id' => $parent->id,
            'location_id' => $parentLocation->id,
        ]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'LOC-CHILD-1',
            'user_id' => $user->id,
            'location_id' => $scanLocation->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('unknown-scans.link', $unknown), [
            'asset_id' => $child->id,
        ])->assertRedirect();

        $this->assertSame($parentLocation->id, $child->fresh()->location_id);
        $this->assertTrue($unknown->fresh()->isResolved());
    }

    public function test_cannot_link_already_resolved_unknown_scan(): void
    {
        $user = $this->scanner();
        $resolvedAsset = Asset::factory()->create(['item_type_id' => $this->itemType->id]);
        $otherAsset = Asset::factory()->create(['item_type_id' => $this->itemType->id]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'ALREADY-DONE',
            'user_id' => $user->id,
            'quantity' => 1,
            'resolved_asset_id' => $resolvedAsset->id,
            'resolved_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('reports.unknown-scans'))
            ->post(route('unknown-scans.link', $unknown), [
                'asset_id' => $otherAsset->id,
            ])
            ->assertRedirect(route('reports.unknown-scans'))
            ->assertSessionHasErrors('asset_id');

        $this->assertSame($resolvedAsset->id, $unknown->fresh()->resolved_asset_id);
    }

    public function test_link_unknown_scan_requires_existing_asset_id(): void
    {
        $user = $this->scanner();
        $unknown = UnknownScan::create([
            'scanned_code' => 'NEED-ASSET',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->from(route('reports.unknown-scans'))
            ->post(route('unknown-scans.link', $unknown), [])
            ->assertRedirect(route('reports.unknown-scans'))
            ->assertSessionHasErrors('asset_id');

        $this->actingAs($user)
            ->from(route('reports.unknown-scans'))
            ->post(route('unknown-scans.link', $unknown), ['asset_id' => 999999])
            ->assertRedirect(route('reports.unknown-scans'))
            ->assertSessionHasErrors('asset_id');

        $this->assertFalse($unknown->fresh()->isResolved());
    }

    public function test_guest_cannot_link_unknown_scan(): void
    {
        $user = $this->scanner();
        $unknown = UnknownScan::create([
            'scanned_code' => 'GUEST-BLOCK',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);
        $asset = Asset::factory()->create(['item_type_id' => $this->itemType->id]);

        $this->post(route('unknown-scans.link', $unknown), [
            'asset_id' => $asset->id,
        ])->assertRedirect(route('login'));

        $this->assertFalse($unknown->fresh()->isResolved());
    }

    public function test_api_can_link_unknown_scan_to_existing_asset(): void
    {
        $user = $this->scanner();
        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'tp_barcode' => null,
        ]);
        $unknown = UnknownScan::create([
            'scanned_code' => 'API-LINK-1',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $this->ensureDepartmentContext($user);
        Sanctum::actingAs($user, ['reports:write']);

        $this->postJson('/api/unknown-scans/'.$unknown->id.'/link', [
            'asset_id' => $asset->id,
            'set_identifier' => true,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Unknown scan linked to existing asset.');

        $this->assertTrue($unknown->fresh()->isResolved());
        $this->assertSame('API-LINK-1', $asset->fresh()->tp_barcode);
    }

    public function test_asset_search_endpoint_returns_matching_assets(): void
    {
        $user = $this->scanner();
        Asset::factory()->create([
            'name' => 'Searchable Roadcase',
            'item_type_id' => $this->itemType->id,
            'tp_barcode' => 'SRC-999',
        ]);

        $this->actingAs($user)
            ->getJson(route('assets.search', ['q' => 'SRC-999']))
            ->assertOk()
            ->assertJsonFragment(['tp_barcode' => 'SRC-999'])
            ->assertJsonFragment(['name' => 'Searchable Roadcase']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\ScanSession;
use App\Models\UnknownScan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

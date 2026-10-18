<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ComparisonReportPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_authenticated_user_can_download_comparison_report_as_pdf(): void
    {
        $user = $this->scanner();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        $matched = Asset::create([
            'name' => 'Matched Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-MATCH-1',
        ]);
        $inventoryOnly = Asset::create([
            'name' => 'Inventory Only Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-INV-1',
        ]);
        $scannedOnly = Asset::create([
            'name' => 'Scanned Only Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-SCAN-1',
        ]);

        $scannedList = AssetList::create([
            'name' => 'Event Stocktake',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        $inventoryList = AssetList::create([
            'name' => 'Warehouse Inventory',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        AssetListItem::create([
            'asset_list_id' => $scannedList->id,
            'asset_id' => $matched->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);
        AssetListItem::create([
            'asset_list_id' => $scannedList->id,
            'asset_id' => $scannedOnly->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);
        AssetListItem::create([
            'asset_list_id' => $inventoryList->id,
            'asset_id' => $matched->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);
        AssetListItem::create([
            'asset_list_id' => $inventoryList->id,
            'asset_id' => $inventoryOnly->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        Carbon::setTestNow('2026-10-14 12:00:00');

        $response = $this->actingAs($user)->post(route('reports.compare.run'), [
            'scanned_list_id' => $scannedList->id,
            'inventory_list_id' => $inventoryList->id,
            'export' => 'pdf',
        ]);

        $filename = 'comparison-'.Str::slug($scannedList->name).'-vs-'.Str::slug($inventoryList->name).'-20261014.pdf';

        $response->assertOk();
        $response->assertDownload($filename);
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_compare_form_offers_pdf_export(): void
    {
        $user = $this->scanner();

        $response = $this->actingAs($user)->get(route('reports.compare'));

        $response->assertOk();
        $response->assertSee('Export', false);
        $response->assertSee('PDF', false);
    }

    public function test_compare_form_list_options_include_item_count_and_last_updated(): void
    {
        $user = $this->scanner();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        $asset = Asset::create([
            'name' => 'Fixture One',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-OPT-1',
        ]);

        Carbon::setTestNow('2026-10-15 09:30:00');

        $list = AssetList::create([
            'name' => 'Event Stocktake',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        Carbon::setTestNow('2026-10-15 14:45:00');

        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.compare'));

        $response->assertOk();
        $response->assertSee('searchableSelect(', false);
        $response->assertSee('Event Stocktake (event) — 1 item, updated 2026-10-15 14:45', false);
    }

    public function test_guest_cannot_download_comparison_report_as_pdf(): void
    {
        $response = $this->post(route('reports.compare.run'), [
            'scanned_list_id' => 1,
            'inventory_list_id' => 2,
            'export' => 'pdf',
        ]);

        $response->assertRedirect(route('login'));
    }
}

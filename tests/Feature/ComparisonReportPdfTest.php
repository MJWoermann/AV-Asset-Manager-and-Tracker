<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparisonReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_download_comparison_report_as_pdf(): void
    {
        $user = User::factory()->create();
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

        $response = $this->actingAs($user)->post(route('reports.compare.run'), [
            'scanned_list_id' => $scannedList->id,
            'inventory_list_id' => $inventoryList->id,
            'export' => 'pdf',
        ]);

        $response->assertOk();
        $response->assertDownload('comparison-'.$scannedList->id.'-vs-'.$inventoryList->id.'.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_compare_form_offers_pdf_export(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.compare'));

        $response->assertOk();
        $response->assertSee('Export PDF', false);
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

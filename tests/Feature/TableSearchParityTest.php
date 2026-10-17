<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use App\Enums\RepairStatus;
use App\Models\Asset;
use App\Models\File;
use App\Models\ItemType;
use App\Models\Manufacturer;
use App\Models\Repair;
use App\Models\SparePart;
use App\Models\UnknownScan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableSearchParityTest extends TestCase
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

    public function test_asset_column_filters_offer_scan_on_all_identifier_fields(): void
    {
        $user = $this->scanner();
        $user->preferences = [
            'asset_table_columns' => [
                'name',
                'fmi_ast',
                'tp_barcode',
                'rig_tag',
                'serial_number',
                'ip_address',
                'mac_address',
            ],
        ];
        $user->save();

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        foreach (['fmi_ast', 'tp_barcode', 'rig_tag', 'serial_number', 'ip_address', 'mac_address'] as $column) {
            $response->assertSee("fieldBarcodeScanner('filter-{$column}'", false);
        }
    }

    public function test_asset_column_filter_supports_multi_token_partial_match(): void
    {
        $user = $this->scanner();

        Asset::create([
            'name' => 'Alpha Fixture',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-ALPHA-9001',
        ]);
        Asset::create([
            'name' => 'Beta Fixture',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-ALPHA-ONLY',
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', [
            'tp_barcode' => 'ALPHA 9001',
        ]));

        $response->assertOk();
        $this->assertSame(
            ['Alpha Fixture'],
            $response->viewData('assets')->pluck('name')->all()
        );
    }

    public function test_repairs_search_matches_across_summary_and_asset_fields(): void
    {
        $user = $this->manager();
        $yamaha = Manufacturer::factory()->create(['name' => 'Yamaha']);

        $matchAsset = Asset::create([
            'name' => 'FOH Console',
            'item_type_id' => $this->itemType->id,
            'manufacturer_id' => $yamaha->id,
            'status' => AssetStatus::Available,
            'serial_number' => 'SN-9001',
        ]);
        $otherAsset = Asset::create([
            'name' => 'Yamaha Speaker',
            'item_type_id' => $this->itemType->id,
            'manufacturer_id' => $yamaha->id,
            'status' => AssetStatus::Available,
        ]);

        $match = Repair::factory()->create([
            'asset_id' => $matchAsset->id,
            'summary' => 'Power supply fault',
            'status' => RepairStatus::Open,
        ]);
        Repair::factory()->create([
            'asset_id' => $otherAsset->id,
            'summary' => 'Yamaha panel crack',
            'status' => RepairStatus::Open,
        ]);

        $response = $this->actingAs($user)->get(route('repairs.index', [
            'status' => 'all',
            'q' => 'Yamaha 9001',
        ]));

        $response->assertOk();
        $this->assertSame(
            [$match->id],
            $response->viewData('repairs')->pluck('id')->all()
        );
        $response->assertSee("fieldBarcodeScanner('repairs-search-q'", false);
    }

    public function test_spares_search_is_multi_token_and_scannable(): void
    {
        $user = $this->scanner();

        $match = SparePart::factory()->create([
            'code' => 'SP-9001',
            'component' => 'Latch kit',
            'product_code' => 'LK-AA',
        ]);
        SparePart::factory()->create([
            'code' => 'SP-1111',
            'component' => 'Latch cover',
            'product_code' => 'LC-BB',
        ]);

        $response = $this->actingAs($user)->get(route('spares.index', [
            'q' => 'Latch 9001',
        ]));

        $response->assertOk();
        $response->assertSee($match->code);
        $response->assertDontSee('SP-1111');
        $response->assertSee("fieldBarcodeScanner('spares-search-q'", false);
    }

    public function test_unknown_scans_search_supports_multi_token_across_fields(): void
    {
        $user = $this->scanner();

        UnknownScan::create([
            'scanned_code' => 'UNK-9001',
            'user_id' => $user->id,
            'note' => 'Found near FOH',
        ]);
        UnknownScan::create([
            'scanned_code' => 'UNK-1111',
            'user_id' => $user->id,
            'note' => 'FOH balcony',
        ]);

        $response = $this->actingAs($user)->get(route('reports.unknown-scans', [
            'status' => 'open',
            'q' => 'FOH 9001',
        ]));

        $response->assertOk();
        $response->assertSee('UNK-9001');
        $response->assertDontSee('UNK-1111');
    }

    public function test_files_search_supports_multi_token_across_title_and_filename(): void
    {
        $user = $this->manager();

        $match = File::factory()->create([
            'title' => 'Console wiring diagram',
            'original_filename' => 'yamaha-foh.pdf',
            'category' => FileCategory::Manual,
            'source_type' => FileSourceType::Upload,
        ]);
        File::factory()->create([
            'title' => 'Yamaha brochure',
            'original_filename' => 'brochure.pdf',
            'category' => FileCategory::Manual,
            'source_type' => FileSourceType::Upload,
        ]);

        $response = $this->actingAs($user)->get(route('files.index', [
            'q' => 'yamaha diagram',
        ]));

        $response->assertOk();
        $response->assertSee($match->title);
        $response->assertDontSee('Yamaha brochure');
    }
}

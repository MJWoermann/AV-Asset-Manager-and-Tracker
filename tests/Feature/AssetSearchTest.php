<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\User;
use App\Support\AssetSearch;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSearchTest extends TestCase
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

    public function test_assets_index_offers_camera_scan_on_search(): void
    {
        $user = $this->scanner();

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee("fieldBarcodeScanner('asset-search-q'", false);
        $response->assertSee('id="asset-search-q"', false);
        $response->assertSee('id="field-qr-asset-search-q"', false);
        $response->assertSee('submitOnScan: true', false);
    }

    public function test_search_matches_partial_identifier_fields(): void
    {
        $user = $this->scanner();
        $itemType = $this->itemType();

        Asset::create([
            'name' => 'Partial Barcode Asset',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-ZX9-4411',
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'ZX9']));

        $response->assertOk();
        $response->assertSee('Partial Barcode Asset');
        $response->assertSee('<mark class="bg-brand/30 text-inherit rounded-sm px-0.5">ZX9</mark>', false);
    }

    public function test_search_matches_asset_id_number(): void
    {
        $user = $this->scanner();
        $itemType = $this->itemType();

        $asset = Asset::create([
            'name' => 'ID Lookup Asset',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        Asset::create([
            'name' => 'Unrelated Asset',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $found = Asset::query()->search((string) $asset->id)->pluck('name')->all();
        $this->assertContains('ID Lookup Asset', $found);

        $foundByHash = Asset::query()->search('#'.$asset->id)->pluck('name')->all();
        $this->assertContains('ID Lookup Asset', $foundByHash);

        $this->actingAs($user)
            ->getJson(route('assets.search', ['q' => '#'.$asset->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => $asset->id])
            ->assertJsonFragment(['name' => 'ID Lookup Asset']);
    }

    public function test_parent_search_endpoint_limits_to_top_level_and_excludes_self(): void
    {
        $user = $this->scanner();
        $itemType = $this->itemType();

        $parent = Asset::create([
            'name' => 'Top Level Parent',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TOP-PARENT-1',
        ]);
        $child = Asset::create([
            'name' => 'Nested Child',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'parent_id' => $parent->id,
            'tp_barcode' => 'NESTED-CHILD-1',
        ]);
        $other = Asset::create([
            'name' => 'Another Top',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TOP-OTHER-2',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('assets.search', [
                'q' => 'TOP',
                'parents_only' => 1,
                'exclude_id' => $other->id,
            ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($parent->id, $ids);
        $this->assertNotContains($child->id, $ids);
        $this->assertNotContains($other->id, $ids);
        $response->assertJsonFragment(['label' => AssetSearch::optionLabel($parent->fresh())]);
    }

    public function test_exact_identifier_match_ranks_above_partial_name_match(): void
    {
        $user = $this->scanner();
        $itemType = $this->itemType();

        $exact = Asset::create([
            'name' => 'Other Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'MATCHME',
        ]);

        $partial = Asset::create([
            'name' => 'MATCHME wash light',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'OTHER-1',
        ]);

        $orderedIds = Asset::query()
            ->search('MATCHME')
            ->orderBySearchRelevance('MATCHME')
            ->pluck('id')
            ->all();

        $this->assertSame([$exact->id, $partial->id], $orderedIds);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'MATCHME']));

        $response->assertOk();
        $response->assertSeeInOrder(['Other Fixture', 'MATCHME wash light']);
    }

    public function test_multi_token_search_matches_across_different_fields(): void
    {
        $user = $this->scanner();
        $itemType = $this->itemType();

        $yamaha = Manufacturer::factory()->create(['name' => 'Yamaha']);

        Asset::create([
            'name' => 'FOH Console',
            'manufacturer_id' => $yamaha->id,
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'serial_number' => 'SN-9001',
        ]);

        Asset::create([
            'name' => 'Yamaha Speaker',
            'manufacturer_id' => $yamaha->id,
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'Yamaha 9001']));

        $response->assertOk();
        $response->assertSee('FOH Console');
        $this->assertSame(
            ['FOH Console'],
            $response->viewData('assets')->pluck('name')->all()
        );
    }

    public function test_multi_token_search_matches_location_and_name_parts(): void
    {
        $user = $this->scanner();
        $itemType = $this->itemType();
        $location = Location::create([
            'name' => 'Bealey 3',
            'type' => LocationType::Room,
        ]);

        Asset::create([
            'name' => 'NVX352-A1-1-FLOATING',
            'item_type_id' => $itemType->id,
            'location_id' => $location->id,
            'status' => AssetStatus::Available,
            'serial_number' => '1917NEJ11659',
            'ip_address' => '10.30.73.9',
            'mac_address' => '00.10.7f.c4.f9.59',
        ]);

        Asset::create([
            'name' => 'Other Bealey Device',
            'item_type_id' => $itemType->id,
            'location_id' => $location->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'Bealey 3 Floating']));

        $response->assertOk();
        $this->assertSame(
            ['NVX352-A1-1-FLOATING'],
            $response->viewData('assets')->pluck('name')->all()
        );
    }

    private function itemType(): ItemType
    {
        return ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
    }
}

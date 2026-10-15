<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\Manufacturer;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetColumnFilterTest extends TestCase
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

    protected function user(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_assets_index_filters_by_column_text_fields(): void
    {
        $user = $this->user();

        $acme = Manufacturer::factory()->create(['name' => 'Acme']);
        $other = Manufacturer::factory()->create(['name' => 'OtherCo']);

        Asset::create([
            'name' => 'Alpha Fixture',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-ALPHA-1',
            'manufacturer_id' => $acme->id,
        ]);
        Asset::create([
            'name' => 'Beta Fixture',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-BETA-9',
            'manufacturer_id' => $other->id,
        ]);

        $byBarcode = $this->actingAs($user)->get(route('assets.index', [
            'tp_barcode' => 'ALPHA',
        ]));
        $byBarcode->assertOk();
        $this->assertSame(
            ['Alpha Fixture'],
            $byBarcode->viewData('assets')->pluck('name')->all()
        );

        $byManufacturer = $this->actingAs($user)->get(route('assets.index', [
            'manufacturer_id' => $other->id,
        ]));
        $byManufacturer->assertOk();
        $this->assertSame(
            ['Beta Fixture'],
            $byManufacturer->viewData('assets')->pluck('name')->all()
        );
    }

    public function test_assets_index_shows_per_column_filter_controls(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('id="asset-filters"', false);
        $response->assertSee('name="tp_barcode"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('Filter by status', false);
        $response->assertSee('>Filter</button>', false);
    }

    public function test_list_show_filters_items_by_status_and_search(): void
    {
        $user = $this->user();
        $list = AssetList::create([
            'name' => 'Stocktake',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $available = Asset::create([
            'name' => 'Available Light',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-AVAIL-1',
        ]);
        $lost = Asset::create([
            'name' => 'Lost Light',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Lost,
            'tp_barcode' => 'TP-LOST-1',
        ]);

        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $available->id,
        ]);
        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $lost->id,
        ]);

        $byStatus = $this->actingAs($user)->get(route('lists.show', [
            'list' => $list,
            'status' => AssetStatus::Available->value,
        ]));
        $byStatus->assertOk();
        $this->assertSame(
            ['Available Light'],
            $byStatus->viewData('items')->map(fn ($item) => $item->asset?->name)->all()
        );
        $byStatus->assertSee('id="asset-filters"', false);
        $byStatus->assertSee('name="status"', false);

        $bySearch = $this->actingAs($user)->get(route('lists.show', [
            'list' => $list,
            'q' => 'TP-LOST',
        ]));
        $bySearch->assertOk();
        $this->assertSame(
            ['Lost Light'],
            $bySearch->viewData('items')->map(fn ($item) => $item->asset?->name)->all()
        );
    }

    public function test_list_show_filters_by_column_barcode(): void
    {
        $user = $this->user();
        $list = AssetList::create([
            'name' => 'Inventory',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $match = Asset::create([
            'name' => 'Match Asset',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-MATCH-42',
        ]);
        $other = Asset::create([
            'name' => 'Other Asset',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-OTHER-7',
        ]);

        AssetListItem::create(['asset_list_id' => $list->id, 'asset_id' => $match->id]);
        AssetListItem::create(['asset_list_id' => $list->id, 'asset_id' => $other->id]);

        $response = $this->actingAs($user)->get(route('lists.show', [
            'list' => $list,
            'tp_barcode' => 'MATCH',
        ]));

        $response->assertOk();
        $this->assertSame(
            ['Match Asset'],
            $response->viewData('items')->map(fn ($item) => $item->asset?->name)->all()
        );
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\User;
use App\Support\PageSize;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PageSizePaginationTest extends TestCase
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

    protected function itemType(): ItemType
    {
        return ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);
    }

    /**
     * @return list<Asset>
     */
    protected function createAssets(int $count): array
    {
        $type = $this->itemType();
        $assets = [];

        for ($i = 1; $i <= $count; $i++) {
            $assets[] = Asset::create([
                'name' => sprintf('Asset %03d', $i),
                'item_type_id' => $type->id,
                'status' => AssetStatus::Available,
                'tp_barcode' => sprintf('TP-%03d', $i),
            ]);
        }

        return $assets;
    }

    public function test_assets_index_defaults_to_fifty_per_page_and_shows_summary(): void
    {
        $user = $this->scanner();
        $this->createAssets(60);

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('Showing', false);
        $response->assertSee('results', false);
        $response->assertSee('Per page', false);
        $response->assertSee('value="10"', false);
        $response->assertSee('value="50"', false);
        $response->assertSee('value="100"', false);
        $response->assertSee('value="200"', false);
        $response->assertSee('value="500"', false);
        $response->assertSee('value="all"', false);
        // Inline onchange must use window.URL — bare URL is document.URL (string) in that scope.
        $response->assertSee('new window.URL(window.location.href)', false);
        $response->assertDontSee('new URL(window.location.href)', false);

        $response->assertViewHas('assets', function (LengthAwarePaginator $assets): bool {
            return $assets->perPage() === 50
                && $assets->total() === 60
                && $assets->count() === 50
                && $assets->firstItem() === 1
                && $assets->lastItem() === 50;
        });
    }

    public function test_assets_index_honours_per_page_query(): void
    {
        $user = $this->scanner();
        $this->createAssets(25);

        $response = $this->actingAs($user)->get(route('assets.index', [
            PageSize::PARAM => 10,
        ]));

        $response->assertOk();
        $response->assertViewHas('assets', function (LengthAwarePaginator $assets): bool {
            return $assets->perPage() === 10
                && $assets->total() === 25
                && $assets->count() === 10
                && $assets->lastItem() === 10;
        });
    }

    public function test_assets_index_all_shows_every_result_on_one_page(): void
    {
        $user = $this->scanner();
        $this->createAssets(55);

        $response = $this->actingAs($user)->get(route('assets.index', [
            PageSize::PARAM => PageSize::ALL,
        ]));

        $response->assertOk();
        $response->assertSee('value="all"', false);
        $response->assertViewHas('assets', function (LengthAwarePaginator $assets): bool {
            return $assets->total() === 55
                && $assets->count() === 55
                && $assets->perPage() === 55
                && $assets->lastPage() === 1;
        });
    }

    public function test_assets_index_rejects_invalid_per_page_and_falls_back_to_default(): void
    {
        $user = $this->scanner();
        $this->createAssets(60);

        $response = $this->actingAs($user)->get(route('assets.index', [
            PageSize::PARAM => 999,
        ]));

        $response->assertOk();
        $response->assertViewHas('assets', function (LengthAwarePaginator $assets): bool {
            return $assets->perPage() === 50 && $assets->count() === 50;
        });
    }

    public function test_list_show_honours_per_page_query(): void
    {
        $user = $this->scanner();
        $assets = $this->createAssets(15);
        $list = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        foreach ($assets as $asset) {
            AssetListItem::create([
                'asset_list_id' => $list->id,
                'asset_id' => $asset->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('lists.show', [
            'list' => $list,
            PageSize::PARAM => 10,
        ]));

        $response->assertOk();
        $response->assertSee('Per page', false);
        $response->assertViewHas('items', function (LengthAwarePaginator $items): bool {
            return $items->perPage() === 10
                && $items->total() === 15
                && $items->count() === 10;
        });
    }
}

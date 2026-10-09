<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\User;
use App\Support\AssetTableColumns;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetColumnPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_asset_table_columns(): void
    {
        $this->patch(route('preferences.asset-columns'), [
            'columns' => ['name', 'status'],
        ])->assertRedirect(route('login'));
    }

    public function test_assets_index_uses_default_columns(): void
    {
        $user = User::factory()->create();
        $type = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);
        Asset::create([
            'name' => 'Moving Head',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-100',
        ]);

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('Columns');
        $response->assertSee('Moving Head');
        $response->assertSee('TP Barcode');
        $response->assertSee('Location');
    }

    public function test_user_can_save_asset_table_columns(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('assets.index'))->patch(route('preferences.asset-columns'), [
            'columns' => ['name', 'fmi_ast', 'serial_number'],
        ]);

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHas('status', 'Column preferences saved.');

        $user->refresh();

        $this->assertSame(
            ['name', 'fmi_ast', 'serial_number'],
            $user->preferences[AssetTableColumns::PREFERENCE_KEY]
        );
        $this->assertSame(['name', 'fmi_ast', 'serial_number'], $user->assetTableColumns());
    }

    public function test_saved_columns_apply_on_assets_index_and_lists(): void
    {
        $user = User::factory()->create();
        $user->preferences = [
            AssetTableColumns::PREFERENCE_KEY => ['name', 'fmi_ast', 'serial_number'],
        ];
        $user->save();

        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        $asset = Asset::create([
            'name' => 'Speaker',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'fmi_ast' => 'FMI-9',
            'serial_number' => 'SN-9',
            'tp_barcode' => 'TP-9',
        ]);

        $list = AssetList::create([
            'name' => 'Tour inventory',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
        ]);

        $assetsResponse = $this->actingAs($user)->get(route('assets.index'));
        $assetsResponse->assertOk();
        $assetsResponse->assertSee('FMI AST#');
        $assetsResponse->assertSee('Serial Number');
        $assetsResponse->assertSee('FMI-9');
        $assetsResponse->assertSee('SN-9');
        $assetsResponse->assertDontSee('TP-9');

        $listResponse = $this->actingAs($user)->get(route('lists.show', $list));
        $listResponse->assertOk();
        $listResponse->assertSee('FMI AST#');
        $listResponse->assertSee('Serial Number');
        $listResponse->assertSee('FMI-9');
        $listResponse->assertSee('SN-9');
        $listResponse->assertDontSee('TP-9');
    }

    public function test_invalid_columns_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('assets.index'))->patch(route('preferences.asset-columns'), [
            'columns' => ['name', 'not_a_real_column'],
        ]);

        $response->assertSessionHasErrors('columns.1');
        $this->assertNull($user->fresh()->preferences);
    }

    public function test_name_column_is_always_retained(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('preferences.asset-columns'), [
            'columns' => ['status', 'location'],
        ])->assertRedirect();

        $this->assertSame(
            ['name', 'status', 'location'],
            $user->fresh()->assetTableColumns()
        );
    }
}

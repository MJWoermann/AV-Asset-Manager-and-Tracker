<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\User;
use App\Support\AssetTableColumns;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetColumnPreferenceTest extends TestCase
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

    public function test_guest_cannot_update_asset_table_columns(): void
    {
        $this->patch(route('preferences.asset-columns'), [
            'columns' => ['name', 'status'],
        ])->assertRedirect(route('login'));
    }

    public function test_assets_index_uses_default_columns(): void
    {
        $user = $this->scanner();
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
        $user = $this->scanner();

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
        $user = $this->scanner();
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
        $user = $this->scanner();

        $response = $this->actingAs($user)->from(route('assets.index'))->patch(route('preferences.asset-columns'), [
            'columns' => ['name', 'not_a_real_column'],
        ]);

        $response->assertSessionHasErrors('columns.1');
        $this->assertNull($user->fresh()->preferences);
    }

    public function test_name_column_is_always_retained(): void
    {
        $user = $this->scanner();

        $this->actingAs($user)->patch(route('preferences.asset-columns'), [
            'columns' => ['status', 'location'],
        ])->assertRedirect();

        $this->assertSame(
            ['name', 'status', 'location'],
            $user->fresh()->assetTableColumns()
        );
    }

    public function test_hiding_a_column_on_assets_keeps_its_filter(): void
    {
        $user = $this->scanner();

        $response = $this->actingAs($user)
            ->from(route('assets.index', [
                'status' => AssetStatus::Available->value,
                'q' => 'mixer',
            ]))
            ->patch(route('preferences.asset-columns'), [
                'columns' => ['name', 'location', 'tp_barcode'],
            ]);

        $response->assertRedirect(route('assets.index', [
            'status' => AssetStatus::Available->value,
            'q' => 'mixer',
        ]));
        $this->assertSame(
            ['name', 'location', 'tp_barcode'],
            $user->fresh()->assetTableColumns()
        );
    }

    public function test_hiding_a_column_on_a_list_clears_its_filter(): void
    {
        $user = $this->scanner();
        $list = AssetList::create([
            'name' => 'Tour inventory',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('lists.show', [
                'list' => $list,
                'status' => AssetStatus::Available->value,
                'q' => 'mixer',
            ]))
            ->patch(route('preferences.asset-columns'), [
                'columns' => ['name', 'location', 'tp_barcode'],
            ]);

        $response->assertRedirect(route('lists.show', [
            'list' => $list,
            'q' => 'mixer',
        ]));
    }

    public function test_assets_index_applies_url_filters_for_hidden_columns(): void
    {
        $user = $this->scanner();
        $user->preferences = [
            AssetTableColumns::PREFERENCE_KEY => ['name', 'location', 'tp_barcode'],
        ];
        $user->save();

        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        Asset::create([
            'name' => 'Road Case',
            'item_type_id' => $type->id,
            'status' => AssetStatus::InService,
            'tp_barcode' => 'TP-ROAD',
        ]);
        Asset::create([
            'name' => 'Road Spare',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-SPARE',
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', [
            'status' => AssetStatus::InService->value,
            'q' => 'Road',
        ]));

        $response->assertOk();
        $this->assertSame(
            ['Road Case'],
            $response->viewData('assets')->pluck('name')->all()
        );
        $response->assertSee('Clear all filters', false);
        $response->assertSee('href="'.route('assets.index').'"', false);
        $response->assertSee('name="status"', false);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Exports\AssetsExport;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\User;
use App\Support\AssetExportColumns;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportColumnSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function inventoryManager(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    public function test_guest_cannot_view_export_column_settings(): void
    {
        $this->get(route('admin.export-columns.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_export_column_settings(): void
    {
        $manager = $this->inventoryManager();

        $this->actingAs($manager)
            ->get(route('admin.export-columns.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_view_export_column_settings(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.export-columns.edit'));

        $response->assertOk();
        $response->assertSee('Export columns');
        $response->assertSee('Asset exports');
        $response->assertSee('List exports');
        $response->assertSee('Description');
        $response->assertSee('Location');
    }

    public function test_admin_can_save_export_columns(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put(route('admin.export-columns.update'), [
            'asset_columns' => ['name', 'tp_barcode', 'location'],
            'list_columns' => ['name', 'fmi_ast', 'quantity'],
        ]);

        $response->assertRedirect(route('admin.export-columns.edit'));
        $response->assertSessionHas('status', 'Export columns saved.');

        $this->assertSame(['name', 'tp_barcode', 'location'], AssetExportColumns::forAssets());
        $this->assertSame(['name', 'fmi_ast', 'quantity'], AssetExportColumns::forLists());
    }

    public function test_name_column_is_always_retained_for_exports(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.export-columns.update'), [
            'asset_columns' => ['status', 'location'],
            'list_columns' => ['quantity'],
        ])->assertRedirect(route('admin.export-columns.edit'));

        $this->assertSame(['name', 'status', 'location'], AssetExportColumns::forAssets());
        $this->assertSame(['name', 'quantity'], AssetExportColumns::forLists());
    }

    public function test_invalid_export_columns_are_rejected(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->from(route('admin.export-columns.edit'))->put(route('admin.export-columns.update'), [
            'asset_columns' => ['name', 'not_a_real_column'],
            'list_columns' => ['name', 'status'],
        ]);

        $response->assertSessionHasErrors('asset_columns.1');
        $this->assertSame(AssetExportColumns::defaults(), AssetExportColumns::forAssets());
    }

    public function test_asset_and_list_exports_use_configured_columns(): void
    {
        $manager = $this->inventoryManager();
        AssetExportColumns::putAssets(['name', 'tp_barcode']);
        AssetExportColumns::putLists(['name', 'quantity', 'fmi_ast']);

        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        $asset = Asset::create([
            'name' => 'Speaker',
            'description' => 'Should not appear',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-42',
            'fmi_ast' => 'FMI-42',
            'quantity' => 1,
        ]);

        $list = AssetList::create([
            'name' => 'Tour inventory',
            'type' => 'inventory',
            'created_by' => $manager->id,
        ]);
        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
            'quantity' => 3,
            'added_by' => $manager->id,
        ]);

        $assetExport = new AssetsExport;
        $this->assertSame(['Name', 'TP Barcode'], $assetExport->headings());
        $this->assertSame([['Speaker', 'TP-42']], $assetExport->collection()->values()->all());

        $listExport = new AssetsExport($list->id);
        $this->assertSame(['Name', 'Quantity', 'FMI AST#'], $listExport->headings());
        $this->assertSame([['Speaker', '3', 'FMI-42']], $listExport->collection()->values()->all());
    }

    public function test_exports_default_to_legacy_columns_when_unset(): void
    {
        $export = new AssetsExport;

        $this->assertSame(
            AssetExportColumns::headings(AssetExportColumns::defaults()),
            $export->headings()
        );
    }
}

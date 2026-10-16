<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Exports\AssetsExport;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\Location;
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

    public function test_admin_can_view_export_column_settings_including_location_and_custom_sets(): void
    {
        $admin = $this->admin();

        $fieldSet = CustomFieldSet::create([
            'name' => 'Network Details',
            'slug' => 'network-details',
        ]);
        CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'VLAN',
            'slug' => 'vlan',
            'type' => 'text',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.export-columns.edit'));

        $response->assertOk();
        $response->assertSee('Export columns');
        $response->assertSee('Asset exports');
        $response->assertSee('List exports');
        $response->assertSee('Description');
        $response->assertSee('Location');
        $response->assertSee('Location: Level');
        $response->assertSee('Parent');
        $response->assertSee('Network Details');
        $response->assertSee('VLAN');
    }

    public function test_admin_can_save_export_columns_including_custom_fields(): void
    {
        $admin = $this->admin();

        $fieldSet = CustomFieldSet::create([
            'name' => 'Network Details',
            'slug' => 'network-details',
        ]);
        $definition = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'VLAN',
            'slug' => 'vlan',
            'type' => 'text',
            'sort_order' => 1,
        ]);
        $customKey = AssetExportColumns::CUSTOM_PREFIX.$definition->id;

        $response = $this->actingAs($admin)->put(route('admin.export-columns.update'), [
            'asset_columns' => ['name', 'tp_barcode', 'location', $customKey],
            'list_columns' => ['name', 'fmi_ast', 'quantity'],
        ]);

        $response->assertRedirect(route('admin.export-columns.edit'));
        $response->assertSessionHas('status', 'Export columns saved.');

        $this->assertSame(['name', 'tp_barcode', 'location', $customKey], AssetExportColumns::forAssets());
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

    public function test_asset_export_includes_selected_location_parent_and_custom_fields(): void
    {
        $level = Location::create([
            'name' => 'Level 1',
            'type' => LocationType::Level,
        ]);
        $room = Location::create([
            'name' => 'Room A',
            'type' => LocationType::Room,
            'parent_id' => $level->id,
        ]);
        $rack = Location::create([
            'name' => 'Rack 1',
            'type' => LocationType::Rack,
            'parent_id' => $room->id,
        ]);

        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        $parent = Asset::create([
            'name' => 'Parent Rack',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'fmi_ast' => 'FMI-PARENT',
            'tp_barcode' => 'TP-PARENT',
            'location_id' => $rack->id,
            'quantity' => 1,
        ]);

        $fieldSet = CustomFieldSet::create([
            'name' => 'Network Details',
            'slug' => 'network-details',
        ]);
        $definition = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'VLAN',
            'slug' => 'vlan',
            'type' => 'text',
            'sort_order' => 1,
        ]);
        $customKey = AssetExportColumns::CUSTOM_PREFIX.$definition->id;

        $child = Asset::create([
            'name' => 'Child Switch',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'fmi_ast' => 'FMI-CHILD',
            'tp_barcode' => 'TP-CHILD',
            'parent_id' => $parent->id,
            'location_id' => $rack->id,
            'quantity' => 1,
            'notes' => 'Spare unit',
        ]);

        AssetCustomFieldValue::create([
            'asset_id' => $child->id,
            'custom_field_definition_id' => $definition->id,
            'value' => '100',
        ]);

        AssetExportColumns::putAssets([
            'name',
            'location',
            'location_level',
            'location_room',
            'location_rack',
            'parent',
            'parent_fmi_ast',
            'parent_tp_barcode',
            'notes',
            $customKey,
        ]);

        $export = new AssetsExport;
        $headings = $export->headings();
        $rows = $export->collection()->keyBy(fn (array $row) => $row[0]);

        $this->assertSame([
            'Name',
            'Location',
            'Location: Level',
            'Location: Room',
            'Location: Rack',
            'Parent',
            'Parent FMI AST#',
            'Parent TP Barcode',
            'Notes',
            'VLAN (Network Details)',
        ], $headings);

        $childRow = $rows->get('Child Switch');
        $this->assertNotNull($childRow);

        $byHeading = array_combine($headings, $childRow);
        $this->assertSame('Level 1 / Room A / Rack 1', $byHeading['Location']);
        $this->assertSame('Level 1', $byHeading['Location: Level']);
        $this->assertSame('Room A', $byHeading['Location: Room']);
        $this->assertSame('Rack 1', $byHeading['Location: Rack']);
        $this->assertSame('Parent Rack', $byHeading['Parent']);
        $this->assertSame('FMI-PARENT', $byHeading['Parent FMI AST#']);
        $this->assertSame('TP-PARENT', $byHeading['Parent TP Barcode']);
        $this->assertSame('Spare unit', $byHeading['Notes']);
        $this->assertSame('100', $byHeading['VLAN (Network Details)']);
    }

    public function test_exports_default_to_legacy_columns_when_unset(): void
    {
        $export = new AssetsExport;

        $this->assertSame(
            AssetExportColumns::headings(AssetExportColumns::defaults()),
            $export->headings()
        );
    }

    public function test_asset_export_escapes_formula_like_cell_values(): void
    {
        AssetExportColumns::putAssets(['name', 'notes']);

        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        Asset::create([
            'name' => '=CMD()',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'notes' => '+1-555',
            'quantity' => 1,
        ]);

        $export = new AssetsExport;
        $row = $export->collection()->first();

        $this->assertSame(["'=CMD()", "'+1-555"], $row);
    }
}

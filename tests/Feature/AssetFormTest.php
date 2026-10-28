<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    public function test_create_form_renders_searchable_selects(): void
    {
        $user = $this->manager();
        ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        $response = $this->actingAs($user)->get(route('assets.create'));

        $response->assertOk();
        $response->assertSee('searchableSelect(', false);
        $response->assertSee('catalogueManufacturerModelFields(', false);
        $response->assertSee('placeholder="Search…"', false);
        $response->assertSee('name="item_type_id"', false);
        $response->assertSee('name="location_id"', false);
        $response->assertSee('name="parent_id"', false);
        $response->assertSee('name="manufacturer_id"', false);
        $response->assertSee('+ Add new manufacturer…', false);
        $response->assertSee('searchUrl:', false);
        $response->assertSee('parents_only', false);
        $response->assertSee('Search by name, ID#, barcode…', false);
    }

    public function test_edit_form_seeds_only_selected_parent_option(): void
    {
        $user = $this->manager();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        $parent = Asset::factory()->create([
            'name' => 'Selected Parent Rack',
            'item_type_id' => $itemType->id,
            'tp_barcode' => 'PARENT-TP-1',
        ]);
        $other = Asset::factory()->create([
            'name' => 'Other Parent Should Not Embed',
            'item_type_id' => $itemType->id,
            'tp_barcode' => 'OTHER-TP-9',
        ]);
        $child = Asset::factory()->create([
            'name' => 'Child Asset',
            'item_type_id' => $itemType->id,
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($user)->get(route('assets.edit', $child));

        $response->assertOk();
        $response->assertSee('Selected Parent Rack', false);
        $response->assertSee('PARENT-TP-1', false);
        $response->assertSee('#'.$parent->id, false);
        $response->assertDontSee('Other Parent Should Not Embed', false);
        $response->assertDontSee('OTHER-TP-9', false);
        $response->assertSee('exclude_id', false);
    }

    public function test_create_form_marks_required_fields(): void
    {
        $user = $this->manager();

        $response = $this->actingAs($user)->get(route('assets.create'));

        $response->assertOk();
        $response->assertSee('Required field', false);
        $this->assertSame(
            3,
            substr_count($response->getContent(), 'sr-only">(required)'),
            'Name, Item type, and Status should each show a required marker'
        );
    }

    public function test_create_form_offers_camera_scan_for_identifier_fields(): void
    {
        $user = $this->manager();

        $response = $this->actingAs($user)->get(route('assets.create'));

        $response->assertOk();
        $response->assertSee("fieldBarcodeScanner('serial_number'", false);
        $response->assertSee("fieldBarcodeScanner('tp_barcode'", false);
        $response->assertSee("fieldBarcodeScanner('mac_address'", false);
        $response->assertSee('id="field-qr-serial_number"', false);
        $response->assertSee('id="field-qr-tp_barcode"', false);
        $response->assertSee('id="field-qr-mac_address"', false);
        $response->assertDontSee("fieldBarcodeScanner('fmi_ast'", false);
        $response->assertDontSee("fieldBarcodeScanner('rig_tag'", false);
    }

    public function test_edit_form_offers_scan_for_flagged_and_mac_custom_fields(): void
    {
        $user = $this->manager();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        $fieldSet = CustomFieldSet::create([
            'name' => 'Labels',
            'slug' => 'labels',
        ]);
        $itemType->fieldSets()->attach($fieldSet->id);
        $scannable = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Shelf code',
            'slug' => 'shelf-code',
            'type' => 'text',
            'is_scannable' => true,
            'sort_order' => 0,
        ]);
        $plain = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Notes label',
            'slug' => 'notes-label',
            'type' => 'text',
            'is_scannable' => false,
            'sort_order' => 1,
        ]);
        $mac = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'MAC Address',
            'slug' => 'mac-address',
            'type' => 'text',
            'is_scannable' => false,
            'sort_order' => 2,
        ]);
        $asset = Asset::factory()->create([
            'item_type_id' => $itemType->id,
        ]);

        $response = $this->actingAs($user)->get(route('assets.edit', $asset));

        $response->assertOk();
        $response->assertSee("fieldBarcodeScanner('cf_{$scannable->id}'", false);
        $response->assertSee('Scan barcode into Shelf code', false);
        $response->assertDontSee("fieldBarcodeScanner('cf_{$plain->id}'", false);
        $response->assertSee("fieldBarcodeScanner('cf_{$mac->id}'", false);
    }
}

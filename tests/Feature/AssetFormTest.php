<?php

namespace Tests\Feature;

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
}

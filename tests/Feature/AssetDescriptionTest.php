<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetDescriptionTest extends TestCase
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

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_store_persists_description_on_asset(): void
    {
        $user = $this->manager();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Test Fixture',
            'description' => 'Front of house wash light',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
        ]);

        $asset = Asset::query()->where('name', 'Test Fixture')->first();

        $this->assertNotNull($asset);
        $this->assertSame('Front of house wash light', $asset->description);
        $response->assertRedirect(route('assets.show', $asset));
    }

    public function test_update_persists_description_on_asset(): void
    {
        $user = $this->manager();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        $asset = Asset::create([
            'name' => 'Test Fixture',
            'description' => 'Original description',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->put(route('assets.update', $asset), [
            'name' => 'Test Fixture',
            'description' => 'Updated description',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
        ]);

        $response->assertRedirect(route('assets.show', $asset));
        $this->assertSame('Updated description', $asset->fresh()->description);
    }

    public function test_show_page_displays_description(): void
    {
        $user = $this->scanner();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        $asset = Asset::create([
            'name' => 'Test Fixture',
            'description' => 'Visible on detail page',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', $asset));

        $response->assertOk();
        $response->assertSee('Visible on detail page');
    }

    public function test_show_page_displays_every_stored_asset_field(): void
    {
        $user = $this->scanner();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        $parent = Asset::create([
            'name' => 'Parent Rack',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);
        $asset = Asset::create([
            'name' => 'Detail Fixture',
            'description' => "Front wash\nSecond line",
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'parent_id' => $parent->id,
            'serial_number' => 'SN-4401',
            'supplier' => 'Stage Supply Co',
            'fmi_ast' => 'FMI-19',
            'tp_barcode' => 'TP-8821',
            'rig_tag' => 'RIG-3',
            'ip_address' => '10.1.2.3',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'purchase_date' => '2024-03-01',
            'warranty_expiry' => '2027-03-01',
            'test_tag_expiry' => '2026-06-15',
            'replacement_cost' => '1250.50',
            'quantity' => 4,
            'min_stock_level' => 1,
            'max_stock_level' => 8,
            'ru_height' => 2,
            'ru_rear_enabled' => true,
            'notes' => "Keep with the truss\n<script>alert(1)</script>",
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', $asset));

        $response->assertOk();
        $response->assertSee('Front wash');
        $response->assertSee('Second line');
        $response->assertSee('General');
        $response->assertSee('Parent Rack');
        $response->assertSee('SN-4401');
        $response->assertSee('Stage Supply Co');
        $response->assertSee('FMI-19');
        $response->assertSee('TP-8821');
        $response->assertSee('RIG-3');
        $response->assertSee('10.1.2.3');
        $response->assertSee('AA:BB:CC:DD:EE:FF');
        $response->assertSee('2024-03-01');
        $response->assertSee('2027-03-01');
        $response->assertSee('2026-06-15');
        $response->assertSee('1,250.50');
        $response->assertSee('Keep with the truss');
        $response->assertSee('<script>alert(1)</script>');
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('Notes');
        $response->assertSee('Supplier');
        $response->assertSee('Purchase date');
        $response->assertSee('Warranty expiry');
        $response->assertSee('Replacement cost');
        $response->assertSee('Min stock level');
        $response->assertSee('Max stock level');
        $response->assertSee('RU height');
        $response->assertSee('Rear face');
    }

    public function test_search_matches_description(): void
    {
        $user = $this->scanner();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        Asset::create([
            'name' => 'Quiet Name',
            'description' => 'UniquePhraseForSearch',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'UniquePhraseForSearch']));

        $response->assertOk();
        $response->assertSee('Quiet Name');
        $response->assertSee('<mark class="bg-brand/30 text-inherit rounded-sm px-0.5">UniquePhraseForSearch</mark>', false);
    }
}

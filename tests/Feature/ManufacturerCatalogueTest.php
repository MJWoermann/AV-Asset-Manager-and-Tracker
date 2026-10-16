<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\User;
use App\Services\ManufacturerCatalogueService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManufacturerCatalogueTest extends TestCase
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

    protected function itemType(): ItemType
    {
        return ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
    }

    public function test_index_shows_nested_manufacturers_and_models(): void
    {
        $user = $this->scanner();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Yamaha']);
        ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'QL1',
        ]);

        $response = $this->actingAs($user)->get(route('manufacturers.index'));

        $response->assertOk();
        $response->assertSee('Yamaha', false);
        $response->assertSee('QL1', false);
        $response->assertSee('Assets', false);
    }

    public function test_index_includes_hierarchy_expand_controls(): void
    {
        $user = $this->scanner();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Yamaha']);
        ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'QL1',
        ]);

        $response = $this->actingAs($user)->get(route('manufacturers.index'));

        $response->assertOk();
        $response->assertSee('manufacturerCatalogue(', false);
        $response->assertSee('Expand all', false);
        $response->assertSee('Collapse all', false);
        $response->assertSee('data-manufacturer-collapsible="1"', false);
        $response->assertSee('Toggle Yamaha models', false);
    }

    public function test_manager_can_create_manufacturer_and_model(): void
    {
        $user = $this->manager();

        $this->actingAs($user)->post(route('manufacturers.store'), [
            'name' => 'Chauvet',
        ])->assertRedirect(route('manufacturers.index'));

        $manufacturer = Manufacturer::query()->where('name', 'Chauvet')->first();
        $this->assertNotNull($manufacturer);

        $this->actingAs($user)->post(route('manufacturers.models.store', $manufacturer), [
            'name' => 'COLORado',
        ])->assertRedirect(route('manufacturers.index'));

        $this->assertDatabaseHas('manufacturer_models', [
            'manufacturer_id' => $manufacturer->id,
            'name' => 'COLORado',
        ]);
    }

    public function test_manager_can_delete_manufacturer_and_transfer_models_and_assets(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $keep = Manufacturer::factory()->create(['name' => 'KeepCo']);
        $delete = Manufacturer::factory()->create(['name' => 'DeleteCo']);
        $keepModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $keep->id,
            'name' => 'Shared',
        ]);
        $deleteModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $delete->id,
            'name' => 'Shared',
        ]);
        $asset = Asset::create([
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $delete->id,
            'manufacturer_model_id' => $deleteModel->id,
        ]);

        $response = $this->actingAs($user)->delete(route('manufacturers.destroy', $delete), [
            'replacement_manufacturer_id' => $keep->id,
        ]);

        $response->assertRedirect(route('manufacturers.index'));
        $this->assertDatabaseMissing('manufacturers', ['id' => $delete->id]);
        $this->assertDatabaseMissing('manufacturer_models', ['id' => $deleteModel->id]);
        $asset->refresh();
        $this->assertSame($keep->id, $asset->manufacturer_id);
        $this->assertSame($keepModel->id, $asset->manufacturer_model_id);
    }

    public function test_manager_can_delete_model_and_reassign_assets(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $manufacturer = Manufacturer::factory()->create();
        $keep = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Keep Model',
        ]);
        $delete = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Delete Model',
        ]);
        $asset = Asset::create([
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $delete->id,
        ]);

        $response = $this->actingAs($user)->delete(route('manufacturers.models.destroy', [$manufacturer, $delete]), [
            'replacement_manufacturer_model_id' => $keep->id,
        ]);

        $response->assertRedirect(route('manufacturers.index'));
        $this->assertDatabaseMissing('manufacturer_models', ['id' => $delete->id]);
        $this->assertSame($keep->id, $asset->fresh()->manufacturer_model_id);
    }

    public function test_scanner_can_create_manufacturer_and_model(): void
    {
        $user = $this->scanner();

        $this->actingAs($user)->get(route('manufacturers.create'))->assertOk();

        $this->actingAs($user)->post(route('manufacturers.store'), [
            'name' => 'ScannerCo',
        ])->assertRedirect(route('manufacturers.index'));

        $manufacturer = Manufacturer::query()->where('name', 'ScannerCo')->first();
        $this->assertNotNull($manufacturer);

        $this->actingAs($user)->get(route('manufacturers.models.create', $manufacturer))->assertOk();

        $this->actingAs($user)->post(route('manufacturers.models.store', $manufacturer), [
            'name' => 'ScannerModel',
        ])->assertRedirect(route('manufacturers.index'));

        $this->assertDatabaseHas('manufacturer_models', [
            'manufacturer_id' => $manufacturer->id,
            'name' => 'ScannerModel',
        ]);
    }

    public function test_scanner_cannot_edit_update_or_destroy_catalogue(): void
    {
        $user = $this->scanner();
        $manufacturer = Manufacturer::factory()->create(['name' => 'KeepCo']);
        $other = Manufacturer::factory()->create(['name' => 'OtherCo']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'KeepModel',
        ]);
        $otherModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $other->id,
            'name' => 'OtherModel',
        ]);

        $this->actingAs($user)->get(route('manufacturers.edit', $manufacturer))->assertForbidden();
        $this->actingAs($user)->put(route('manufacturers.update', $manufacturer), [
            'name' => 'Renamed',
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('manufacturers.destroy', $manufacturer), [
            'replacement_manufacturer_id' => $other->id,
        ])->assertForbidden();

        $this->actingAs($user)->get(route('manufacturers.models.edit', [$manufacturer, $model]))->assertForbidden();
        $this->actingAs($user)->put(route('manufacturers.models.update', [$manufacturer, $model]), [
            'name' => 'RenamedModel',
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('manufacturers.models.destroy', [$manufacturer, $model]), [
            'replacement_manufacturer_model_id' => $otherModel->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('manufacturers', [
            'id' => $manufacturer->id,
            'name' => 'KeepCo',
        ]);
        $this->assertDatabaseHas('manufacturer_models', [
            'id' => $model->id,
            'name' => 'KeepModel',
        ]);
    }

    public function test_asset_form_can_create_manufacturer_and_model_on_the_fly(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'New Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'manufacturer_id' => '__new__',
            'new_manufacturer' => 'InlineCo',
            'manufacturer_model_id' => '__new__',
            'new_manufacturer_model' => 'InlineModel',
        ]);

        $asset = Asset::query()->where('name', 'New Fixture')->first();
        $this->assertNotNull($asset);
        $response->assertRedirect(route('assets.show', $asset));
        $this->assertNotNull($asset->manufacturer_id);
        $this->assertNotNull($asset->manufacturer_model_id);
        $this->assertSame('InlineCo', $asset->manufacturer?->name);
        $this->assertSame('InlineModel', $asset->manufacturerModel?->name);
    }

    public function test_assets_index_filters_by_manufacturer_and_model_ids(): void
    {
        $user = $this->scanner();
        $itemType = $this->itemType();
        $acme = Manufacturer::factory()->create(['name' => 'Acme']);
        $other = Manufacturer::factory()->create(['name' => 'OtherCo']);
        $acmeModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $acme->id,
            'name' => 'A1',
        ]);
        $otherModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $other->id,
            'name' => 'B2',
        ]);

        Asset::create([
            'name' => 'Alpha Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $acme->id,
            'manufacturer_model_id' => $acmeModel->id,
        ]);
        Asset::create([
            'name' => 'Beta Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $other->id,
            'manufacturer_model_id' => $otherModel->id,
        ]);

        $byManufacturer = $this->actingAs($user)->get(route('assets.index', [
            'manufacturer_id' => $other->id,
        ]));
        $byManufacturer->assertOk();
        $this->assertSame(
            ['Beta Fixture'],
            $byManufacturer->viewData('assets')->pluck('name')->all()
        );

        $byModel = $this->actingAs($user)->get(route('assets.index', [
            'manufacturer_model_id' => $acmeModel->id,
        ]));
        $byModel->assertOk();
        $this->assertSame(
            ['Alpha Fixture'],
            $byModel->viewData('assets')->pluck('name')->all()
        );
    }

    public function test_import_finds_or_creates_manufacturer_and_model(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        ItemType::create(['name' => 'Audio Equipment', 'slug' => 'audio-equipment']);
        $existing = Manufacturer::factory()->create(['name' => 'ExistingCo']);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Manufacturer,Model',
            'Desk,Audio Equipment,ExistingCo,DeskOne',
            'Wash,Audio Equipment,BrandNew,WashX',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Manufacturer' => 'manufacturer',
                'Model' => 'model',
            ],
        ])->assertOk();

        $this->actingAs($user)->post(route('import.process'), [
            'actions' => [],
            'bulk_action' => 'create',
        ])->assertRedirect(route('assets.index'));

        $desk = Asset::query()->where('name', 'Desk')->first();
        $wash = Asset::query()->where('name', 'Wash')->first();
        $this->assertNotNull($desk);
        $this->assertNotNull($wash);
        $this->assertSame($existing->id, $desk->manufacturer_id);
        $this->assertSame('DeskOne', $desk->manufacturerModel?->name);
        $this->assertSame('BrandNew', $wash->manufacturer?->name);
        $this->assertSame('WashX', $wash->manufacturerModel?->name);
        $this->assertSame(1, Manufacturer::query()->where('name', 'BrandNew')->count());
    }

    public function test_catalogue_service_resolves_case_insensitive_names(): void
    {
        $service = app(ManufacturerCatalogueService::class);
        $first = $service->resolveManufacturer('Yamaha');
        $second = $service->resolveManufacturer('yamaha');

        $this->assertNotNull($first);
        $this->assertTrue($first->is($second));
        $this->assertSame(1, Manufacturer::query()->count());
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\RepairProcedure;
use App\Models\SparePart;
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

    public function test_index_shows_manufacturers_without_embedding_models(): void
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
        $response->assertDontSee('QL1', false);
        $response->assertSee('Assets', false);
        $response->assertSee(route('manufacturers.models.index', $manufacturer), false);
    }

    public function test_manager_delete_dialogs_use_searchable_replacement_lists(): void
    {
        $user = $this->manager();
        $keep = Manufacturer::factory()->create(['name' => 'KeepCo']);
        $delete = Manufacturer::factory()->create(['name' => 'DeleteCo']);
        ManufacturerModel::factory()->create([
            'manufacturer_id' => $keep->id,
            'name' => 'Keep Model',
        ]);
        $deleteModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $delete->id,
            'name' => 'Delete Model',
        ]);
        Asset::create([
            'name' => 'Delete Fixture',
            'item_type_id' => $this->itemType()->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $delete->id,
            'manufacturer_model_id' => $deleteModel->id,
        ]);

        $index = $this->actingAs($user)->get(route('manufacturers.index'));

        $index->assertOk();
        $index->assertSee('id="replacement_manufacturer_id"', false);
        $index->assertSee('id="replacement_manufacturer_model_id"', false);
        $index->assertSee('Search manufacturers…', false);
        $index->assertSee('Search models…', false);
        $index->assertSee('searchableSelect(', false);
        $index->assertDontSee('<select id="replacement_manufacturer_id"', false);
        $index->assertDontSee('<select id="replacement_manufacturer_model_id"', false);

        $manufacturerEdit = $this->actingAs($user)->get(route('manufacturers.edit', $delete));

        $manufacturerEdit->assertOk();
        $manufacturerEdit->assertSee('id="replacement_manufacturer_id"', false);
        $manufacturerEdit->assertSee('Search manufacturers…', false);
        $manufacturerEdit->assertDontSee('<select id="replacement_manufacturer_id"', false);

        $modelEdit = $this->actingAs($user)->get(route('manufacturers.models.edit', [$delete, $deleteModel]));

        $modelEdit->assertOk();
        $modelEdit->assertSee('id="replacement_manufacturer_model_id"', false);
        $modelEdit->assertSee('Search models…', false);
        $modelEdit->assertDontSee('<select id="replacement_manufacturer_model_id"', false);
    }

    public function test_manager_can_delete_unused_manufacturer_without_a_replacement(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'UnusedCo']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Unused Model',
        ]);

        $response = $this->actingAs($user)->delete(route('manufacturers.destroy', $manufacturer));

        $response->assertRedirect(route('manufacturers.index'));
        $this->assertDatabaseMissing('manufacturers', ['id' => $manufacturer->id]);
        $this->assertDatabaseMissing('manufacturer_models', ['id' => $model->id]);
        $this->assertDatabaseMissing('manufacturer_aliases', [
            'former_name' => 'UnusedCo',
        ]);
    }

    public function test_unused_manufacturer_edit_confirms_delete_without_a_replacement_list(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'SoloCo']);

        $response = $this->actingAs($user)->get(route('manufacturers.edit', $manufacturer));

        $response->assertOk();
        $response->assertSee('Delete manufacturer', false);
        $response->assertSee('This manufacturer has no assets. Delete it? Its models will be removed.', false);
        $response->assertDontSee('id="replacement_manufacturer_id"', false);
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
        $response->assertSee('Loading models', false);
    }

    public function test_models_index_returns_manufacturer_models_as_json(): void
    {
        $user = $this->scanner();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Yamaha']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'QL1',
        ]);

        $response = $this->actingAs($user)->getJson(route('manufacturers.models.index', $manufacturer));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $model->id);
        $response->assertJsonPath('data.0.name', 'QL1');
        $response->assertJsonPath('data.0.label', 'Yamaha / QL1');
        $response->assertJsonPath('data.0.edit_url', null);
        $response->assertJsonPath('data.0.destroy_url', null);
    }

    public function test_manager_models_index_includes_manage_urls(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Yamaha']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'QL1',
        ]);

        $response = $this->actingAs($user)->getJson(route('manufacturers.models.index', $manufacturer));

        $response->assertOk();
        $response->assertJsonPath('data.0.edit_url', route('manufacturers.models.edit', [$manufacturer, $model]));
        $response->assertJsonPath('data.0.destroy_url', route('manufacturers.models.destroy', [$manufacturer, $model]));
    }

    public function test_manager_can_fetch_replacement_model_options(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Yamaha']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'QL1',
        ]);

        $response = $this->actingAs($user)->getJson(route('manufacturers.replacement-models'));

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $model->id,
            'label' => 'Yamaha / QL1',
        ]);
    }

    public function test_scanner_cannot_fetch_replacement_model_options(): void
    {
        $user = $this->scanner();

        $this->actingAs($user)
            ->getJson(route('manufacturers.replacement-models'))
            ->assertForbidden();
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
        $response->assertSessionHas('expand_manufacturer_id', $manufacturer->id);
        $this->assertDatabaseMissing('manufacturer_models', ['id' => $delete->id]);
        $this->assertSame($keep->id, $asset->fresh()->manufacturer_model_id);

        $index = $this->actingAs($user)->get(route('manufacturers.index'));
        $index->assertOk();
        $index->assertSee('restoreManufacturerId: '.$manufacturer->id, false);
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

    public function test_reassigning_the_last_asset_removes_the_previous_model_and_manufacturer(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $drop = Manufacturer::factory()->create(['name' => 'DropCo']);
        $keep = Manufacturer::factory()->create(['name' => 'KeepCo']);
        $dropModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $drop->id,
            'name' => 'DropModel',
        ]);
        $keepModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $keep->id,
            'name' => 'KeepModel',
        ]);
        $asset = Asset::create([
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $drop->id,
            'manufacturer_model_id' => $dropModel->id,
        ]);

        $response = $this->actingAs($user)->put(route('assets.update', $asset), [
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'manufacturer_id' => $keep->id,
            'manufacturer_model_id' => $keepModel->id,
        ]);

        $response->assertRedirect(route('assets.show', $asset));
        $asset->refresh();
        $this->assertSame($keep->id, $asset->manufacturer_id);
        $this->assertSame($keepModel->id, $asset->manufacturer_model_id);
        $this->assertModelMissing($drop);
        $this->assertModelMissing($dropModel);
        $this->assertModelExists($keep);
        $this->assertModelExists($keepModel);
    }

    public function test_deleting_the_last_asset_removes_its_model_and_manufacturer(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $manufacturer = Manufacturer::factory()->create(['name' => 'GoneCo']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'GoneModel',
        ]);
        $asset = Asset::create([
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);

        $response = $this->actingAs($user)->delete(route('assets.destroy', $asset));

        $response->assertRedirect(route('assets.index'));
        $this->assertModelMissing($asset);
        $this->assertModelMissing($manufacturer);
        $this->assertModelMissing($model);
    }

    public function test_model_and_manufacturer_remain_while_another_asset_uses_them(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $manufacturer = Manufacturer::factory()->create(['name' => 'StillCo']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'StillModel',
        ]);
        $removed = Asset::create([
            'name' => 'Removed Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);
        Asset::create([
            'name' => 'Remaining Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);

        $response = $this->actingAs($user)->delete(route('assets.destroy', $removed));

        $response->assertRedirect(route('assets.index'));
        $this->assertModelExists($manufacturer);
        $this->assertModelExists($model);
    }

    public function test_model_linked_to_a_spare_part_remains_after_its_last_asset_is_cleared(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $manufacturer = Manufacturer::factory()->create(['name' => 'SpareCo']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'SpareModel',
        ]);
        $asset = Asset::create([
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);
        SparePart::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);

        $response = $this->actingAs($user)->put(route('assets.update', $asset), [
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'manufacturer_id' => '',
            'manufacturer_model_id' => '',
        ]);

        $response->assertRedirect(route('assets.show', $asset));
        $asset->refresh();
        $this->assertNull($asset->manufacturer_id);
        $this->assertNull($asset->manufacturer_model_id);
        $this->assertModelExists($manufacturer);
        $this->assertModelExists($model);
    }

    public function test_model_linked_to_a_repair_procedure_remains_after_its_last_asset_is_cleared(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $manufacturer = Manufacturer::factory()->create(['name' => 'ProcedureCo']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'ProcedureModel',
        ]);
        $asset = Asset::create([
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);
        RepairProcedure::factory()->create([
            'manufacturer_model_id' => $model->id,
        ]);

        $response = $this->actingAs($user)->put(route('assets.update', $asset), [
            'name' => 'Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'manufacturer_id' => '',
            'manufacturer_model_id' => '',
        ]);

        $response->assertRedirect(route('assets.show', $asset));
        $this->assertModelExists($manufacturer);
        $this->assertModelExists($model);
    }

    public function test_manufacturer_remains_when_another_department_still_has_an_asset(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $otherDepartment = Department::factory()->create();
        $manufacturer = Manufacturer::factory()->create(['name' => 'SharedCo']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'SharedModel',
        ]);
        $visible = Asset::create([
            'name' => 'Visible Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);
        Asset::withoutDepartmentScope()->create([
            'department_id' => $otherDepartment->id,
            'name' => 'Other Department Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);

        $response = $this->actingAs($user)->delete(route('assets.destroy', $visible));

        $response->assertRedirect(route('assets.index'));
        $this->assertModelExists($manufacturer);
        $this->assertModelExists($model);
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

    public function test_unspecified_model_edit_offers_an_existing_or_new_manufacturer(): void
    {
        $user = $this->manager();
        $unspecified = Manufacturer::factory()->create(['name' => Manufacturer::UNSPECIFIED_NAME]);
        $named = Manufacturer::factory()->create(['name' => 'Yamaha']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $unspecified->id,
            'name' => 'QL5',
        ]);
        $namedModel = ManufacturerModel::factory()->create([
            'manufacturer_id' => $named->id,
            'name' => 'QL1',
        ]);

        $edit = $this->actingAs($user)->get(route('manufacturers.models.edit', [$unspecified, $model]));
        $edit->assertOk();
        $edit->assertSee('Search manufacturers');
        $edit->assertSee('+ Add new manufacturer');
        $edit->assertSee('Yamaha');

        $namedEdit = $this->actingAs($user)->get(route('manufacturers.models.edit', [$named, $namedModel]));
        $namedEdit->assertOk();
        $namedEdit->assertDontSee('Search manufacturers', false);
        $namedEdit->assertDontSee('+ Add new manufacturer', false);
    }

    public function test_manager_can_assign_an_existing_manufacturer_to_an_unspecified_model(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $otherDepartment = Department::factory()->create();
        $unspecified = Manufacturer::factory()->create(['name' => Manufacturer::UNSPECIFIED_NAME]);
        $target = Manufacturer::factory()->create(['name' => 'Yamaha']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $unspecified->id,
            'name' => 'QL5',
        ]);
        ManufacturerModel::factory()->create([
            'manufacturer_id' => $unspecified->id,
            'name' => 'Stays',
        ]);
        $asset = Asset::create([
            'name' => 'Console',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $unspecified->id,
            'manufacturer_model_id' => $model->id,
        ]);
        $hidden = Asset::withoutDepartmentScope()->create([
            'department_id' => $otherDepartment->id,
            'name' => 'Other console',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $unspecified->id,
            'manufacturer_model_id' => $model->id,
        ]);
        $spare = SparePart::factory()->create([
            'manufacturer_id' => $unspecified->id,
            'manufacturer_model_id' => $model->id,
        ]);

        $response = $this->actingAs($user)->put(route('manufacturers.models.update', [$unspecified, $model]), [
            'name' => 'QL5',
            'manufacturer_id' => (string) $target->id,
        ]);

        $response->assertRedirect(route('manufacturers.index'));
        $response->assertSessionHas('status', 'Manufacturer assigned.');
        $response->assertSessionHas('expand_manufacturer_id', $target->id);
        $this->assertSame($target->id, $model->fresh()->manufacturer_id);
        $this->assertSame($target->id, $asset->fresh()->manufacturer_id);
        $this->assertSame($model->id, $asset->fresh()->manufacturer_model_id);
        $hidden->refresh();
        $this->assertSame($target->id, Asset::withoutDepartmentScope()->find($hidden->id)?->manufacturer_id);
        $this->assertSame($target->id, $spare->fresh()->manufacturer_id);
        $this->assertModelExists($unspecified);
    }

    public function test_manager_can_add_a_manufacturer_for_an_unspecified_model(): void
    {
        $user = $this->manager();
        $unspecified = Manufacturer::factory()->create(['name' => Manufacturer::UNSPECIFIED_NAME]);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $unspecified->id,
            'name' => 'QL5',
        ]);

        $response = $this->actingAs($user)->put(route('manufacturers.models.update', [$unspecified, $model]), [
            'name' => 'QL5',
            'manufacturer_id' => '__new__',
            'new_manufacturer' => 'DiGiCo',
        ]);

        $created = Manufacturer::query()->where('name', 'DiGiCo')->first();
        $response->assertRedirect(route('manufacturers.index'));
        $response->assertSessionHas('expand_manufacturer_id', $created?->id);
        $this->assertNotNull($created);
        $this->assertSame($created->id, $model->fresh()->manufacturer_id);
        $this->assertDatabaseMissing('manufacturers', ['id' => $unspecified->id]);
    }

    public function test_assigning_an_unspecified_model_merges_into_the_same_model_name(): void
    {
        $user = $this->manager();
        $itemType = $this->itemType();
        $unspecified = Manufacturer::factory()->create(['name' => Manufacturer::UNSPECIFIED_NAME]);
        $target = Manufacturer::factory()->create(['name' => 'Yamaha']);
        $existing = ManufacturerModel::factory()->create([
            'manufacturer_id' => (string) $target->id,
            'name' => 'QL5',
        ]);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $unspecified->id,
            'name' => 'ql5',
        ]);
        $asset = Asset::create([
            'name' => 'Console',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'manufacturer_id' => $unspecified->id,
            'manufacturer_model_id' => $model->id,
        ]);
        $procedure = RepairProcedure::factory()->create([
            'manufacturer_model_id' => $model->id,
        ]);
        $spare = SparePart::factory()->create();
        $model->compatibleSpareParts()->attach($spare);

        $response = $this->actingAs($user)->put(route('manufacturers.models.update', [$unspecified, $model]), [
            'name' => 'QL5',
            'manufacturer_id' => (string) $target->id,
        ]);

        $response->assertRedirect(route('manufacturers.index'));
        $response->assertSessionHas('status', 'Model assigned to the existing manufacturer model.');
        $this->assertDatabaseMissing('manufacturer_models', ['id' => $model->id]);
        $this->assertSame($existing->id, $asset->fresh()->manufacturer_model_id);
        $this->assertSame($target->id, $asset->fresh()->manufacturer_id);
        $this->assertSame($existing->id, $procedure->fresh()->manufacturer_model_id);
        $this->assertTrue($existing->compatibleSpareParts()->whereKey($spare->id)->exists());
        $this->assertDatabaseMissing('manufacturers', ['id' => $unspecified->id]);
    }

    public function test_adding_a_manufacturer_requires_a_name(): void
    {
        $user = $this->manager();
        $unspecified = Manufacturer::factory()->create(['name' => Manufacturer::UNSPECIFIED_NAME]);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $unspecified->id,
            'name' => 'QL5',
        ]);

        $response = $this->actingAs($user)->from(route('manufacturers.models.edit', [$unspecified, $model]))
            ->put(route('manufacturers.models.update', [$unspecified, $model]), [
                'name' => 'QL1',
                'manufacturer_id' => '__new__',
            ]);

        $response->assertRedirect(route('manufacturers.models.edit', [$unspecified, $model]));
        $response->assertSessionHasErrors([
            'new_manufacturer' => 'Enter a manufacturer name.',
        ]);
        $this->assertSame('QL5', $model->fresh()->name);
        $this->assertSame($unspecified->id, $model->fresh()->manufacturer_id);
    }

    public function test_unspecified_model_can_be_renamed_without_a_manufacturer(): void
    {
        $user = $this->manager();
        $unspecified = Manufacturer::factory()->create(['name' => Manufacturer::UNSPECIFIED_NAME]);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $unspecified->id,
            'name' => 'QL5',
        ]);

        $this->actingAs($user)->put(route('manufacturers.models.update', [$unspecified, $model]), [
            'name' => 'QL1',
        ])->assertRedirect(route('manufacturers.index'));

        $this->assertSame('QL1', $model->fresh()->name);
        $this->assertSame($unspecified->id, $model->fresh()->manufacturer_id);
    }

    public function test_named_model_update_does_not_change_its_manufacturer(): void
    {
        $user = $this->manager();
        $named = Manufacturer::factory()->create(['name' => 'Yamaha']);
        $other = Manufacturer::factory()->create(['name' => 'DiGiCo']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $named->id,
            'name' => 'QL5',
        ]);

        $this->actingAs($user)->put(route('manufacturers.models.update', [$named, $model]), [
            'name' => 'QL5',
            'manufacturer_id' => $other->id,
            'new_manufacturer' => 'Midas',
        ])->assertRedirect(route('manufacturers.index'));

        $this->assertSame($named->id, $model->fresh()->manufacturer_id);
        $this->assertDatabaseMissing('manufacturers', ['name' => 'Midas']);
    }
}

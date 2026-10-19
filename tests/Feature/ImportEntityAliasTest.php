<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\LocationAlias;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\User;
use App\Services\ImportService;
use App\Services\ManufacturerCatalogueService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportEntityAliasTest extends TestCase
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

    public function test_merging_locations_records_alias_for_case_variant(): void
    {
        $user = $this->manager();
        $keep = Location::create(['name' => 'Store A', 'type' => LocationType::Room]);
        $dup = Location::create(['name' => 'store a', 'type' => LocationType::Room]);

        $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'merge_duplicates',
            'location_ids' => [$keep->id, $dup->id],
        ])->assertRedirect(route('locations.index'));

        $this->assertDatabaseHas('location_aliases', [
            'former_name' => 'store a',
            'type' => LocationType::Room->value,
            'location_id' => $keep->id,
        ]);
    }

    public function test_import_maps_former_location_name_to_merged_survivor(): void
    {
        $user = $this->manager();
        ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        $keep = Location::create(['name' => 'Store A', 'type' => LocationType::Room]);
        LocationAlias::query()->create([
            'former_name' => 'store a',
            'type' => LocationType::Room,
            'location_id' => $keep->id,
        ]);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Room',
            'Speaker One,Audio,store a',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Room' => 'location_room',
            ],
        ])->assertOk();
        $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
        ])->assertRedirect(route('assets.index'));

        $asset = Asset::query()->where('name', 'Speaker One')->first();
        $this->assertNotNull($asset);
        $this->assertSame($keep->id, $asset->location_id);
        $this->assertDatabaseMissing('locations', ['name' => 'store a']);
    }

    public function test_deleting_manufacturer_records_alias_used_by_import(): void
    {
        $user = $this->manager();
        $keep = Manufacturer::factory()->create(['name' => 'KeepCo']);
        $delete = Manufacturer::factory()->create(['name' => 'DeleteCo']);
        ManufacturerModel::factory()->create([
            'manufacturer_id' => $delete->id,
            'name' => 'Only Model',
        ]);

        $this->actingAs($user)->delete(route('manufacturers.destroy', $delete), [
            'replacement_manufacturer_id' => $keep->id,
        ])->assertRedirect(route('manufacturers.index'));

        $this->assertDatabaseHas('manufacturer_aliases', [
            'former_name' => 'DeleteCo',
            'manufacturer_id' => $keep->id,
        ]);

        $resolved = app(ManufacturerCatalogueService::class)->resolveFromNames('DeleteCo', 'Only Model');
        $this->assertSame($keep->id, $resolved['manufacturer_id']);
        $this->assertDatabaseMissing('manufacturers', ['name' => 'DeleteCo']);
    }

    public function test_deleting_model_records_alias_used_by_import(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Yamaha']);
        $keep = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'QL1',
        ]);
        $delete = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'QL1 Old',
        ]);

        $this->actingAs($user)->delete(route('manufacturers.models.destroy', [$manufacturer, $delete]), [
            'replacement_manufacturer_model_id' => $keep->id,
        ])->assertRedirect(route('manufacturers.index'));

        $this->assertDatabaseHas('manufacturer_model_aliases', [
            'former_name' => 'QL1 Old',
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $keep->id,
        ]);

        $resolved = app(ManufacturerCatalogueService::class)->resolveFromNames('Yamaha', 'QL1 Old');
        $this->assertSame($keep->id, $resolved['manufacturer_model_id']);
        $this->assertDatabaseMissing('manufacturer_models', ['name' => 'QL1 Old']);
    }

    public function test_identical_name_location_merge_does_not_create_redundant_alias(): void
    {
        $user = $this->manager();
        $keep = Location::create(['name' => 'Warehouse', 'type' => LocationType::Site]);
        $dup = Location::create(['name' => 'Warehouse', 'type' => LocationType::Site]);

        $this->actingAs($user)->post(route('locations.bulk-update'), [
            'action' => 'merge_duplicates',
            'location_ids' => [$keep->id, $dup->id],
        ])->assertRedirect(route('locations.index'));

        $this->assertSame(0, LocationAlias::query()->count());
        $this->assertDatabaseMissing('locations', ['id' => $dup->id]);
    }

    public function test_location_resolve_prefers_live_match_over_alias(): void
    {
        $keep = Location::create(['name' => 'Store A', 'type' => LocationType::Room]);
        $live = Location::create(['name' => 'store a', 'type' => LocationType::Room]);
        LocationAlias::query()->create([
            'former_name' => 'store a',
            'type' => LocationType::Room,
            'location_id' => $keep->id,
        ]);

        $resolvedId = app(ImportService::class)->resolveLocation(null, null, 'store a', null);

        $this->assertSame($live->id, $resolvedId);
    }
}

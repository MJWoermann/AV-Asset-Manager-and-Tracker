<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Models\ItemType;
use App\Models\ItemTypeAlias;
use App\Models\Location;
use App\Models\LocationAlias;
use App\Models\Manufacturer;
use App\Models\ManufacturerAlias;
use App\Models\ManufacturerModel;
use App\Models\ManufacturerModelAlias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityAliasBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_type_alias_resolves_by_former_name_case_insensitively(): void
    {
        $type = ItemType::factory()->create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        ItemTypeAlias::query()->create([
            'former_name' => 'Lighting Fixture',
            'former_slug' => 'lighting-fixture',
            'item_type_id' => $type->id,
        ]);

        $resolved = ItemTypeAlias::resolveItemType('lighting fixture');

        $this->assertNotNull($resolved);
        $this->assertSame($type->id, $resolved->id);
    }

    public function test_item_type_record_merge_repoints_existing_aliases_and_records_former_name(): void
    {
        $from = ItemType::factory()->create([
            'name' => 'Old Type',
            'slug' => 'old-type',
        ]);
        $to = ItemType::factory()->create([
            'name' => 'New Type',
            'slug' => 'new-type',
        ]);
        ItemTypeAlias::query()->create([
            'former_name' => 'Ancient Type',
            'former_slug' => 'ancient-type',
            'item_type_id' => $from->id,
        ]);

        ItemTypeAlias::recordMerge($from, $to);

        $this->assertDatabaseHas('item_type_aliases', [
            'former_name' => 'Ancient Type',
            'item_type_id' => $to->id,
        ]);
        $this->assertDatabaseHas('item_type_aliases', [
            'former_name' => 'Old Type',
            'former_slug' => 'old-type',
            'item_type_id' => $to->id,
        ]);
        $this->assertSame($to->id, ItemTypeAlias::resolveItemType('Old Type')?->id);
    }

    public function test_item_type_forget_matching_removes_case_and_slug_variants(): void
    {
        $type = ItemType::factory()->create();
        ItemTypeAlias::query()->create([
            'former_name' => 'Spare Type',
            'former_slug' => 'spare-type',
            'item_type_id' => $type->id,
        ]);

        ItemTypeAlias::forgetMatching('spare type', 'spare-type');

        $this->assertSame(0, ItemTypeAlias::query()->count());
    }

    public function test_location_alias_resolves_by_former_name_within_type(): void
    {
        $room = Location::create([
            'name' => 'Store A',
            'type' => LocationType::Room,
        ]);
        LocationAlias::query()->create([
            'former_name' => 'store a',
            'type' => LocationType::Room,
            'location_id' => $room->id,
        ]);

        $resolved = LocationAlias::resolveLocation('STORE A', LocationType::Room);

        $this->assertNotNull($resolved);
        $this->assertSame($room->id, $resolved->id);
        $this->assertNull(LocationAlias::resolveLocation('STORE A', LocationType::Site));
    }

    public function test_location_record_merge_repoints_aliases_and_skips_same_identity(): void
    {
        $from = Location::create([
            'name' => 'store a',
            'type' => LocationType::Room,
        ]);
        $to = Location::create([
            'name' => 'Store A',
            'type' => LocationType::Room,
        ]);
        LocationAlias::query()->create([
            'former_name' => 'Warehouse Bay',
            'type' => LocationType::Room,
            'location_id' => $from->id,
        ]);

        LocationAlias::recordMerge($from, $to);

        $this->assertDatabaseHas('location_aliases', [
            'former_name' => 'Warehouse Bay',
            'location_id' => $to->id,
        ]);
        $this->assertDatabaseHas('location_aliases', [
            'former_name' => 'store a',
            'type' => LocationType::Room->value,
            'location_id' => $to->id,
        ]);

        $sameName = Location::create([
            'name' => 'Identical',
            'type' => LocationType::Site,
        ]);
        $sameSurvivor = Location::create([
            'name' => 'Identical',
            'type' => LocationType::Site,
        ]);
        $before = LocationAlias::query()->count();

        LocationAlias::recordMerge($sameName, $sameSurvivor);

        $this->assertSame($before, LocationAlias::query()->count());
    }

    public function test_location_forget_matching_is_scoped_by_type(): void
    {
        $room = Location::create(['name' => 'Room One', 'type' => LocationType::Room]);
        $site = Location::create(['name' => 'Site One', 'type' => LocationType::Site]);
        LocationAlias::query()->create([
            'former_name' => 'Shared Name',
            'type' => LocationType::Room,
            'location_id' => $room->id,
        ]);
        LocationAlias::query()->create([
            'former_name' => 'Shared Name',
            'type' => LocationType::Site,
            'location_id' => $site->id,
        ]);

        LocationAlias::forgetMatching('shared name', LocationType::Room);

        $this->assertSame(0, LocationAlias::query()->where('type', LocationType::Room)->count());
        $this->assertSame(1, LocationAlias::query()->where('type', LocationType::Site)->count());
    }

    public function test_manufacturer_alias_resolves_and_record_merge_repoints(): void
    {
        $from = Manufacturer::factory()->create(['name' => 'Acme Old']);
        $to = Manufacturer::factory()->create(['name' => 'Acme']);
        ManufacturerAlias::query()->create([
            'former_name' => 'Acme Corp',
            'manufacturer_id' => $from->id,
        ]);

        ManufacturerAlias::recordMerge($from, $to);

        $this->assertDatabaseHas('manufacturer_aliases', [
            'former_name' => 'Acme Corp',
            'manufacturer_id' => $to->id,
        ]);
        $this->assertDatabaseHas('manufacturer_aliases', [
            'former_name' => 'Acme Old',
            'manufacturer_id' => $to->id,
        ]);
        $this->assertSame($to->id, ManufacturerAlias::resolveManufacturer('acme old')?->id);
    }

    public function test_manufacturer_forget_matching_removes_case_variants(): void
    {
        $manufacturer = Manufacturer::factory()->create();
        ManufacturerAlias::query()->create([
            'former_name' => 'Legacy Brand',
            'manufacturer_id' => $manufacturer->id,
        ]);

        ManufacturerAlias::forgetMatching('legacy brand');

        $this->assertSame(0, ManufacturerAlias::query()->count());
    }

    public function test_manufacturer_record_merge_skips_alias_when_names_match(): void
    {
        $from = Manufacturer::factory()->create(['name' => 'Acme A']);
        $to = Manufacturer::factory()->create(['name' => 'Acme B']);
        // Names are unique in the DB; recordMerge compares in-memory attributes
        // (e.g. after a rename that already aligned the labels).
        $from->name = $to->name;
        $before = ManufacturerAlias::query()->count();

        ManufacturerAlias::recordMerge($from, $to);

        $this->assertSame($before, ManufacturerAlias::query()->count());
    }

    public function test_manufacturer_model_alias_resolves_within_manufacturer_scope(): void
    {
        $manufacturer = Manufacturer::factory()->create();
        $other = Manufacturer::factory()->create();
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Widget Pro',
        ]);
        ManufacturerModelAlias::query()->create([
            'former_name' => 'Widget',
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
        ]);

        $resolved = ManufacturerModelAlias::resolveModel($manufacturer, 'widget');

        $this->assertNotNull($resolved);
        $this->assertSame($model->id, $resolved->id);
        $this->assertNull(ManufacturerModelAlias::resolveModel($other, 'widget'));
    }

    public function test_manufacturer_model_record_merge_repoints_and_updates_manufacturer_id(): void
    {
        $oldMfr = Manufacturer::factory()->create();
        $newMfr = Manufacturer::factory()->create();
        $from = ManufacturerModel::factory()->create([
            'manufacturer_id' => $oldMfr->id,
            'name' => 'Old Model',
        ]);
        $to = ManufacturerModel::factory()->create([
            'manufacturer_id' => $newMfr->id,
            'name' => 'New Model',
        ]);
        ManufacturerModelAlias::query()->create([
            'former_name' => 'Ancient Model',
            'manufacturer_id' => $oldMfr->id,
            'manufacturer_model_id' => $from->id,
        ]);

        ManufacturerModelAlias::recordMerge($from, $to);

        $this->assertDatabaseHas('manufacturer_model_aliases', [
            'former_name' => 'Ancient Model',
            'manufacturer_model_id' => $to->id,
            'manufacturer_id' => $newMfr->id,
        ]);
        $this->assertDatabaseHas('manufacturer_model_aliases', [
            'former_name' => 'Old Model',
            'manufacturer_model_id' => $to->id,
            'manufacturer_id' => $newMfr->id,
        ]);
        $this->assertSame($to->id, ManufacturerModelAlias::resolveModel($newMfr, 'Old Model')?->id);
    }

    public function test_manufacturer_model_forget_matching_is_scoped_by_manufacturer(): void
    {
        $mfrA = Manufacturer::factory()->create();
        $mfrB = Manufacturer::factory()->create();
        $modelA = ManufacturerModel::factory()->create(['manufacturer_id' => $mfrA->id]);
        $modelB = ManufacturerModel::factory()->create(['manufacturer_id' => $mfrB->id]);
        ManufacturerModelAlias::query()->create([
            'former_name' => 'Shared',
            'manufacturer_id' => $mfrA->id,
            'manufacturer_model_id' => $modelA->id,
        ]);
        ManufacturerModelAlias::query()->create([
            'former_name' => 'Shared',
            'manufacturer_id' => $mfrB->id,
            'manufacturer_model_id' => $modelB->id,
        ]);

        ManufacturerModelAlias::forgetMatching('shared', $mfrA->id);

        $this->assertSame(0, ManufacturerModelAlias::query()->where('manufacturer_id', $mfrA->id)->count());
        $this->assertSame(1, ManufacturerModelAlias::query()->where('manufacturer_id', $mfrB->id)->count());
    }
}

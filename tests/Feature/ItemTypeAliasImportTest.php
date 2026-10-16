<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ItemType;
use App\Models\ItemTypeAlias;
use App\Models\User;
use App\Services\ImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ItemTypeAliasImportTest extends TestCase
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

    public function test_deleting_item_type_records_alias_for_replacement(): void
    {
        $user = $this->manager();
        $keep = ItemType::create(['name' => 'Lighting Fixture', 'slug' => 'lighting-fixture']);
        $delete = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);

        $this->actingAs($user)->delete(route('item-types.destroy', $delete), [
            'replacement_item_type_id' => $keep->id,
        ])->assertRedirect(route('item-types.index'));

        $this->assertDatabaseHas('item_type_aliases', [
            'former_name' => 'LX Fixture',
            'former_slug' => 'lx-fixture',
            'item_type_id' => $keep->id,
        ]);
    }

    public function test_chained_deletes_remap_earlier_aliases_to_final_type(): void
    {
        $user = $this->manager();
        $final = ItemType::create(['name' => 'Final Type', 'slug' => 'final-type']);
        $middle = ItemType::create(['name' => 'Middle Type', 'slug' => 'middle-type']);
        $first = ItemType::create(['name' => 'First Type', 'slug' => 'first-type']);

        $this->actingAs($user)->delete(route('item-types.destroy', $first), [
            'replacement_item_type_id' => $middle->id,
        ])->assertRedirect(route('item-types.index'));

        $this->actingAs($user)->delete(route('item-types.destroy', $middle), [
            'replacement_item_type_id' => $final->id,
        ])->assertRedirect(route('item-types.index'));

        $this->assertDatabaseHas('item_type_aliases', [
            'former_name' => 'First Type',
            'item_type_id' => $final->id,
        ]);
        $this->assertDatabaseHas('item_type_aliases', [
            'former_name' => 'Middle Type',
            'item_type_id' => $final->id,
        ]);
    }

    public function test_import_maps_former_item_type_name_to_replacement(): void
    {
        $user = $this->manager();
        $keep = ItemType::create(['name' => 'Lighting Fixture', 'slug' => 'lighting-fixture']);
        ItemTypeAlias::query()->create([
            'former_name' => 'LX Fixture',
            'former_slug' => 'lx-fixture',
            'item_type_id' => $keep->id,
        ]);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type',
            'Moving Head,LX Fixture',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
            ],
        ])->assertOk();

        $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
        ])->assertRedirect(route('assets.index'));

        $asset = Asset::query()->where('name', 'Moving Head')->first();
        $this->assertNotNull($asset);
        $this->assertSame($keep->id, $asset->item_type_id);
        $this->assertDatabaseMissing('item_types', ['name' => 'LX Fixture']);
    }

    public function test_resolve_item_type_prefers_live_type_over_alias(): void
    {
        $keep = ItemType::create(['name' => 'Lighting Fixture', 'slug' => 'lighting-fixture']);
        $recreated = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);

        // Simulate a stale alias that shares the live type's name.
        ItemTypeAlias::query()->create([
            'former_name' => 'LX Fixture',
            'former_slug' => 'lx-fixture-alias',
            'item_type_id' => $keep->id,
        ]);

        $resolved = app(ImportService::class)->resolveItemType('LX Fixture');

        $this->assertSame($recreated->id, $resolved->id);
    }

    public function test_edit_form_lists_former_names(): void
    {
        $user = $this->manager();
        $keep = ItemType::create(['name' => 'Lighting Fixture', 'slug' => 'lighting-fixture']);
        ItemTypeAlias::query()->create([
            'former_name' => 'LX Fixture',
            'former_slug' => 'lx-fixture',
            'item_type_id' => $keep->id,
        ]);

        $response = $this->actingAs($user)->get(route('item-types.edit', $keep));

        $response->assertOk();
        $response->assertSee('Former names (import aliases)', false);
        $response->assertSee('LX Fixture', false);
    }
}

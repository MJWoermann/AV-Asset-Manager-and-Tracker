<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\User;
use App\Services\AssetMergeService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetDuplicateMergeTest extends TestCase
{
    use RefreshDatabase;

    protected ItemType $itemType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
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

    protected function makeAsset(array $attributes = []): Asset
    {
        return Asset::create(array_merge([
            'name' => 'Test Asset',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
        ], $attributes));
    }

    /**
     * @param  list<Asset>  $assets
     * @return array<string, string>
     */
    protected function defaultFieldSources(array $assets, int $survivorId): array
    {
        $collection = collect($assets);
        $defaults = app(AssetMergeService::class)->defaultFieldSources($collection, $survivorId);

        return $defaults['fields'];
    }

    public function test_duplicate_index_groups_case_insensitive_barcode_matches(): void
    {
        $user = $this->manager();
        $a = $this->makeAsset(['name' => 'Alpha', 'tp_barcode' => 'TP-ABC']);
        $b = $this->makeAsset(['name' => 'Beta', 'tp_barcode' => 'tp-abc']);
        $this->makeAsset(['name' => 'Unique', 'tp_barcode' => 'TP-OTHER']);

        $response = $this->actingAs($user)->get(route('assets.duplicates.index'));

        $response->assertOk();
        $response->assertSee('Duplicate check');
        $response->assertSee('Alpha');
        $response->assertSee('Beta');
        $response->assertDontSee('Unique', false);
        $response->assertSee('1 group');
    }

    public function test_scanner_cannot_view_or_merge_duplicates(): void
    {
        $user = $this->scanner();
        $a = $this->makeAsset(['tp_barcode' => 'TP-1']);
        $b = $this->makeAsset(['tp_barcode' => 'TP-1']);

        $this->actingAs($user)->get(route('assets.duplicates.index'))->assertForbidden();
        $this->actingAs($user)->get(route('assets.duplicates.merge', [
            'asset_ids' => [$a->id, $b->id],
        ]))->assertForbidden();
        $this->actingAs($user)->post(route('assets.duplicates.merge.store'), [
            'asset_ids' => [$a->id, $b->id],
            'survivor_id' => $a->id,
            'confirm_merge' => '1',
            'fields' => $this->defaultFieldSources([$a, $b], $a->id),
        ])->assertForbidden();
    }

    public function test_merge_form_renders_side_by_side_fields(): void
    {
        $user = $this->manager();
        $a = $this->makeAsset(['name' => 'Keep', 'tp_barcode' => 'TP-SAME', 'serial_number' => 'SN-A']);
        $b = $this->makeAsset(['name' => 'Drop', 'tp_barcode' => 'TP-SAME', 'serial_number' => 'SN-B']);

        $response = $this->actingAs($user)->get(route('assets.duplicates.merge', [
            'asset_ids' => [$a->id, $b->id],
        ]));

        $response->assertOk();
        $response->assertSee('Merge assets');
        $response->assertSee('Conflict');
        $response->assertSee('SN-A');
        $response->assertSee('SN-B');
        $response->assertSee('Merge permanently');
    }

    public function test_merge_applies_field_selection_and_removes_non_survivors(): void
    {
        $user = $this->manager();
        $list = AssetList::create([
            'name' => 'Inventory',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $survivor = $this->makeAsset([
            'name' => 'Survivor',
            'tp_barcode' => 'TP-KEEP',
            'serial_number' => null,
            'notes' => 'Keep notes',
        ]);
        $duplicate = $this->makeAsset([
            'name' => 'Duplicate',
            'tp_barcode' => 'TP-DROP',
            'serial_number' => 'SN-FROM-DUP',
            'notes' => 'Dup notes',
        ]);
        $child = $this->makeAsset([
            'name' => 'Child',
            'parent_id' => $duplicate->id,
        ]);

        AssetListItem::create([
            'asset_list_id' => $list->id,
            'asset_id' => $duplicate->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $fields = $this->defaultFieldSources([$survivor, $duplicate], $survivor->id);
        $fields['serial_number'] = (string) $duplicate->id;
        $fields['tp_barcode'] = (string) $survivor->id;
        $fields['notes'] = (string) $duplicate->id;
        $fields['name'] = (string) $survivor->id;

        $response = $this->actingAs($user)->post(route('assets.duplicates.merge.store'), [
            'asset_ids' => [$survivor->id, $duplicate->id],
            'survivor_id' => $survivor->id,
            'confirm_merge' => '1',
            'fields' => $fields,
        ]);

        $response->assertRedirect(route('assets.show', $survivor));
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('assets', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('assets', [
            'id' => $survivor->id,
            'tp_barcode' => 'TP-KEEP',
            'serial_number' => 'SN-FROM-DUP',
            'notes' => 'Dup notes',
            'name' => 'Survivor',
        ]);
        $this->assertSame($survivor->id, $child->fresh()->parent_id);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $survivor->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $survivor->id,
            'action' => 'merged',
        ]);
    }

    public function test_merge_applies_custom_field_selection(): void
    {
        $user = $this->manager();
        $fieldSet = CustomFieldSet::create([
            'name' => 'Extra',
            'slug' => 'extra',
        ]);
        $definition = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Colour',
            'slug' => 'colour',
            'type' => 'text',
        ]);
        $this->itemType->fieldSets()->attach($fieldSet->id);

        $survivor = $this->makeAsset(['name' => 'A', 'tp_barcode' => 'X']);
        $duplicate = $this->makeAsset(['name' => 'B', 'tp_barcode' => 'X']);

        AssetCustomFieldValue::create([
            'asset_id' => $survivor->id,
            'custom_field_definition_id' => $definition->id,
            'value' => 'Red',
        ]);
        AssetCustomFieldValue::create([
            'asset_id' => $duplicate->id,
            'custom_field_definition_id' => $definition->id,
            'value' => 'Blue',
        ]);

        $fields = $this->defaultFieldSources([$survivor, $duplicate], $survivor->id);

        $response = $this->actingAs($user)->post(route('assets.duplicates.merge.store'), [
            'asset_ids' => [$survivor->id, $duplicate->id],
            'survivor_id' => $survivor->id,
            'confirm_merge' => '1',
            'fields' => $fields,
            'custom_fields' => [
                $definition->id => (string) $duplicate->id,
            ],
        ]);

        $response->assertRedirect(route('assets.show', $survivor));
        $this->assertDatabaseHas('asset_custom_field_values', [
            'asset_id' => $survivor->id,
            'custom_field_definition_id' => $definition->id,
            'value' => 'Blue',
        ]);
        $this->assertDatabaseMissing('assets', ['id' => $duplicate->id]);
    }

    public function test_merge_rejects_more_than_six_assets(): void
    {
        $user = $this->manager();
        $assets = [];
        for ($i = 0; $i < 7; $i++) {
            $assets[] = $this->makeAsset(['name' => 'A'.$i, 'tp_barcode' => 'SHARED']);
        }

        $ids = collect($assets)->pluck('id')->all();

        $response = $this->actingAs($user)->from(route('assets.duplicates.index'))->get(route('assets.duplicates.merge', [
            'asset_ids' => $ids,
        ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('asset_ids');
    }

    public function test_bulk_start_redirects_into_merge_form(): void
    {
        $user = $this->manager();
        $a = $this->makeAsset(['name' => 'One']);
        $b = $this->makeAsset(['name' => 'Two']);

        $response = $this->actingAs($user)->post(route('assets.duplicates.start'), [
            'asset_ids' => [$a->id, $b->id],
        ]);

        $response->assertRedirect(route('assets.duplicates.merge', [
            'asset_ids' => [$a->id, $b->id],
        ]));
    }

    public function test_assets_index_links_to_duplicate_check(): void
    {
        $user = $this->manager();

        $this->actingAs($user)->get(route('assets.index'))
            ->assertOk()
            ->assertSee('Duplicates')
            ->assertSee(route('assets.duplicates.index'), false);
    }
}

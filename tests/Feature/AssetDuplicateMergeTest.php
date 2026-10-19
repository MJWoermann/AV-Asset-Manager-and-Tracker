<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\AssetMergeAlias;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetMergeService;
use App\Services\ImportService;
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
        $response->assertSee('TP-ABC');
        $response->assertSee('1 group');
        $response->assertDontSee('>Unique<', false);
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
        $this->actingAs($user)->post(route('assets.duplicates.clear'), [
            'asset_ids' => [$a->id],
            'fields' => ['tp_barcode'],
            'confirm_clear' => '1',
        ])->assertForbidden();
        $this->actingAs($user)->post(route('assets.duplicates.parent-child'), [
            'asset_ids' => [$a->id, $b->id],
            'parent_id' => $a->id,
            'confirm_parent_child' => '1',
        ])->assertForbidden();
    }

    public function test_clear_identifier_fields_from_selected_assets_shrinks_duplicate_group(): void
    {
        $user = $this->manager();
        $keep = $this->makeAsset(['name' => 'Keep SN', 'serial_number' => 'SN-SHARED']);
        $clearA = $this->makeAsset(['name' => 'Clear A', 'serial_number' => 'SN-SHARED']);
        $clearB = $this->makeAsset(['name' => 'Clear B', 'serial_number' => 'SN-SHARED']);

        $this->actingAs($user)->get(route('assets.duplicates.index'))
            ->assertOk()
            ->assertSee('1 group')
            ->assertSee('Clear fields');

        $response = $this->actingAs($user)->post(route('assets.duplicates.clear'), [
            'asset_ids' => [$clearA->id, $clearB->id],
            'fields' => ['serial_number'],
            'confirm_clear' => '1',
        ]);

        $response->assertRedirect(route('assets.duplicates.index'));
        $response->assertSessionHas('status');

        $this->assertNull($clearA->fresh()->serial_number);
        $this->assertNull($clearB->fresh()->serial_number);
        $this->assertSame('SN-SHARED', $keep->fresh()->serial_number);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $clearA->id,
            'action' => 'cleared',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $clearB->id,
            'action' => 'cleared',
        ]);

        $this->actingAs($user)->get(route('assets.duplicates.index'))
            ->assertOk()
            ->assertSee('0 groups')
            ->assertSee('No duplicates found');
    }

    public function test_clear_requires_confirmation_and_valid_identifier_fields(): void
    {
        $user = $this->manager();
        $a = $this->makeAsset(['serial_number' => 'SN-1']);
        $b = $this->makeAsset(['serial_number' => 'SN-1']);

        $response = $this->actingAs($user)->from(route('assets.duplicates.index'))->post(route('assets.duplicates.clear'), [
            'asset_ids' => [$a->id],
            'fields' => ['serial_number'],
        ]);
        $response->assertRedirect(route('assets.duplicates.index'));
        $response->assertSessionHasErrors('confirm_clear');
        $this->assertSame('SN-1', $a->fresh()->serial_number);

        $response = $this->actingAs($user)->from(route('assets.duplicates.index'))->post(route('assets.duplicates.clear'), [
            'asset_ids' => [$a->id, $b->id],
            'fields' => ['name'],
            'confirm_clear' => '1',
        ]);
        $response->assertRedirect(route('assets.duplicates.index'));
        $response->assertSessionHasErrors('fields.0');
        $this->assertSame('SN-1', $a->fresh()->serial_number);
        $this->assertSame('Test Asset', $a->fresh()->name);
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
        $response->assertSee('Set quantity to total of merged assets');
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

    public function test_parent_child_links_selected_assets_and_inherits_location(): void
    {
        $user = $this->manager();
        $location = Location::create([
            'name' => 'Warehouse A',
            'type' => LocationType::Building,
        ]);

        $parent = $this->makeAsset([
            'name' => 'Rack',
            'tp_barcode' => 'TP-PC',
            'location_id' => $location->id,
        ]);
        $child = $this->makeAsset([
            'name' => 'Fixture',
            'tp_barcode' => 'TP-PC',
            'location_id' => null,
        ]);

        $response = $this->actingAs($user)->post(route('assets.duplicates.parent-child'), [
            'asset_ids' => [$parent->id, $child->id],
            'parent_id' => $parent->id,
            'confirm_parent_child' => '1',
        ]);

        $response->assertRedirect(route('assets.duplicates.index'));
        $response->assertSessionHas('status');

        $child->refresh();
        $this->assertSame($parent->id, (int) $child->parent_id);
        $this->assertSame($location->id, (int) $child->location_id);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $child->id,
            'action' => 'reparented',
        ]);
    }

    public function test_merge_can_set_survivor_quantity_to_total_of_merged_assets(): void
    {
        $user = $this->manager();
        $survivor = $this->makeAsset([
            'name' => 'Keep Qty',
            'tp_barcode' => 'TP-QTY',
            'quantity' => 2,
        ]);
        $duplicate = $this->makeAsset([
            'name' => 'Drop Qty',
            'tp_barcode' => 'TP-QTY',
            'quantity' => 3,
        ]);

        $fields = $this->defaultFieldSources([$survivor, $duplicate], $survivor->id);

        $response = $this->actingAs($user)->post(route('assets.duplicates.merge.store'), [
            'asset_ids' => [$survivor->id, $duplicate->id],
            'survivor_id' => $survivor->id,
            'confirm_merge' => '1',
            'set_quantity_to_total' => '1',
            'fields' => $fields,
        ]);

        $response->assertRedirect(route('assets.show', $survivor));
        $this->assertDatabaseMissing('assets', ['id' => $duplicate->id]);
        $this->assertSame(5, (int) $survivor->fresh()->quantity);
    }

    public function test_duplicate_index_filters_by_field_and_multi_field(): void
    {
        $user = $this->manager();

        // Group matched only on barcode.
        $this->makeAsset(['name' => 'Bar A', 'tp_barcode' => 'TP-ONLY']);
        $this->makeAsset(['name' => 'Bar B', 'tp_barcode' => 'TP-ONLY']);

        // Group matched on barcode + serial.
        $this->makeAsset([
            'name' => 'Multi A',
            'tp_barcode' => 'TP-MULTI',
            'serial_number' => 'SN-MULTI',
        ]);
        $this->makeAsset([
            'name' => 'Multi B',
            'tp_barcode' => 'TP-MULTI',
            'serial_number' => 'SN-MULTI',
        ]);

        $this->actingAs($user)->get(route('assets.duplicates.index', ['field' => 'serial_number']))
            ->assertOk()
            ->assertSee('Multi A')
            ->assertDontSee('Bar A');

        $this->actingAs($user)->get(route('assets.duplicates.index', ['multi' => '1']))
            ->assertOk()
            ->assertSee('Multi A')
            ->assertDontSee('Bar A');

        $this->actingAs($user)->get(route('assets.duplicates.index', [
            'field' => 'tp_barcode',
            'multi' => '1',
        ]))
            ->assertOk()
            ->assertSee('Multi A')
            ->assertDontSee('Bar A');
    }

    public function test_default_duplicate_search_excludes_parent_child_identifier_matches(): void
    {
        $user = $this->manager();

        $parent = $this->makeAsset([
            'name' => 'Rack Parent',
            'serial_number' => 'SN-RELATED',
        ]);
        $this->makeAsset([
            'name' => 'Child Fixture',
            'serial_number' => 'SN-RELATED',
            'parent_id' => $parent->id,
        ]);

        $this->makeAsset([
            'name' => 'Loose Alpha',
            'tp_barcode' => 'TP-LOOSE',
        ]);
        $this->makeAsset([
            'name' => 'Loose Beta',
            'tp_barcode' => 'TP-LOOSE',
        ]);

        // Siblings under the same parent still count as duplicates by default.
        $kit = $this->makeAsset(['name' => 'Kit Parent']);
        $this->makeAsset([
            'name' => 'Sibling One',
            'fmi_ast' => 'FMI-SIB',
            'parent_id' => $kit->id,
        ]);
        $this->makeAsset([
            'name' => 'Sibling Two',
            'fmi_ast' => 'FMI-SIB',
            'parent_id' => $kit->id,
        ]);

        $default = $this->actingAs($user)->get(route('assets.duplicates.index'));
        $default->assertOk();
        $default->assertSee('excluding parent/child matches');
        $default->assertSee('Search including parent/child');
        $default->assertSee('2 groups');
        $default->assertSee('Loose Alpha');
        $default->assertSee('Loose Beta');
        $default->assertSee('Sibling One');
        $default->assertSee('Sibling Two');
        $default->assertDontSee('Rack Parent');
        $default->assertDontSee('Child Fixture');

        $included = $this->actingAs($user)->get(route('assets.duplicates.index', [
            'include_related' => '1',
        ]));
        $included->assertOk();
        $included->assertSee('including parent/child matches');
        $included->assertSee('Hide parent/child matches');
        $included->assertSee('3 groups');
        $included->assertSee('Rack Parent');
        $included->assertSee('Child Fixture');
        $included->assertSee('Loose Alpha');

        // Field filter still works in include mode.
        $this->actingAs($user)->get(route('assets.duplicates.index', [
            'include_related' => '1',
            'field' => 'serial_number',
        ]))
            ->assertOk()
            ->assertSee('Rack Parent')
            ->assertDontSee('Loose Alpha');
    }

    public function test_merge_records_aliases_and_reimport_matches_survivor(): void
    {
        $user = $this->manager();
        $survivor = $this->makeAsset([
            'name' => 'Survivor',
            'tp_barcode' => 'TP-SURVIVE',
            'serial_number' => 'SN-KEEP',
        ]);
        $duplicate = $this->makeAsset([
            'name' => 'Absorbed',
            'tp_barcode' => 'TP-ABSORB',
            'serial_number' => 'SN-ABSORB',
            'mac_address' => 'AA:BB:CC:DD:EE:11',
        ]);

        $fields = $this->defaultFieldSources([$survivor, $duplicate], $survivor->id);
        // Keep survivor barcode/serial; do not take absorbed identifiers onto survivor.
        $fields['tp_barcode'] = (string) $survivor->id;
        $fields['serial_number'] = (string) $survivor->id;
        $fields['mac_address'] = (string) $survivor->id;
        $fields['name'] = (string) $survivor->id;

        $this->actingAs($user)->post(route('assets.duplicates.merge.store'), [
            'asset_ids' => [$survivor->id, $duplicate->id],
            'survivor_id' => $survivor->id,
            'confirm_merge' => '1',
            'fields' => $fields,
        ])->assertRedirect(route('assets.show', $survivor));

        $this->assertDatabaseMissing('assets', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('asset_merge_aliases', [
            'survivor_asset_id' => $survivor->id,
            'merged_asset_id' => $duplicate->id,
            'field' => 'tp_barcode',
            'value' => 'TP-ABSORB',
        ]);
        $this->assertDatabaseHas('asset_merge_aliases', [
            'survivor_asset_id' => $survivor->id,
            'field' => 'serial_number',
            'value' => 'SN-ABSORB',
        ]);
        $this->assertDatabaseHas('asset_merge_aliases', [
            'survivor_asset_id' => $survivor->id,
            'field' => 'mac_address',
            'value' => 'AA:BB:CC:DD:EE:11',
        ]);

        $rows = app(ImportService::class)->prepareRows(collect([
            collect([
                'name' => 'Reuploaded Absorbed',
                'type' => 'LX Fixture',
                'barcode' => 'TP-ABSORB',
            ]),
            collect([
                'name' => 'Reuploaded Serial',
                'type' => 'LX Fixture',
                'serial' => 'SN-ABSORB',
            ]),
        ]), [
            'name' => 'name',
            'type' => 'item_type',
            'barcode' => 'tp_barcode',
            'serial' => 'serial_number',
        ]);

        $this->assertSame($survivor->id, $rows[0]['existing_id']);
        $this->assertContains('tp_barcode', $rows[0]['matched_on']);
        $this->assertSame($survivor->id, $rows[1]['existing_id']);
        $this->assertContains('serial_number', $rows[1]['matched_on']);

        // Absorbed identifier must not create a fresh duplicate group against a new asset.
        $this->assertSame(1, Asset::query()->where('tp_barcode', 'TP-SURVIVE')->count());
        $this->assertSame(0, Asset::query()->where('tp_barcode', 'TP-ABSORB')->count());
        $this->assertSame(
            $survivor->id,
            AssetMergeAlias::resolveSurvivorId('tp_barcode', 'TP-ABSORB'),
        );
    }

    public function test_deleting_survivor_removes_merge_aliases(): void
    {
        $user = $this->manager();
        $survivor = $this->makeAsset(['name' => 'Keep', 'tp_barcode' => 'TP-DEL-S']);
        $duplicate = $this->makeAsset(['name' => 'Drop', 'tp_barcode' => 'TP-DEL-D']);

        $fields = $this->defaultFieldSources([$survivor, $duplicate], $survivor->id);
        $this->actingAs($user)->post(route('assets.duplicates.merge.store'), [
            'asset_ids' => [$survivor->id, $duplicate->id],
            'survivor_id' => $survivor->id,
            'confirm_merge' => '1',
            'fields' => $fields,
        ])->assertRedirect();

        $this->assertDatabaseHas('asset_merge_aliases', [
            'survivor_asset_id' => $survivor->id,
            'field' => 'tp_barcode',
            'value' => 'TP-DEL-D',
        ]);

        $survivor->delete();

        $this->assertDatabaseMissing('asset_merge_aliases', [
            'survivor_asset_id' => $survivor->id,
        ]);
        $this->assertNull(AssetMergeAlias::resolveSurvivorId('tp_barcode', 'TP-DEL-D'));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetRuPlacement;
use App\Models\AuditLog;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuVisualiserFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function withFeature(User $user): User
    {
        return $this->enableFeature($user, 'ru_visualiser');
    }

    protected function manager(bool $feature = true): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('inventory_manager');

        return $feature ? $this->withFeature($user) : $user;
    }

    protected function scanner(bool $feature = true): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('scanner');

        return $feature ? $this->withFeature($user) : $user;
    }

    protected function makeRackType(bool $supports = true): ItemType
    {
        return ItemType::query()->create([
            'name' => 'Rack Type '.fake()->unique()->numerify('###'),
            'slug' => 'rack-type-'.fake()->unique()->numerify('###'),
            'supports_ru_visualiser' => $supports,
        ]);
    }

    protected function makeRackAsset(
        ?ItemType $itemType = null,
        int $ruHeight = 42,
        bool $rearEnabled = false,
    ): Asset {
        $itemType ??= $this->makeRackType();

        return Asset::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'Test Rack',
            'status' => AssetStatus::Available,
            'ru_height' => $ruHeight,
            'ru_rear_enabled' => $rearEnabled,
        ]);
    }

    protected function makeChildAsset(?ItemType $itemType = null, ?int $parentId = null, ?int $ruHeight = 1): Asset
    {
        $itemType ??= ItemType::query()->create([
            'name' => 'Child Type '.fake()->unique()->numerify('###'),
            'slug' => 'child-type-'.fake()->unique()->numerify('###'),
        ]);

        return Asset::query()->create([
            'item_type_id' => $itemType->id,
            'parent_id' => $parentId,
            'name' => 'Child Asset '.fake()->unique()->numerify('###'),
            'status' => AssetStatus::Available,
            'ru_height' => $ruHeight,
        ]);
    }

    public function test_flag_off_returns_404_for_placement_routes_and_hides_rack_tab(): void
    {
        $user = $this->manager(feature: false);
        $rack = $this->makeRackAsset();

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 1,
                'ru_height' => 1,
                'kind' => 'note',
                'note' => 'Patch panel',
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertOk()
            ->assertDontSee('>Rack</a>', false)
            ->assertDontSee('Add placement', false);
    }

    public function test_flag_on_and_type_support_shows_rack_tab(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset();

        $this->actingAs($user)
            ->get(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertOk()
            ->assertSee('Rack', false)
            ->assertSee('Add placement', false)
            ->assertSee('Width', false)
            ->assertSee('Half', false)
            ->assertSee('Third', false)
            ->assertSee('Move the whole row', false);
    }

    public function test_rack_tab_hidden_when_type_does_not_support_ru_visualiser(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset($this->makeRackType(supports: false));

        $this->actingAs($user)
            ->get(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertOk()
            ->assertDontSee('>Rack</a>', false)
            ->assertDontSee('Add placement', false);
    }

    public function test_scanner_cannot_mutate_ru_placements(): void
    {
        $user = $this->scanner();
        $rack = $this->makeRackAsset();

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 2,
                'kind' => 'note',
                'note' => 'Blanking panel',
            ])
            ->assertForbidden();

        $this->assertSame(0, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_can_place_note_on_rack(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset();

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 2,
                'kind' => 'note',
                'note' => 'Blanking panel',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']));

        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'face' => 'front',
            'start_ru' => 3,
            'ru_height' => 2,
            'kind' => 'note',
            'note' => 'Blanking panel',
            'child_asset_id' => null,
        ]);
    }

    public function test_placing_child_sets_parent_id(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset();
        $child = $this->makeChildAsset(ruHeight: 2);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 1,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']));

        $this->assertSame($rack->id, $child->fresh()->parent_id);
        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'kind' => 'asset',
            'child_asset_id' => $child->id,
            'ru_height' => 2,
        ]);
    }

    public function test_reparent_without_confirm_is_rejected(): void
    {
        $user = $this->manager();
        $existingParent = $this->makeRackAsset();
        $rack = $this->makeRackAsset();
        $child = $this->makeChildAsset(parentId: $existingParent->id);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 1,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors('confirm_parent_change');

        $this->assertSame($existingParent->id, $child->fresh()->parent_id);
        $this->assertSame(0, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_reparent_with_confirm_succeeds(): void
    {
        $user = $this->manager();
        $existingParent = $this->makeRackAsset();
        $rack = $this->makeRackAsset();
        $child = $this->makeChildAsset(parentId: $existingParent->id);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 1,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
                'confirm_parent_change' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertSame($rack->id, $child->fresh()->parent_id);
        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'child_asset_id' => $child->id,
        ]);
    }

    public function test_overlap_placement_is_rejected(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 10);

        AssetRuPlacement::query()->create([
            'rack_asset_id' => $rack->id,
            'face' => 'front',
            'start_ru' => 3,
            'ru_height' => 2,
            'kind' => 'note',
            'note' => 'Existing',
        ]);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'ru_height' => 2,
                'kind' => 'note',
                'note' => 'Overlap',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors('start_ru');

        $this->assertSame(1, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_out_of_range_placement_is_rejected(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 10);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 9,
                'ru_height' => 3,
                'kind' => 'note',
                'note' => 'Too tall',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors('start_ru');

        $this->assertSame(0, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_rear_placement_rejected_when_rear_disabled(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(rearEnabled: false);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 1,
                'ru_height' => 1,
                'kind' => 'note',
                'note' => 'Rear note',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors('face');

        $this->assertSame(0, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_native_ru_height_seeded_from_custom_field_when_null(): void
    {
        $user = $this->manager();
        $itemType = $this->makeRackType();
        $fieldSet = CustomFieldSet::query()->create([
            'name' => 'Rack Dimensions',
            'slug' => 'rack-dimensions-'.fake()->unique()->numerify('###'),
        ]);
        $definition = CustomFieldDefinition::query()->create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'RU',
            'slug' => 'ru',
            'type' => 'text',
            'sort_order' => 0,
        ]);
        $itemType->fieldSets()->attach($fieldSet);

        $asset = Asset::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'Unseeded Rack',
            'status' => AssetStatus::Available,
            'ru_height' => null,
            'ru_rear_enabled' => false,
        ]);

        AssetCustomFieldValue::query()->create([
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $definition->id,
            'value' => '24 RU',
        ]);

        $this->assertNull($asset->fresh()->ru_height);

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'rack']))
            ->assertOk()
            ->assertSee('Rack', false);

        $this->assertSame(24, $asset->fresh()->ru_height);
    }

    public function test_removing_placement_keeps_child_parent_and_lists_as_unplaced(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset();
        $child = $this->makeChildAsset(ruHeight: 1);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 1,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']));

        $placement = AssetRuPlacement::query()
            ->where('rack_asset_id', $rack->id)
            ->where('child_asset_id', $child->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->delete(route('assets.ru-placements.destroy', [$rack, $placement]))
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']));

        $this->assertDatabaseMissing('asset_ru_placements', ['id' => $placement->id]);
        $this->assertSame($rack->id, $child->fresh()->parent_id);

        $this->actingAs($user)
            ->get(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertOk()
            ->assertSee($child->name, false)
            ->assertSee('Unplaced children', false);
    }

    public function test_full_depth_placement_requires_rear_enabled(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(rearEnabled: false);
        $child = $this->makeChildAsset(ruHeight: 2);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 1,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors('full_depth');

        $this->assertSame(0, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_full_depth_placement_occupies_rear_and_blocks_overlap(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 10, rearEnabled: true);
        $child = $this->makeChildAsset(ruHeight: 2);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'child_asset_id' => $child->id,
            'face' => 'front',
            'start_ru' => 3,
            'ru_height' => 2,
            'full_depth' => 1,
        ]);

        $this->assertSame(
            1,
            AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count(),
            'Full-depth uses a single placement row'
        );

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 4,
                'ru_height' => 1,
                'kind' => 'note',
                'note' => 'Conflicts with full-depth rear span',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors('start_ru');

        $this->assertSame(1, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_rear_occupied_blocks_full_depth_placement(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 10, rearEnabled: true);
        $child = $this->makeChildAsset(ruHeight: 2);

        AssetRuPlacement::query()->create([
            'rack_asset_id' => $rack->id,
            'face' => 'rear',
            'start_ru' => 5,
            'ru_height' => 1,
            'full_depth' => false,
            'kind' => 'note',
            'note' => 'Rear note',
        ]);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors('start_ru');

        $this->assertSame(1, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_details_tab_shows_readonly_rack_preview_when_not_empty(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12, rearEnabled: true);

        AssetRuPlacement::query()->create([
            'rack_asset_id' => $rack->id,
            'face' => 'front',
            'start_ru' => 1,
            'ru_height' => 2,
            'full_depth' => false,
            'kind' => 'note',
            'note' => 'Preview note',
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', $rack))
            ->assertOk()
            ->assertSee('Rack front', false)
            ->assertSee('Preview note', false)
            ->assertSee('Open Rack', false)
            ->assertDontSee('Add placement', false);
    }

    public function test_details_tab_omits_rack_preview_when_empty(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset();

        $this->actingAs($user)
            ->get(route('assets.show', $rack))
            ->assertOk()
            ->assertDontSee('Rack front', false)
            ->assertDontSee('Add placement', false);
    }

    public function test_two_halves_in_different_slots_on_the_same_ru_both_save(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Left half',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 2,
                'kind' => 'note',
                'note' => 'Right half',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'note' => 'Left half',
            'width' => 'half',
            'slot' => 1,
            'start_ru' => 4,
            'ru_height' => 2,
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'note' => 'Right half',
            'width' => 'half',
            'slot' => 2,
            'start_ru' => 4,
            'ru_height' => 1,
        ]);
    }

    public function test_same_slot_half_on_the_same_ru_is_rejected(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Left half',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']));

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Also left',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);

        $this->assertSame(1, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_three_thirds_in_one_ru_all_save(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12);

        foreach ([1 => 'Left third', 2 => 'Centre third', 3 => 'Right third'] as $slot => $note) {
            $this->actingAs($user)
                ->post(route('assets.ru-placements.store', $rack), [
                    'face' => 'front',
                    'start_ru' => 6,
                    'ru_height' => $slot,
                    'width' => 'third',
                    'slot' => $slot,
                    'kind' => 'note',
                    'note' => $note,
                ])
                ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'note' => 'Centre third',
            'width' => 'third',
            'slot' => 2,
            'ru_height' => 2,
            'start_ru' => 6,
        ]);
    }

    public function test_fourth_third_in_an_occupied_span_is_rejected(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12);
        $this->placeThirds($user, $rack, 6);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 6,
                'ru_height' => 1,
                'width' => 'third',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Fourth third',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);

        $this->assertSame(3, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_half_in_a_third_span_is_rejected(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12);
        $this->placeThirds($user, $rack, 6);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 6,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Half in a third bay',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);

        $this->assertSame(3, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_full_width_placement_blocks_every_slot_in_its_span(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'ru_height' => 2,
                'width' => 'full',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Full width',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        foreach ([['half', 1], ['half', 2], ['third', 3]] as [$width, $slot]) {
            $this->actingAs($user)
                ->from(route('assets.show', [$rack, 'tab' => 'rack']))
                ->post(route('assets.ru-placements.store', $rack), [
                    'face' => 'front',
                    'start_ru' => 5,
                    'ru_height' => 1,
                    'width' => $width,
                    'slot' => $slot,
                    'kind' => 'note',
                    'note' => "Blocked {$width} {$slot}",
                ])
                ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
                ->assertSessionHasErrors([
                    'start_ru' => 'This placement overlaps another placement on the same face.',
                ]);
        }

        $this->assertSame(1, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_full_depth_half_on_the_front_left_blocks_the_rear_right_and_leaves_the_rear_left_free(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12, rearEnabled: true);
        $child = $this->makeChildAsset(ruHeight: 2);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 2,
                'kind' => 'note',
                'note' => 'Rear right blocked',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Rear left free',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'child_asset_id' => $child->id,
            'width' => 'half',
            'slot' => 1,
            'full_depth' => 1,
            'face' => 'front',
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'note' => 'Rear left free',
            'face' => 'rear',
            'width' => 'half',
            'slot' => 1,
        ]);
        $this->assertDatabaseMissing('asset_ru_placements', [
            'note' => 'Rear right blocked',
        ]);
        $this->assertSame(2, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_full_depth_half_on_the_front_right_blocks_the_rear_left_and_leaves_the_rear_right_free(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12, rearEnabled: true);
        $child = $this->makeChildAsset(ruHeight: 2);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 2,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Rear left blocked',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 2,
                'kind' => 'note',
                'note' => 'Rear right free',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'rack_asset_id' => $rack->id,
            'child_asset_id' => $child->id,
            'width' => 'half',
            'slot' => 2,
            'full_depth' => 1,
            'face' => 'front',
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'note' => 'Rear right free',
            'face' => 'rear',
            'width' => 'half',
            'slot' => 2,
        ]);
        $this->assertDatabaseMissing('asset_ru_placements', [
            'note' => 'Rear left blocked',
        ]);
        $this->assertSame(2, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_full_depth_halves_in_different_front_slots_both_save(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12, rearEnabled: true);
        $left = $this->makeChildAsset(ruHeight: 2);
        $right = $this->makeChildAsset(ruHeight: 1);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'asset',
                'child_asset_id' => $left->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 2,
                'kind' => 'asset',
                'child_asset_id' => $right->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'child_asset_id' => $left->id,
            'face' => 'front',
            'slot' => 1,
            'width' => 'half',
            'full_depth' => 1,
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'child_asset_id' => $right->id,
            'face' => 'front',
            'slot' => 2,
            'width' => 'half',
            'full_depth' => 1,
        ]);
        $this->assertSame(2, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_full_depth_third_on_the_front_left_blocks_the_rear_right_and_leaves_the_rear_left_free(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12, rearEnabled: true);
        $child = $this->makeChildAsset(ruHeight: 2);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 2,
                'width' => 'third',
                'slot' => 1,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'third',
                'slot' => 3,
                'kind' => 'note',
                'note' => 'Rear right blocked',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'third',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Rear left free',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'child_asset_id' => $child->id,
            'face' => 'front',
            'width' => 'third',
            'slot' => 1,
            'full_depth' => 1,
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'note' => 'Rear left free',
            'face' => 'rear',
            'width' => 'third',
            'slot' => 1,
        ]);
        $this->assertDatabaseMissing('asset_ru_placements', [
            'note' => 'Rear right blocked',
        ]);
        $this->assertSame(2, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_full_depth_third_in_the_centre_blocks_the_rear_centre_and_leaves_the_sides_free(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12, rearEnabled: true);
        $child = $this->makeChildAsset(ruHeight: 2);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 2,
                'width' => 'third',
                'slot' => 2,
                'kind' => 'asset',
                'child_asset_id' => $child->id,
                'full_depth' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'rear',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'third',
                'slot' => 2,
                'kind' => 'note',
                'note' => 'Rear centre blocked',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);

        foreach ([1 => 'Rear left free', 3 => 'Rear right free'] as $slot => $note) {
            $this->actingAs($user)
                ->post(route('assets.ru-placements.store', $rack), [
                    'face' => 'rear',
                    'start_ru' => 3,
                    'ru_height' => 1,
                    'width' => 'third',
                    'slot' => $slot,
                    'kind' => 'note',
                    'note' => $note,
                ])
                ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseHas('asset_ru_placements', [
            'child_asset_id' => $child->id,
            'face' => 'front',
            'width' => 'third',
            'slot' => 2,
            'full_depth' => 1,
        ]);
        $this->assertDatabaseMissing('asset_ru_placements', [
            'note' => 'Rear centre blocked',
        ]);
        $this->assertSame(3, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_half_depth_left_does_not_block_the_rear_bays(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12, rearEnabled: true);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 3,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Front left',
            ])
            ->assertSessionHasNoErrors();

        foreach ([1 => 'Rear left', 2 => 'Rear right'] as $slot => $note) {
            $this->actingAs($user)
                ->post(route('assets.ru-placements.store', $rack), [
                    'face' => 'rear',
                    'start_ru' => 3,
                    'ru_height' => 1,
                    'width' => 'half',
                    'slot' => $slot,
                    'kind' => 'note',
                    'note' => $note,
                ])
                ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_half_width_slot_outside_its_range_is_rejected(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 12);

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 1,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 3,
                'kind' => 'note',
                'note' => 'Too far right',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'slot' => 'That position is not available for this width.',
            ]);

        $this->assertSame(0, AssetRuPlacement::query()->where('rack_asset_id', $rack->id)->count());
    }

    public function test_half_row_moves_to_a_new_start_ru_together(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 20, rearEnabled: true);
        $leftChild = $this->makeChildAsset(ruHeight: 2);
        $rightChild = $this->makeChildAsset(ruHeight: 1);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'asset',
                'child_asset_id' => $leftChild->id,
                'full_depth' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 4,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 2,
                'kind' => 'asset',
                'child_asset_id' => $rightChild->id,
            ])
            ->assertSessionHasNoErrors();

        $left = AssetRuPlacement::query()->where('child_asset_id', $leftChild->id)->firstOrFail();
        $right = AssetRuPlacement::query()->where('child_asset_id', $rightChild->id)->firstOrFail();

        $this->actingAs($user)
            ->put(route('assets.ru-placements.update', [$rack, $left]), [
                'face' => 'front',
                'start_ru' => 8,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 1,
                'full_depth' => '1',
                'kind' => 'asset',
                'child_asset_id' => $leftChild->id,
                'move_row' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'id' => $left->id,
            'start_ru' => 8,
            'ru_height' => 2,
            'slot' => 1,
            'width' => 'half',
            'full_depth' => 1,
            'face' => 'front',
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'id' => $right->id,
            'start_ru' => 8,
            'ru_height' => 1,
            'slot' => 2,
            'width' => 'half',
            'full_depth' => 0,
        ]);

        $log = AuditLog::query()
            ->where('auditable_type', Asset::class)
            ->where('auditable_id', $rack->id)
            ->where('action', 'ru_placement_row_moved')
            ->sole();
        $memberIds = [(int) $left->id, (int) $right->id];
        sort($memberIds);

        $this->assertSame(4, $log->old_values['start_ru']);
        $this->assertSame(8, $log->new_values['start_ru']);
        $this->assertSame($memberIds, $log->old_values['member_ids']);
        $this->assertSame($memberIds, $log->new_values['member_ids']);
        $this->assertSame(0, AuditLog::query()->where('auditable_id', $rack->id)->where('action', 'ru_placement_updated')->count());
    }

    public function test_third_row_moves_to_a_new_start_ru_together(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 20);
        $this->placeThirds($user, $rack, 5);

        $centre = AssetRuPlacement::query()->where('note', 'Centre third')->firstOrFail();

        $this->actingAs($user)
            ->put(route('assets.ru-placements.update', [$rack, $centre]), [
                'face' => 'front',
                'start_ru' => 9,
                'ru_height' => 2,
                'width' => 'third',
                'slot' => 2,
                'kind' => 'note',
                'note' => 'Centre third',
                'move_row' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'note' => 'Left third',
            'start_ru' => 9,
            'slot' => 1,
            'ru_height' => 1,
            'width' => 'third',
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'note' => 'Centre third',
            'start_ru' => 9,
            'slot' => 2,
            'ru_height' => 2,
            'width' => 'third',
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'note' => 'Right third',
            'start_ru' => 9,
            'slot' => 3,
            'ru_height' => 3,
            'width' => 'third',
        ]);
        $this->assertSame(1, AuditLog::query()->where('auditable_id', $rack->id)->where('action', 'ru_placement_row_moved')->count());
    }

    public function test_row_move_is_rejected_when_the_destination_overlaps(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 20);
        $this->placeHalves($user, $rack, 4);

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => 8,
                'ru_height' => 1,
                'kind' => 'note',
                'note' => 'Blocker',
            ])
            ->assertSessionHasNoErrors();

        $left = AssetRuPlacement::query()->where('note', 'Left half')->firstOrFail();

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->put(route('assets.ru-placements.update', [$rack, $left]), [
                'face' => 'front',
                'start_ru' => 8,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Left half',
                'move_row' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);

        $this->assertDatabaseHas('asset_ru_placements', ['note' => 'Left half', 'start_ru' => 4, 'ru_height' => 2]);
        $this->assertDatabaseHas('asset_ru_placements', ['note' => 'Right half', 'start_ru' => 4, 'ru_height' => 1]);
        $this->assertSame(0, AuditLog::query()->where('auditable_id', $rack->id)->where('action', 'ru_placement_row_moved')->count());
    }

    public function test_row_move_is_rejected_when_a_member_would_fall_outside_the_rack(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 10);
        $this->placeHalves($user, $rack, 4);

        $right = AssetRuPlacement::query()->where('note', 'Right half')->firstOrFail();

        $this->actingAs($user)
            ->from(route('assets.show', [$rack, 'tab' => 'rack']))
            ->put(route('assets.ru-placements.update', [$rack, $right]), [
                'face' => 'front',
                'start_ru' => 10,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 2,
                'kind' => 'note',
                'note' => 'Right half',
                'move_row' => '1',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasErrors([
                'start_ru' => 'Placement must fit within the rack capacity of 10 RU.',
            ]);

        $this->assertDatabaseHas('asset_ru_placements', ['note' => 'Left half', 'start_ru' => 4]);
        $this->assertDatabaseHas('asset_ru_placements', ['note' => 'Right half', 'start_ru' => 4, 'ru_height' => 1]);
    }

    public function test_unchecking_move_row_moves_only_the_edited_item(): void
    {
        $user = $this->manager();
        $rack = $this->makeRackAsset(ruHeight: 20);
        $this->placeHalves($user, $rack, 4);

        $left = AssetRuPlacement::query()->where('note', 'Left half')->firstOrFail();

        $this->actingAs($user)
            ->put(route('assets.ru-placements.update', [$rack, $left]), [
                'face' => 'front',
                'start_ru' => 9,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Left half',
                'move_row' => '0',
            ])
            ->assertRedirect(route('assets.show', [$rack, 'tab' => 'rack']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('asset_ru_placements', [
            'id' => $left->id,
            'start_ru' => 9,
            'ru_height' => 2,
            'slot' => 1,
        ]);
        $this->assertDatabaseHas('asset_ru_placements', [
            'note' => 'Right half',
            'start_ru' => 4,
            'ru_height' => 1,
            'slot' => 2,
        ]);
        $this->assertSame(0, AuditLog::query()->where('auditable_id', $rack->id)->where('action', 'ru_placement_row_moved')->count());
        $this->assertSame(1, AuditLog::query()->where('auditable_id', $rack->id)->where('action', 'ru_placement_updated')->count());
    }

    protected function placeHalves(User $user, Asset $rack, int $startRu): void
    {
        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => $startRu,
                'ru_height' => 2,
                'width' => 'half',
                'slot' => 1,
                'kind' => 'note',
                'note' => 'Left half',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('assets.ru-placements.store', $rack), [
                'face' => 'front',
                'start_ru' => $startRu,
                'ru_height' => 1,
                'width' => 'half',
                'slot' => 2,
                'kind' => 'note',
                'note' => 'Right half',
            ])
            ->assertSessionHasNoErrors();
    }

    protected function placeThirds(User $user, Asset $rack, int $startRu): void
    {
        foreach ([1 => 'Left third', 2 => 'Centre third', 3 => 'Right third'] as $slot => $note) {
            $this->actingAs($user)
                ->post(route('assets.ru-placements.store', $rack), [
                    'face' => 'front',
                    'start_ru' => $startRu,
                    'ru_height' => $slot,
                    'width' => 'third',
                    'slot' => $slot,
                    'kind' => 'note',
                    'note' => $note,
                ])
                ->assertSessionHasNoErrors();
        }
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetRuPlacement;
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
            ->assertSee('Add placement', false);
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
}

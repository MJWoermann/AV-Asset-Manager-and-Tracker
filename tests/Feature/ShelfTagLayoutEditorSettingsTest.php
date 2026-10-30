<?php

namespace Tests\Feature;

use App\Models\ShelfTagLayout;
use App\Models\User;
use App\Support\ShelfTagPaperPresets;
use Database\Factories\ShelfTagLayoutFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShelfTagLayoutEditorSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function manager(): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('inventory_manager');

        return $this->enableFeature($user, 'shelf_tag_generator');
    }

    public function test_editor_grid_settings_persist_on_create_and_reload(): void
    {
        $user = $this->manager();

        $response = $this->actingAs($user)->post(route('labels.layouts.store'), [
            'name' => 'Grid settings layout',
            'paper_preset' => ShelfTagPaperPresets::DEFAULT,
            'width_mm' => 62,
            'height_mm' => 40,
            'orientation' => 'portrait',
            'length_mode' => ShelfTagPaperPresets::LENGTH_MODE_FIXED,
            'elements' => json_encode(ShelfTagLayoutFactory::sampleElements()),
            'editor_settings' => json_encode([
                'show_grid' => false,
                'snap_to_grid' => true,
                'grid_size_mm' => 2.5,
            ]),
        ]);

        $layout = ShelfTagLayout::query()->first();
        $this->assertNotNull($layout);
        $response->assertRedirect(route('labels.layouts.edit', $layout));

        $this->assertFalse((bool) data_get($layout->editor_settings, 'show_grid'));
        $this->assertTrue((bool) data_get($layout->editor_settings, 'snap_to_grid'));
        $this->assertSame(2.5, (float) data_get($layout->editor_settings, 'grid_size_mm'));
        $this->assertSame(ShelfTagPaperPresets::LENGTH_MODE_FIXED, $layout->length_mode);

        $fresh = $layout->fresh();
        $this->assertNotNull($fresh);
        $settings = $fresh->normalizedEditorSettings();
        $this->assertFalse($settings['show_grid']);
        $this->assertTrue($settings['snap_to_grid']);
        $this->assertSame(2.5, $settings['grid_size_mm']);
        $this->assertTrue($settings['show_bleed']);
        $this->assertSame(3.0, $settings['bleed_top_mm']);
        $this->assertSame(3.0, $settings['bleed_bottom_mm']);
        $this->assertSame(2.0, $settings['bleed_left_mm']);
        $this->assertSame(2.0, $settings['bleed_right_mm']);

        $edit = $this->actingAs($user)->get(route('labels.layouts.edit', $layout));
        $edit->assertOk();
        $edit->assertSee('shelfTagDesigner(', false);
        $edit->assertSee('Show grid', false);
        $edit->assertSee('Snap to grid', false);
        $edit->assertSee('Show bleed', false);
        $edit->assertSee('shelf-tag-bleed-guide', false);
    }

    public function test_bleed_guide_settings_persist_and_clamp(): void
    {
        $user = $this->manager();

        $this->actingAs($user)->post(route('labels.layouts.store'), [
            'name' => 'Bleed layout',
            'paper_preset' => ShelfTagPaperPresets::DEFAULT,
            'width_mm' => 62,
            'height_mm' => 40,
            'orientation' => 'portrait',
            'length_mode' => ShelfTagPaperPresets::LENGTH_MODE_FIXED,
            'elements' => json_encode([]),
            'editor_settings' => json_encode([
                'show_bleed' => false,
                'bleed_top_mm' => 80,
                'bleed_bottom_mm' => 1.26,
                'bleed_left_mm' => -4,
                'bleed_right_mm' => 6,
            ]),
        ])->assertRedirect();

        $layout = ShelfTagLayout::query()->where('name', 'Bleed layout')->first();
        $this->assertNotNull($layout);
        $settings = $layout->normalizedEditorSettings();
        $this->assertFalse($settings['show_bleed']);
        $this->assertSame(50.0, $settings['bleed_top_mm']);
        $this->assertSame(1.3, $settings['bleed_bottom_mm']);
        $this->assertSame(0.0, $settings['bleed_left_mm']);
        $this->assertSame(6.0, $settings['bleed_right_mm']);
    }

    public function test_editor_settings_update_and_length_mode_auto_persist(): void
    {
        $user = $this->manager();
        $layout = ShelfTagLayout::factory()->create([
            'created_by' => $user->id,
            'editor_settings' => ShelfTagPaperPresets::defaultEditorSettings(),
            'length_mode' => ShelfTagPaperPresets::LENGTH_MODE_FIXED,
            'height_mm' => 40,
        ]);

        $this->actingAs($user)
            ->put(route('labels.layouts.update', $layout), [
                'name' => $layout->name,
                'paper_preset' => ShelfTagPaperPresets::DEFAULT,
                'width_mm' => 62,
                'height_mm' => 55,
                'orientation' => 'portrait',
                'length_mode' => ShelfTagPaperPresets::LENGTH_MODE_AUTO,
                'elements' => json_encode(ShelfTagLayoutFactory::sampleElements()),
                'editor_settings' => json_encode([
                    'show_grid' => true,
                    'snap_to_grid' => true,
                    'grid_size_mm' => 10,
                ]),
            ])
            ->assertRedirect(route('labels.layouts.edit', $layout));

        $layout->refresh();
        $settings = $layout->normalizedEditorSettings();
        $this->assertTrue($settings['show_grid']);
        $this->assertTrue($settings['snap_to_grid']);
        $this->assertSame(10.0, $settings['grid_size_mm']);
        $this->assertSame(ShelfTagPaperPresets::LENGTH_MODE_AUTO, $layout->length_mode);
        $this->assertSame(55.0, (float) $layout->height_mm);
    }

    public function test_individual_grid_fields_map_into_editor_settings(): void
    {
        $user = $this->manager();

        $this->actingAs($user)->post(route('labels.layouts.store'), [
            'name' => 'Flat grid fields',
            'paper_preset' => ShelfTagPaperPresets::DEFAULT,
            'width_mm' => 62,
            'height_mm' => 40,
            'orientation' => 'portrait',
            'length_mode' => 'fixed',
            'elements' => json_encode([]),
            'show_grid' => '0',
            'snap_to_grid' => '1',
            'grid_size_mm' => 5,
        ])->assertRedirect();

        $layout = ShelfTagLayout::query()->where('name', 'Flat grid fields')->first();
        $this->assertNotNull($layout);
        $settings = $layout->normalizedEditorSettings();
        $this->assertFalse($settings['show_grid']);
        $this->assertTrue($settings['snap_to_grid']);
        $this->assertSame(5.0, $settings['grid_size_mm']);
    }

    public function test_auto_length_rejected_for_diecut_preset(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->post(route('labels.layouts.store'), [
                'name' => 'Diecut auto',
                'paper_preset' => 'diecut_62x100',
                'width_mm' => 62,
                'height_mm' => 100,
                'orientation' => 'portrait',
                'length_mode' => ShelfTagPaperPresets::LENGTH_MODE_AUTO,
                'elements' => json_encode([]),
            ])
            ->assertSessionHasErrors('length_mode');
    }
}

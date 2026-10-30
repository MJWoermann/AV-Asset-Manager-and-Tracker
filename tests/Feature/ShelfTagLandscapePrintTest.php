<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\ShelfTagLayout;
use App\Models\User;
use App\Services\ShelfTagRenderService;
use Database\Factories\ShelfTagLayoutFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShelfTagLandscapePrintTest extends TestCase
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

    public function test_landscape_layout_uses_swapped_display_dimensions(): void
    {
        $layout = ShelfTagLayout::factory()->create([
            'width_mm' => 62.0,
            'height_mm' => 100.0,
            'orientation' => 'landscape',
        ]);

        $this->assertTrue($layout->isLandscape());
        $this->assertEqualsWithDelta(100.0, $layout->displayWidthMm(), 0.01);
        $this->assertEqualsWithDelta(62.0, $layout->displayHeightMm(), 0.01);
    }

    public function test_render_service_emits_landscape_label_size(): void
    {
        $user = $this->manager();
        $layout = ShelfTagLayout::factory()->create([
            'created_by' => $user->id,
            'width_mm' => 62.0,
            'height_mm' => 120.0,
            'orientation' => 'landscape',
            'elements' => ShelfTagLayoutFactory::sampleElements(),
        ]);

        $itemType = ItemType::query()->create([
            'name' => 'Type Landscape',
            'slug' => 'type-landscape',
        ]);
        $asset = Asset::query()->create([
            'name' => 'Landscape Asset',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-LAND-1',
        ]);

        $labels = app(ShelfTagRenderService::class)->buildLabels($layout, collect([$asset]));

        $this->assertCount(1, $labels);
        $this->assertSame('landscape', $labels[0]['orientation']);
        $this->assertEqualsWithDelta(120.0, $labels[0]['width_mm'], 0.01);
        $this->assertEqualsWithDelta(62.0, $labels[0]['height_mm'], 0.01);
    }

    public function test_pdf_markup_uses_absolute_positioning_and_landscape_page(): void
    {
        $user = $this->manager();
        $layout = ShelfTagLayout::factory()->create([
            'created_by' => $user->id,
            'width_mm' => 62.0,
            'height_mm' => 90.0,
            'orientation' => 'landscape',
            'elements' => ShelfTagLayoutFactory::sampleElements(),
        ]);

        $itemType = ItemType::query()->create([
            'name' => 'Type Pdf',
            'slug' => 'type-pdf-land',
        ]);
        $asset = Asset::query()->create([
            'name' => 'Pdf Asset',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-PDF-1',
        ]);

        $labels = app(ShelfTagRenderService::class)->buildLabels($layout, collect([$asset]), withServerImages: true);
        $html = view('shelf-tags.print.pdf', [
            'layout' => $layout,
            'labels' => $labels,
        ])->render();

        $this->assertStringContainsString('position:absolute', $html);
        $this->assertStringContainsString('width: 90mm', $html);
        $this->assertStringContainsString('height: 62mm', $html);
        $this->assertStringContainsString('TP-PDF-1', $html);
    }

    public function test_landscape_preview_shows_swapped_sheet_size(): void
    {
        $user = $this->manager();
        $list = AssetList::query()->create([
            'name' => 'Inventory Land',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        $itemType = ItemType::query()->create([
            'name' => 'Type Prev',
            'slug' => 'type-prev-land',
        ]);
        $asset = Asset::query()->create([
            'name' => 'Preview Asset',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-PREV-1',
        ]);
        AssetListItem::query()->create([
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);

        $layout = ShelfTagLayout::factory()->create([
            'created_by' => $user->id,
            'width_mm' => 62.0,
            'height_mm' => 110.0,
            'orientation' => 'landscape',
            'elements' => ShelfTagLayoutFactory::sampleElements(),
        ]);

        $this->actingAs($user)
            ->post(route('labels.print.preview'), [
                'shelf_tag_layout_id' => $layout->id,
                'asset_list_id' => $list->id,
                'mode' => 'all',
            ])
            ->assertRedirect(route('labels.print.show'));

        $this->actingAs($user)
            ->get(route('labels.print.show'))
            ->assertOk()
            ->assertSee('width: 110mm', false)
            ->assertSee('height: 62mm', false)
            ->assertSee('data-orientation="landscape"', false);
    }
}

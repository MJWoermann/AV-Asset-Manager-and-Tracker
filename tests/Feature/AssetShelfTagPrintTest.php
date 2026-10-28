<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\ShelfTagLayout;
use App\Models\User;
use Database\Factories\ShelfTagLayoutFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetShelfTagPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function withFeature(User $user): User
    {
        return $this->enableFeature($user, 'shelf_tag_generator');
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

    protected function makeAsset(array $overrides = []): Asset
    {
        $itemType = ItemType::query()->create([
            'name' => 'Type '.fake()->unique()->numerify('###'),
            'slug' => 'type-'.fake()->unique()->numerify('###'),
        ]);

        return Asset::query()->create(array_merge([
            'item_type_id' => $itemType->id,
            'name' => 'Shelf Asset',
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-'.fake()->unique()->numerify('######'),
            'fmi_ast' => 'FMI-'.fake()->unique()->numerify('####'),
        ], $overrides));
    }

    public function test_flag_off_returns_404_for_asset_shelf_tag_routes(): void
    {
        $user = $this->manager(feature: false);
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->get(route('assets.label', $asset))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('assets.label.pdf', $asset))
            ->assertNotFound();
    }

    public function test_scanner_with_flag_on_gets_forbidden(): void
    {
        $user = $this->scanner(feature: true);
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->get(route('assets.label', $asset))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('assets.label.pdf', $asset))
            ->assertForbidden();
    }

    public function test_manager_with_flag_sees_print_link_on_asset_show(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(['name' => 'Printable Amp']);

        $this->actingAs($user)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSee(route('assets.label', $asset, false), false)
            ->assertSee('Print', false);
    }

    public function test_manager_without_flag_does_not_see_print_link_on_asset_show(): void
    {
        $user = $this->manager(feature: false);
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertDontSee(route('assets.label', $asset, false), false);
    }

    public function test_layout_query_auto_previews_single_asset_label(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset([
            'name' => 'Rack Amp Solo',
            'tp_barcode' => 'TP-SOLO-1',
            'fmi_ast' => 'FMI-SOLO',
        ]);

        $layout = ShelfTagLayout::factory()->create([
            'name' => 'Solo Layout',
            'created_by' => $user->id,
            'elements' => ShelfTagLayoutFactory::sampleElements(),
        ]);

        $this->actingAs($user)
            ->get(route('assets.label', [$asset, 'layout' => $layout->id]))
            ->assertOk()
            ->assertSee('Rack Amp Solo')
            ->assertSee('Solo Layout')
            ->assertSee('FMI-SOLO')
            ->assertSee('Print', false)
            ->assertSee('Download PDF', false)
            ->assertSee('shelfTagPrintPreview(', false)
            ->assertSee('name="layout"', false);
    }

    public function test_defaults_to_first_layout_when_no_query(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(['name' => 'Default Preview Asset']);

        ShelfTagLayout::factory()->create([
            'name' => 'Alpha Layout',
            'created_by' => $user->id,
            'elements' => ShelfTagLayoutFactory::sampleElements(),
        ]);
        ShelfTagLayout::factory()->create([
            'name' => 'Zeta Layout',
            'created_by' => $user->id,
            'elements' => ShelfTagLayoutFactory::sampleElements(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('assets.label', $asset));

        $response->assertOk()
            ->assertSee('Default Preview Asset')
            ->assertSee('Alpha Layout')
            ->assertSee('shelfTagPrintPreview(', false);

        $this->assertSame(
            ShelfTagLayout::query()->orderBy('name')->value('id'),
            $response->viewData('layout')?->id
        );
    }

    public function test_pdf_downloads_for_single_asset(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(['name' => 'PDF Solo Asset']);

        $layout = ShelfTagLayout::factory()->create([
            'created_by' => $user->id,
            'elements' => ShelfTagLayoutFactory::sampleElements(),
        ]);

        $this->actingAs($user)
            ->get(route('assets.label.pdf', [$asset, 'layout' => $layout->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_empty_layouts_shows_create_link(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->get(route('assets.label', $asset))
            ->assertOk()
            ->assertSee(route('labels.layouts.create', [], false), false)
            ->assertDontSee('shelfTagPrintPreview(', false);
    }
}

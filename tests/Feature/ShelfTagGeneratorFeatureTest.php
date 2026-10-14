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
use App\Models\Location;
use App\Models\ShelfTagLayout;
use App\Models\User;
use App\Services\ShelfTagRenderService;
use App\Support\ShelfTagFieldKeys;
use App\Support\ShelfTagPaperPresets;
use Database\Factories\ShelfTagLayoutFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShelfTagGeneratorFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function withFeature(User $user): User
    {
        $user->preferences = [
            'features' => [
                'shelf_tag_generator' => true,
            ],
        ];
        $user->save();

        return $user->fresh();
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

    protected function makeList(User $user, string $name = 'Inventory A'): AssetList
    {
        return AssetList::query()->create([
            'name' => $name,
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
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

    protected function attachToList(AssetList $list, Asset $asset, User $user): void
    {
        AssetListItem::query()->create([
            'asset_list_id' => $list->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'added_by' => $user->id,
        ]);
    }

    public function test_flag_off_returns_404_and_hides_nav(): void
    {
        $user = $this->manager(feature: false);

        $this->actingAs($user)
            ->get(route('shelf-tags.layouts.index'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('shelf-tags.print.create'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Shelf tag layouts', false)
            ->assertDontSee('Print shelf tags', false);
    }

    public function test_scanner_with_flag_on_gets_forbidden(): void
    {
        $user = $this->scanner(feature: true);

        $this->actingAs($user)
            ->get(route('shelf-tags.layouts.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('shelf-tags.print.create'))
            ->assertForbidden();
    }

    public function test_manager_with_flag_can_crud_layout_and_upload_logo(): void
    {
        Storage::fake('public');
        $user = $this->manager();

        $create = $this->actingAs($user)->get(route('shelf-tags.layouts.create'));
        $create->assertOk()->assertSee('New shelf tag layout');

        $response = $this->actingAs($user)->post(route('shelf-tags.layouts.store'), [
            'name' => 'Warehouse 62mm',
            'paper_preset' => ShelfTagPaperPresets::DEFAULT,
            'width_mm' => 62,
            'height_mm' => 40,
            'orientation' => 'portrait',
            'elements' => json_encode(ShelfTagLayoutFactory::sampleElements()),
            'logo' => UploadedFile::fake()->image('logo.png', 80, 40),
        ]);

        $layout = ShelfTagLayout::query()->first();
        $this->assertNotNull($layout);
        $response->assertRedirect(route('shelf-tags.layouts.edit', $layout));
        $this->assertSame('Warehouse 62mm', $layout->name);
        $this->assertNotNull($layout->logo_path);
        Storage::disk('public')->assertExists($layout->logo_path);

        $this->actingAs($user)
            ->put(route('shelf-tags.layouts.update', $layout), [
                'name' => 'Warehouse 62mm updated',
                'paper_preset' => ShelfTagPaperPresets::DEFAULT,
                'width_mm' => 62,
                'height_mm' => 45,
                'orientation' => 'portrait',
                'elements' => json_encode(ShelfTagLayoutFactory::sampleElements()),
            ])
            ->assertRedirect(route('shelf-tags.layouts.edit', $layout));

        $this->assertSame('Warehouse 62mm updated', $layout->fresh()->name);
        $this->assertSame(45.0, (float) $layout->fresh()->height_mm);

        $this->actingAs($user)
            ->delete(route('shelf-tags.layouts.destroy', $layout))
            ->assertRedirect(route('shelf-tags.layouts.index'));

        $this->assertDatabaseMissing('shelf_tag_layouts', ['id' => $layout->id]);
    }

    public function test_invalid_paper_preset_is_rejected(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->post(route('shelf-tags.layouts.store'), [
                'name' => 'Bad preset',
                'paper_preset' => 'not_a_real_preset',
                'width_mm' => 62,
                'height_mm' => 40,
                'orientation' => 'portrait',
                'elements' => json_encode([]),
            ])
            ->assertSessionHasErrors('paper_preset');
    }

    public function test_diecut_dimensions_must_match_preset(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->post(route('shelf-tags.layouts.store'), [
                'name' => 'Bad diecut',
                'paper_preset' => 'diecut_62x100',
                'width_mm' => 62,
                'height_mm' => 40,
                'orientation' => 'portrait',
                'elements' => json_encode([]),
            ])
            ->assertSessionHasErrors('paper_preset');
    }

    public function test_print_preview_and_pdf_for_list_assets(): void
    {
        $user = $this->manager();
        $list = $this->makeList($user);
        $asset = $this->makeAsset(['name' => 'Rack Amp', 'tp_barcode' => 'TP-PRINT-1']);
        $this->attachToList($list, $asset, $user);

        $layout = ShelfTagLayout::factory()->create([
            'created_by' => $user->id,
            'elements' => ShelfTagLayoutFactory::sampleElements(),
        ]);

        $this->actingAs($user)
            ->post(route('shelf-tags.print.preview'), [
                'shelf_tag_layout_id' => $layout->id,
                'asset_list_id' => $list->id,
                'mode' => 'all',
            ])
            ->assertRedirect(route('shelf-tags.print.show'));

        $this->actingAs($user)
            ->get(route('shelf-tags.print.show'))
            ->assertOk()
            ->assertSee('Rack Amp')
            ->assertSee('Print', false)
            ->assertSee('Download PDF', false);

        $this->actingAs($user)
            ->get(route('shelf-tags.print.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_selected_ids_not_on_list_are_rejected(): void
    {
        $user = $this->manager();
        $list = $this->makeList($user);
        $onList = $this->makeAsset();
        $offList = $this->makeAsset();
        $this->attachToList($list, $onList, $user);

        $layout = ShelfTagLayout::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->from(route('shelf-tags.print.create'))
            ->post(route('shelf-tags.print.preview'), [
                'shelf_tag_layout_id' => $layout->id,
                'asset_list_id' => $list->id,
                'mode' => 'selected',
                'asset_ids' => [$onList->id, $offList->id],
            ])
            ->assertSessionHasErrors('asset_ids');
    }

    public function test_field_resolution_includes_core_and_custom_fields(): void
    {
        $location = Location::query()->create([
            'name' => 'Bay 3',
            'type' => 'room',
        ]);
        $itemType = ItemType::query()->create([
            'name' => 'Amplifier',
            'slug' => 'amplifier-st',
        ]);
        $set = CustomFieldSet::query()->create([
            'name' => 'Shelf extras',
            'slug' => 'shelf-extras',
        ]);
        $definition = CustomFieldDefinition::query()->create([
            'custom_field_set_id' => $set->id,
            'name' => 'Shelf bay',
            'slug' => 'shelf-bay',
            'type' => 'text',
            'sort_order' => 1,
        ]);

        $asset = Asset::query()->create([
            'item_type_id' => $itemType->id,
            'location_id' => $location->id,
            'name' => 'Custom Field Asset',
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-CUSTOM-1',
            'fmi_ast' => 'FMI-99',
            'serial_number' => 'SN-1',
        ]);

        AssetCustomFieldValue::query()->create([
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $definition->id,
            'value' => 'B4',
        ]);

        $asset->load(['location', 'itemType', 'customFieldValues.definition']);

        $this->assertSame('Custom Field Asset', ShelfTagFieldKeys::resolve($asset, 'name'));
        $this->assertSame('TP-CUSTOM-1', ShelfTagFieldKeys::resolve($asset, 'tp_barcode'));
        $this->assertSame('Bay 3', ShelfTagFieldKeys::resolve($asset, 'location.name'));
        $this->assertSame('Amplifier', ShelfTagFieldKeys::resolve($asset, 'itemType.name'));
        $this->assertSame('B4', ShelfTagFieldKeys::resolve($asset, 'custom:shelf-bay'));

        $layout = ShelfTagLayout::factory()->make([
            'elements' => [
                [
                    'id' => 'el-custom',
                    'type' => 'field',
                    'x' => 1,
                    'y' => 1,
                    'width' => 40,
                    'height' => 8,
                    'z' => 1,
                    'props' => ['fieldKey' => 'custom:shelf-bay', 'fontSize' => 10],
                ],
            ],
        ]);

        $labels = app(ShelfTagRenderService::class)->buildLabels($layout, collect([$asset]));
        $this->assertSame('B4', $labels[0]['elements'][0]['value']);
    }

    public function test_list_show_exposes_shelf_tags_entry_when_enabled(): void
    {
        $user = $this->manager();
        $list = $this->makeList($user);

        $this->actingAs($user)
            ->get(route('lists.show', $list))
            ->assertOk()
            ->assertSee('Shelf tags', false);

        $disabled = $this->manager(feature: false);
        $this->actingAs($disabled)
            ->get(route('lists.show', $list))
            ->assertOk()
            ->assertDontSee(route('shelf-tags.print.create', ['asset_list_id' => $list->id], false), false);
    }

    public function test_nav_shows_shelf_tag_links_for_enabled_manager(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Shelf tag layouts', false)
            ->assertSee('Print shelf tags', false);
    }
}

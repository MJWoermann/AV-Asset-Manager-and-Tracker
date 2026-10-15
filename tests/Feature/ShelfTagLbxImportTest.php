<?php

namespace Tests\Feature;

use App\Models\ShelfTagLayout;
use App\Models\User;
use App\Services\ShelfTagLbxImporter;
use App\Support\ShelfTagLbxConverter;
use App\Support\ShelfTagLbxParser;
use App\Support\ShelfTagPaperPresets;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShelfTagLbxImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function fixturePath(): string
    {
        return base_path('tests/fixtures/shelf-tags/float-switch.lbx');
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

    public function test_continuous_length_max_accepts_118_mm(): void
    {
        $this->assertSame(300.0, ShelfTagPaperPresets::CONTINUOUS_LENGTH_MAX_MM);
        $this->assertSame(118.0, ShelfTagPaperPresets::clampContinuousLength(118.0));
        $this->assertSame(300.0, ShelfTagPaperPresets::clampContinuousLength(400.0));
    }

    public function test_parser_reads_float_switch_fixture_objects_and_qr_data(): void
    {
        $this->assertFileExists($this->fixturePath());

        $parsed = app(ShelfTagLbxParser::class)->parse($this->fixturePath());

        $this->assertSame('landscape', $parsed['orientation']);
        $this->assertEqualsWithDelta(334.8, $parsed['background']['width'], 0.1);
        $this->assertEqualsWithDelta(167.1, $parsed['background']['height'], 0.1);

        $kinds = array_count_values(array_column($parsed['objects'], 'kind'));
        $this->assertSame(4, $kinds['text'] ?? 0);
        $this->assertSame(1, $kinds['image'] ?? 0);
        $this->assertSame(1, $kinds['barcode'] ?? 0);

        $qr = collect($parsed['objects'])->first(
            fn (array $object): bool => ($object['kind'] ?? '') === 'barcode'
                && ($object['protocol'] ?? '') === 'QRCODE'
        );
        $this->assertNotNull($qr);
        $this->assertSame(
            'mailto:ict-servicedesk@tepae.co.nz?subject=Device%20Onboarding%20Request',
            $qr['data']
        );

        $texts = collect($parsed['objects'])
            ->where('kind', 'text')
            ->pluck('data')
            ->all();
        $this->assertContains('ICT', $texts);
        $this->assertContains('Aruba Network Switch', $texts);
        $this->assertContains('CCEC-FLOAT-SW 4', $texts);

        $this->assertArrayHasKey('Object0.jpg', $parsed['files']);
        $this->assertNotSame('', $parsed['files']['Object0.jpg']);
    }

    public function test_converter_landscape_transform_places_logo_near_top_left_and_maps_qr(): void
    {
        $parsed = app(ShelfTagLbxParser::class)->parse($this->fixturePath());
        $converted = app(ShelfTagLbxConverter::class)->convert($parsed, 'Float Switch Case Sticker');

        $this->assertSame('continuous_62', $converted['paper_preset']);
        $this->assertEqualsWithDelta(59.0, $converted['width_mm'], 1.0);
        $this->assertEqualsWithDelta(118.0, $converted['height_mm'], 1.0);
        $this->assertSame(ShelfTagPaperPresets::LENGTH_MODE_FIXED, $converted['length_mode']);
        $this->assertNotNull($converted['logo_binary']);

        $types = array_count_values(array_column($converted['elements'], 'type'));
        $this->assertSame(4, $types['text'] ?? 0);
        $this->assertSame(1, $types['logo'] ?? 0);
        $this->assertSame(1, $types['qr'] ?? 0);

        $logo = collect($converted['elements'])->firstWhere('type', 'logo');
        $this->assertNotNull($logo);
        $this->assertLessThan(2.0, (float) $logo['x']);
        $this->assertLessThan(2.0, (float) $logo['y']);

        $qr = collect($converted['elements'])->firstWhere('type', 'qr');
        $this->assertNotNull($qr);
        $this->assertSame(
            'mailto:ict-servicedesk@tepae.co.nz?subject=Device%20Onboarding%20Request',
            $qr['props']['content'] ?? null
        );
        $this->assertSame('M', $qr['props']['ecc'] ?? null);
    }

    public function test_converter_pt_to_mm_and_portrait_transform(): void
    {
        $converter = app(ShelfTagLbxConverter::class);

        $this->assertEqualsWithDelta(25.4, $converter->ptToMm(72.0), 0.0001);

        $box = $converter->transformBox(
            20.0,
            10.0,
            40.0,
            15.0,
            ['x' => 0.0, 'y' => 0.0, 'width' => 100.0, 'height' => 50.0],
            landscape: false
        );

        $this->assertEqualsWithDelta($converter->ptToMm(20.0), $box['x'], 0.05);
        $this->assertEqualsWithDelta($converter->ptToMm(10.0), $box['y'], 0.05);
        $this->assertEqualsWithDelta($converter->ptToMm(40.0), $box['width'], 0.05);
        $this->assertEqualsWithDelta($converter->ptToMm(15.0), $box['height'], 0.05);
    }

    public function test_manager_with_flag_can_import_lbx_and_is_redirected_to_edit(): void
    {
        Storage::fake('public');
        $user = $this->manager();

        $this->actingAs($user)
            ->get(route('shelf-tags.layouts.import'))
            ->assertOk()
            ->assertSee('Import P-touch layout');

        $this->actingAs($user)
            ->get(route('shelf-tags.index'))
            ->assertOk()
            ->assertSee('Import .lbx');

        $upload = new UploadedFile(
            $this->fixturePath(),
            'float-switch.lbx',
            'application/zip',
            null,
            true
        );

        $response = $this->actingAs($user)->post(route('shelf-tags.layouts.import.store'), [
            'lbx' => $upload,
        ]);

        $layout = ShelfTagLayout::query()->first();
        $this->assertNotNull($layout);
        $response->assertRedirect(route('shelf-tags.layouts.edit', $layout));
        $response->assertSessionHas('status');

        $this->assertSame('continuous_62', $layout->paper_preset);
        $this->assertGreaterThan(100.0, (float) $layout->height_mm);
        $this->assertLessThanOrEqual(ShelfTagPaperPresets::CONTINUOUS_LENGTH_MAX_MM, (float) $layout->height_mm);
        $this->assertNotNull($layout->logo_path);
        Storage::disk('public')->assertExists($layout->logo_path);

        $types = collect($layout->elements)->pluck('type');
        $this->assertTrue($types->contains('qr'));
        $this->assertTrue($types->contains('logo'));
        $this->assertSame(4, $types->filter(fn (string $type): bool => $type === 'text')->count());
        $this->assertSame($user->id, $layout->created_by);
    }

    public function test_scanner_with_flag_is_forbidden_from_lbx_import(): void
    {
        $user = $this->scanner(feature: true);

        $this->actingAs($user)
            ->get(route('shelf-tags.layouts.import'))
            ->assertForbidden();

        $upload = new UploadedFile(
            $this->fixturePath(),
            'float-switch.lbx',
            'application/zip',
            null,
            true
        );

        $this->actingAs($user)
            ->post(route('shelf-tags.layouts.import.store'), ['lbx' => $upload])
            ->assertForbidden();
    }

    public function test_manager_without_feature_flag_gets_404_on_lbx_import(): void
    {
        $user = $this->manager(feature: false);

        $this->actingAs($user)
            ->get(route('shelf-tags.layouts.import'))
            ->assertNotFound();

        $upload = new UploadedFile(
            $this->fixturePath(),
            'float-switch.lbx',
            'application/zip',
            null,
            true
        );

        $this->actingAs($user)
            ->post(route('shelf-tags.layouts.import.store'), ['lbx' => $upload])
            ->assertNotFound();
    }

    public function test_importer_service_persists_layout_from_fixture(): void
    {
        Storage::fake('public');
        $user = $this->manager();

        $result = app(ShelfTagLbxImporter::class)->import(
            $this->fixturePath(),
            $user,
            'Float Switch Case Sticker'
        );

        $layout = $result['layout'];
        $this->assertInstanceOf(ShelfTagLayout::class, $layout);
        $this->assertDatabaseHas('shelf_tag_layouts', [
            'id' => $layout->id,
            'paper_preset' => 'continuous_62',
            'created_by' => $user->id,
        ]);
        $this->assertNotNull($layout->logo_path);
        Storage::disk('public')->assertExists($layout->logo_path);
    }
}

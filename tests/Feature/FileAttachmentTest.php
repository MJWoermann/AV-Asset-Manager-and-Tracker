<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\File;
use App\Models\FileLink;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    protected function makeItemType(string $name = 'LX Fixture'): ItemType
    {
        return ItemType::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
        ]);
    }

    protected function makeAsset(?ItemType $itemType = null): Asset
    {
        $itemType ??= $this->makeItemType();

        return Asset::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'Test Asset',
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-'.fake()->unique()->numerify('####'),
        ]);
    }

    protected function makeLocation(): Location
    {
        return Location::query()->create([
            'name' => 'Room A',
            'type' => LocationType::Room,
            'is_portable' => false,
        ]);
    }

    public function test_guest_cannot_download_file(): void
    {
        $file = File::factory()->create();

        $this->get(route('files.download', $file))->assertRedirect(route('login'));
    }

    public function test_scanner_can_upload_and_link_file_to_asset(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $upload = UploadedFile::fake()->create('quote.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('files.upload'), [
            'title' => 'Repair quote',
            'category' => FileCategory::Quote->value,
            'file' => $upload,
            'linkable_type' => 'asset',
            'linkable_id' => $asset->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseHas('file_links', [
            'linkable_type' => Asset::class,
            'linkable_id' => $asset->id,
        ]);

        $file = File::query()->first();
        $this->assertSame(FileSourceType::Upload, $file->source_type);
        Storage::disk('local')->assertExists($file->path);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => 'file_linked',
        ]);
    }

    public function test_identical_upload_reuses_storage_blob_across_assets(): void
    {
        $user = $this->scanner();
        $assetA = $this->makeAsset();
        $assetB = $this->makeAsset($assetA->itemType);

        $bytes = '%PDF-1.4 identical content for dedup test';
        $first = UploadedFile::fake()->createWithContent('manual.pdf', $bytes);
        $second = UploadedFile::fake()->createWithContent('manual-copy.pdf', $bytes);

        $this->actingAs($user)->post(route('files.upload'), [
            'title' => 'Manual',
            'category' => FileCategory::Manual->value,
            'file' => $first,
            'linkable_type' => 'asset',
            'linkable_id' => $assetA->id,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('files.upload'), [
            'title' => 'Manual again',
            'category' => FileCategory::Manual->value,
            'file' => $second,
            'linkable_type' => 'asset',
            'linkable_id' => $assetB->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseCount('file_links', 2);

        $file = File::query()->first();
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_item_type_file_appears_as_inherited_on_asset_files_tab(): void
    {
        $manager = $this->manager();
        $itemType = $this->makeItemType();
        $asset = $this->makeAsset($itemType);

        $upload = UploadedFile::fake()->create('manual.pdf', 50, 'application/pdf');

        $this->actingAs($manager)->post(route('files.upload'), [
            'title' => 'Shared manual',
            'category' => FileCategory::Manual->value,
            'file' => $upload,
            'linkable_type' => 'item_type',
            'linkable_id' => $itemType->id,
        ])->assertRedirect();

        $response = $this->actingAs($manager)->get(route('assets.show', [$asset, 'tab' => 'files']));

        $response->assertOk();
        $response->assertSee('Shared manual');
        $response->assertSee('Via item type');
    }

    public function test_url_attachment_stores_no_path_and_download_redirects(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        $this->actingAs($user)->post(route('files.url'), [
            'title' => 'Manufacturer manual',
            'category' => FileCategory::Manual->value,
            'external_url' => 'https://example.com/docs/manual.pdf',
            'linkable_type' => 'asset',
            'linkable_id' => $asset->id,
        ])->assertRedirect();

        $file = File::query()->first();
        $this->assertSame(FileSourceType::Url, $file->source_type);
        $this->assertNull($file->path);
        $this->assertSame('https://example.com/docs/manual.pdf', $file->external_url);

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertRedirect('https://example.com/docs/manual.pdf');
    }

    public function test_unlink_deletes_library_file_when_last_link_removed(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $upload = UploadedFile::fake()->create('quote.pdf', 40, 'application/pdf');

        $this->actingAs($user)->post(route('files.upload'), [
            'title' => 'Quote',
            'category' => FileCategory::Quote->value,
            'file' => $upload,
            'linkable_type' => 'asset',
            'linkable_id' => $asset->id,
        ])->assertRedirect();

        $file = File::query()->first();
        $path = $file->path;
        $link = FileLink::query()->first();

        $this->actingAs($user)
            ->delete(route('files.unlink', $link))
            ->assertRedirect();

        $this->assertDatabaseCount('files', 0);
        $this->assertDatabaseCount('file_links', 0);
        Storage::disk('local')->assertMissing($path);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => 'file_unlinked',
        ]);
    }

    public function test_scanner_cannot_link_file_to_location(): void
    {
        $scanner = $this->scanner();
        $location = $this->makeLocation();
        $upload = UploadedFile::fake()->create('photo.jpg', 30, 'image/jpeg');

        $this->actingAs($scanner)->post(route('files.upload'), [
            'title' => 'Room photo',
            'category' => FileCategory::Photo->value,
            'file' => $upload,
            'linkable_type' => 'location',
            'linkable_id' => $location->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('files', 0);
    }

    public function test_manager_can_link_file_to_location_and_browse_library(): void
    {
        $manager = $this->manager();
        $location = $this->makeLocation();
        $upload = UploadedFile::fake()->create('floorplan.pdf', 80, 'application/pdf');

        $this->actingAs($manager)->post(route('files.upload'), [
            'title' => 'Floorplan',
            'category' => FileCategory::Floorplan->value,
            'file' => $upload,
            'linkable_type' => 'location',
            'linkable_id' => $location->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('files', 1);

        $this->actingAs($manager)
            ->get(route('files.index'))
            ->assertOk()
            ->assertSee('Floorplan');
    }

    public function test_scanner_cannot_browse_library_index(): void
    {
        $this->actingAs($this->scanner())
            ->get(route('files.index'))
            ->assertForbidden();
    }

    public function test_authenticated_user_can_download_upload(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $upload = UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf');

        $this->actingAs($user)->post(route('files.upload'), [
            'title' => 'Doc',
            'category' => FileCategory::Other->value,
            'file' => $upload,
            'linkable_type' => 'asset',
            'linkable_id' => $asset->id,
        ]);

        $file = File::query()->first();

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertOk();
    }
}

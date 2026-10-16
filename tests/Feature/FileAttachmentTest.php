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

    protected function linkFileToAsset(File $file, ?Asset $asset = null): Asset
    {
        $asset ??= $this->makeAsset();

        FileLink::query()->create([
            'file_id' => $file->id,
            'linkable_type' => Asset::class,
            'linkable_id' => $asset->id,
            'note' => null,
        ]);

        return $asset;
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
            ->assertOk()
            ->assertSee('Leaving the application', false)
            ->assertSee('example.com', false);
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

    public function test_url_download_rejects_non_http_schemes(): void
    {
        $user = $this->scanner();
        $file = File::factory()->url()->create([
            'external_url' => 'javascript:alert(1)',
        ]);
        $this->linkFileToAsset($file);

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertNotFound();
    }

    public function test_roleless_user_cannot_download_file(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->create();

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertForbidden();
    }

    public function test_scanner_can_download_linked_file(): void
    {
        $user = $this->scanner();
        $path = 'attachments/test/linked.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 linked');

        $file = File::factory()->create([
            'path' => $path,
            'mime_type' => 'application/pdf',
        ]);
        $this->linkFileToAsset($file);

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertOk();
    }

    public function test_scanner_cannot_download_unlinked_library_file(): void
    {
        $user = $this->scanner();
        $path = 'attachments/test/unlinked.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 unlinked');

        $file = File::factory()->create([
            'path' => $path,
            'mime_type' => 'application/pdf',
        ]);

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('files.preview', $file))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('files.thumbnail', $file))
            ->assertForbidden();
    }

    public function test_manager_can_download_unlinked_library_file(): void
    {
        $manager = $this->manager();
        $path = 'attachments/test/library.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 library');

        $file = File::factory()->create([
            'path' => $path,
            'mime_type' => 'application/pdf',
        ]);

        $this->actingAs($manager)
            ->get(route('files.download', $file))
            ->assertOk();
    }

    public function test_scanner_search_omits_unlinked_library_files(): void
    {
        $scanner = $this->scanner();
        $manager = $this->manager();

        $linked = File::factory()->create(['title' => 'Shared Spec Alpha']);
        $this->linkFileToAsset($linked);
        $unlinked = File::factory()->create(['title' => 'Shared Spec Beta']);

        $scannerResults = $this->actingAs($scanner)
            ->getJson(route('files.search', ['q' => 'Shared Spec']))
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $scannerResults);
        $this->assertSame($linked->id, $scannerResults[0]['id']);
        $this->assertNotContains($unlinked->id, collect($scannerResults)->pluck('id')->all());

        $managerResults = $this->actingAs($manager)
            ->getJson(route('files.search', ['q' => 'Shared Spec']))
            ->assertOk()
            ->json('data');

        $managerIds = collect($managerResults)->pluck('id')->all();
        $this->assertContains($linked->id, $managerIds);
        $this->assertContains($unlinked->id, $managerIds);
    }

    public function test_untrusted_external_url_shows_leave_app_confirm(): void
    {
        $user = $this->scanner();
        $file = File::factory()->url()->create([
            'external_url' => 'https://docs.vendor.com/a/manual.pdf',
        ]);
        $this->linkFileToAsset($file);

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertOk()
            ->assertSee('Leaving the application', false)
            ->assertSee('docs.vendor.com', false)
            ->assertSee('https://docs.vendor.com/a/manual.pdf', false)
            ->assertDontSee('redirect', false);
    }

    public function test_trusting_domain_on_one_file_skips_confirm_for_another_on_same_host(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $fileA = File::factory()->url()->create([
            'external_url' => 'https://docs.vendor.com/a/manual.pdf',
        ]);
        $fileB = File::factory()->url()->create([
            'external_url' => 'https://docs.vendor.com/b/other.pdf',
        ]);
        $fileC = File::factory()->url()->create([
            'external_url' => 'https://other.example.org/x.pdf',
        ]);
        $this->linkFileToAsset($fileA, $asset);
        $this->linkFileToAsset($fileB, $asset);
        $this->linkFileToAsset($fileC, $asset);

        $this->actingAs($user)
            ->post(route('files.external.continue', $fileA), ['trust' => '1'])
            ->assertRedirect('https://docs.vendor.com/a/manual.pdf');

        $user->refresh();
        $this->assertContains('docs.vendor.com', data_get($user->preferences, 'trusted_external_domains'));

        $this->actingAs($user)
            ->get(route('files.download', $fileB))
            ->assertRedirect('https://docs.vendor.com/b/other.pdf');

        $this->actingAs($user)
            ->get(route('files.preview', $fileC))
            ->assertOk()
            ->assertSee('Leaving the application', false)
            ->assertSee('other.example.org', false);
    }

    public function test_continue_once_does_not_persist_trust(): void
    {
        $user = $this->scanner();
        $file = File::factory()->url()->create([
            'external_url' => 'https://once.example.com/doc.pdf',
        ]);
        $this->linkFileToAsset($file);

        $this->actingAs($user)
            ->post(route('files.external.continue', $file), ['trust' => '0'])
            ->assertRedirect('https://once.example.com/doc.pdf');

        $user->refresh();
        $this->assertNotContains('once.example.com', data_get($user->preferences, 'trusted_external_domains', []));

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertOk()
            ->assertSee('Leaving the application', false);
    }

    public function test_removing_trusted_domain_restores_confirm_for_all_files_on_host(): void
    {
        $user = $this->scanner();
        $user->preferences = [
            'trusted_external_domains' => ['docs.vendor.com'],
        ];
        $user->save();

        $asset = $this->makeAsset();
        $fileA = File::factory()->url()->create([
            'external_url' => 'https://docs.vendor.com/a/manual.pdf',
        ]);
        $fileB = File::factory()->url()->create([
            'external_url' => 'https://docs.vendor.com/b/other.pdf',
        ]);
        $this->linkFileToAsset($fileA, $asset);
        $this->linkFileToAsset($fileB, $asset);

        $this->actingAs($user)
            ->get(route('files.download', $fileA))
            ->assertRedirect('https://docs.vendor.com/a/manual.pdf');

        $this->actingAs($user)
            ->delete(route('preferences.trusted-external-domains.destroy'), [
                'host' => 'docs.vendor.com',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertNotContains('docs.vendor.com', data_get($user->preferences, 'trusted_external_domains', []));

        $this->actingAs($user)
            ->get(route('files.download', $fileA))
            ->assertOk()
            ->assertSee('Leaving the application', false);

        $this->actingAs($user)
            ->get(route('files.preview', $fileB))
            ->assertOk()
            ->assertSee('Leaving the application', false);
    }

    public function test_guest_cannot_preview_file(): void
    {
        $file = File::factory()->create();

        $this->get(route('files.preview', $file))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_preview_pdf_inline(): void
    {
        $user = $this->scanner();
        $path = 'attachments/test/preview.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 preview test');

        $file = File::factory()->create([
            'path' => $path,
            'mime_type' => 'application/pdf',
            'original_filename' => 'preview.pdf',
        ]);
        $this->linkFileToAsset($file);

        $response = $this->actingAs($user)->get(route('files.preview', $file));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('filename=', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_authenticated_user_can_preview_jpeg_inline(): void
    {
        $user = $this->scanner();
        $path = 'attachments/test/preview.jpg';
        Storage::disk('local')->put($path, 'fake-jpeg-bytes');

        $file = File::factory()->create([
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'original_filename' => 'preview.jpg',
        ]);
        $this->linkFileToAsset($file);

        $response = $this->actingAs($user)->get(route('files.preview', $file));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function test_preview_rejects_non_previewable_upload(): void
    {
        $user = $this->scanner();
        $path = 'attachments/test/data.csv';
        Storage::disk('local')->put($path, "a,b\n1,2\n");

        $file = File::factory()->create([
            'path' => $path,
            'mime_type' => 'text/csv',
            'original_filename' => 'data.csv',
        ]);
        $this->linkFileToAsset($file);

        $this->actingAs($user)
            ->get(route('files.preview', $file))
            ->assertStatus(415);
    }

    public function test_thumbnail_route_streams_when_thumbnail_path_set(): void
    {
        $user = $this->scanner();
        $thumbPath = 'attachments/test/thumb.jpg';
        Storage::disk('local')->put($thumbPath, 'thumb-bytes');

        $file = File::factory()->create([
            'mime_type' => 'image/jpeg',
            'thumbnail_path' => $thumbPath,
        ]);
        $this->linkFileToAsset($file);

        $this->actingAs($user)
            ->get(route('files.thumbnail', $file))
            ->assertOk();
    }

    public function test_manager_can_view_file_show_with_linked_entities(): void
    {
        $manager = $this->manager();
        $itemType = $this->makeItemType('Shared Type');
        $asset = $this->makeAsset($itemType);
        $asset->update(['name' => 'Linked Camera']);

        $file = File::factory()->create(['title' => 'Shared Manual']);

        FileLink::query()->create([
            'file_id' => $file->id,
            'linkable_type' => Asset::class,
            'linkable_id' => $asset->id,
            'note' => 'On asset',
        ]);
        FileLink::query()->create([
            'file_id' => $file->id,
            'linkable_type' => ItemType::class,
            'linkable_id' => $itemType->id,
            'note' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('files.show', $file))
            ->assertOk()
            ->assertSee('Shared Manual')
            ->assertSee('Linked Camera')
            ->assertSee('Shared Type')
            ->assertSee('Asset')
            ->assertSee('Item type');
    }

    public function test_scanner_cannot_view_file_show(): void
    {
        $file = File::factory()->create();

        $this->actingAs($this->scanner())
            ->get(route('files.show', $file))
            ->assertForbidden();
    }

    public function test_file_show_renders_deleted_linkable_without_error(): void
    {
        $manager = $this->manager();
        $file = File::factory()->create(['title' => 'Orphan Link File']);
        $missingAssetId = 999_999;

        FileLink::query()->create([
            'file_id' => $file->id,
            'linkable_type' => Asset::class,
            'linkable_id' => $missingAssetId,
            'note' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('files.show', $file))
            ->assertOk()
            ->assertSee('Orphan Link File')
            ->assertSee('Deleted (#'.$missingAssetId.')');
    }
}

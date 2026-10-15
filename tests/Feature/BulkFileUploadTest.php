<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Models\Asset;
use App\Models\File;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('local');
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

    protected function makeAsset(): Asset
    {
        $itemType = ItemType::query()->create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        return Asset::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'Test Asset',
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-'.fake()->unique()->numerify('####'),
        ]);
    }

    public function test_guest_cannot_bulk_upload(): void
    {
        $this->post(route('files.bulk-upload'), [
            'files' => [UploadedFile::fake()->create('a.pdf', 20, 'application/pdf')],
            'titles' => ['A'],
            'categories' => [FileCategory::Other->value],
        ])->assertRedirect(route('login'));
    }

    public function test_scanner_cannot_bulk_upload_to_library_without_linkable(): void
    {
        $user = $this->scanner();

        $this->actingAs($user)->post(route('files.bulk-upload'), [
            'files' => [UploadedFile::fake()->create('a.pdf', 20, 'application/pdf')],
            'titles' => ['A'],
            'categories' => [FileCategory::Other->value],
        ])->assertForbidden();

        $this->assertDatabaseCount('files', 0);
    }

    public function test_manager_can_bulk_upload_to_library(): void
    {
        $user = $this->manager();
        $files = [
            UploadedFile::fake()->create('manual.pdf', 40, 'application/pdf'),
            UploadedFile::fake()->create('invoice.pdf', 30, 'application/pdf'),
        ];

        $response = $this->actingAs($user)->post(route('files.bulk-upload'), [
            'files' => $files,
            'titles' => ['Device manual', 'Supplier invoice'],
            'categories' => [FileCategory::Manual->value, FileCategory::Invoice->value],
            'notes' => ['Rev A', null],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', '2 files uploaded.');
        $this->assertDatabaseCount('files', 2);
        $this->assertDatabaseHas('files', [
            'title' => 'Device manual',
            'category' => FileCategory::Manual->value,
        ]);
        $this->assertDatabaseHas('files', [
            'title' => 'Supplier invoice',
            'category' => FileCategory::Invoice->value,
        ]);

        File::query()->each(function (File $file): void {
            Storage::disk('local')->assertExists($file->path);
        });
    }

    public function test_scanner_can_bulk_upload_and_link_to_asset(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        $response = $this->actingAs($user)->post(route('files.bulk-upload'), [
            'files' => [
                UploadedFile::fake()->create('quote-1.pdf', 25, 'application/pdf'),
                UploadedFile::fake()->create('quote-2.pdf', 25, 'application/pdf'),
            ],
            'titles' => ['Quote 1', 'Quote 2'],
            'categories' => [FileCategory::Quote->value, FileCategory::Quote->value],
            'notes' => ['First', 'Second'],
            'linkable_type' => 'asset',
            'linkable_id' => $asset->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('files', 2);
        $this->assertDatabaseCount('file_links', 2);
        $this->assertDatabaseHas('file_links', [
            'linkable_type' => Asset::class,
            'linkable_id' => $asset->id,
            'note' => 'First',
        ]);
    }

    public function test_bulk_upload_requires_matching_titles_and_categories(): void
    {
        $user = $this->manager();

        $response = $this->actingAs($user)->from(route('files.index'))->post(route('files.bulk-upload'), [
            'files' => [
                UploadedFile::fake()->create('a.pdf', 20, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 20, 'application/pdf'),
            ],
            'titles' => ['Only one title'],
            'categories' => [FileCategory::Other->value, FileCategory::Other->value],
        ]);

        $response->assertRedirect(route('files.index'));
        $response->assertSessionHasErrors('titles');
        $this->assertDatabaseCount('files', 0);
    }

    public function test_bulk_upload_rejects_unsupported_mime(): void
    {
        $user = $this->manager();

        $response = $this->actingAs($user)->from(route('files.index'))->post(route('files.bulk-upload'), [
            'files' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
            'titles' => ['Notes'],
            'categories' => [FileCategory::Other->value],
        ]);

        $response->assertRedirect(route('files.index'));
        $response->assertSessionHasErrors('files.0');
        $this->assertDatabaseCount('files', 0);
    }

    public function test_files_index_shows_bulk_upload_button(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->get(route('files.index'))
            ->assertOk()
            ->assertSee('Bulk upload')
            ->assertSee(route('files.bulk-upload'), false);
    }
}

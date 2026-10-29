<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AssetExportSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_export_redirects_to_login(): void
    {
        $this->get(route('export.assets'))
            ->assertRedirect(route('login'));
    }

    public function test_scanner_cannot_export_assets(): void
    {
        $scanner = User::factory()->create();
        $scanner->assignRole('scanner');

        $this->actingAs($scanner)
            ->get(route('export.assets'))
            ->assertForbidden();
    }

    public function test_export_without_a_selection_includes_every_asset(): void
    {
        $manager = $this->manager();
        Asset::factory()->create(['name' => 'Alpha Speaker']);
        Asset::factory()->create(['name' => 'Bravo Speaker']);

        $response = $this->actingAs($manager)->get(route('export.assets', [
            'format' => 'csv',
        ]));

        $contents = $this->downloadContents($response);

        $this->assertStringContainsString('Alpha Speaker', $contents);
        $this->assertStringContainsString('Bravo Speaker', $contents);
    }

    public function test_export_with_a_blank_selection_includes_every_asset(): void
    {
        $manager = $this->manager();
        Asset::factory()->create(['name' => 'Alpha Speaker']);
        Asset::factory()->create(['name' => 'Bravo Speaker']);

        $response = $this->actingAs($manager)->post(route('export.assets'), [
            'format' => 'csv',
            'asset_ids' => '',
        ]);

        $contents = $this->downloadContents($response);

        $this->assertStringContainsString('Alpha Speaker', $contents);
        $this->assertStringContainsString('Bravo Speaker', $contents);
    }

    public function test_export_with_selected_ids_includes_only_those_assets(): void
    {
        $manager = $this->manager();
        $selected = Asset::factory()->create(['name' => 'Selected Speaker']);
        Asset::factory()->create(['name' => 'Unselected Speaker']);

        $response = $this->actingAs($manager)->post(route('export.assets'), [
            'format' => 'csv',
            'asset_ids' => ' '.$selected->id.',,'.$selected->id.' ',
        ]);

        $contents = $this->downloadContents($response);

        $this->assertStringContainsString('Selected Speaker', $contents);
        $this->assertStringNotContainsString('Unselected Speaker', $contents);
    }

    public function test_export_omits_assets_outside_the_department_scope(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $manager = $this->manager();
        $manager->departments()->sync([$department->id]);

        $type = ItemType::factory()->create();
        $visible = Asset::withoutDepartmentScope()->create([
            'department_id' => $department->id,
            'name' => 'Visible Speaker',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'quantity' => 1,
        ]);
        $hidden = Asset::withoutDepartmentScope()->create([
            'department_id' => $otherDepartment->id,
            'name' => 'Hidden Speaker',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($manager)->post(route('export.assets'), [
            'format' => 'csv',
            'asset_ids' => $visible->id.','.$hidden->id,
        ]);

        $contents = $this->downloadContents($response);

        $this->assertStringContainsString('Visible Speaker', $contents);
        $this->assertStringNotContainsString('Hidden Speaker', $contents);
    }

    public function test_export_rejects_non_numeric_selected_ids(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->from(route('assets.index'))
            ->post(route('export.assets'), [
                'asset_ids' => '12,abc',
            ])
            ->assertRedirect(route('assets.index'))
            ->assertSessionHasErrors([
                'asset_ids' => 'Selected assets must be a list of asset ids.',
            ]);
    }

    public function test_export_rejects_more_selected_ids_than_the_export_limit(): void
    {
        config(['performance.export_max_rows' => 2]);

        $manager = $this->manager();

        $this->actingAs($manager)
            ->from(route('assets.index'))
            ->post(route('export.assets'), [
                'asset_ids' => '1,2,3',
            ])
            ->assertRedirect(route('assets.index'))
            ->assertSessionHasErrors([
                'asset_ids' => 'Select at most 2 assets to export.',
            ]);
    }

    public function test_assets_index_posts_the_current_selection_to_export(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('id="assets-export-form"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('name="asset_ids"', false);
        $response->assertSee('selectedIds.join', false);
        $response->assertSee('form="assets-export-form"', false);
        $response->assertSee('Export selected', false);
        $response->assertSee('publishSelection', false);
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    protected function downloadContents(TestResponse $response): string
    {
        $response->assertOk();
        $response->assertDownload();

        $file = $response->baseResponse->getFile();

        return (string) file_get_contents($file->getPathname());
    }
}

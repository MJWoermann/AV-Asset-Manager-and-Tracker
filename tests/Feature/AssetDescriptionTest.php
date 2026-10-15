<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetDescriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
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

    public function test_store_persists_description_on_asset(): void
    {
        $user = $this->manager();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Test Fixture',
            'description' => 'Front of house wash light',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
        ]);

        $asset = Asset::query()->where('name', 'Test Fixture')->first();

        $this->assertNotNull($asset);
        $this->assertSame('Front of house wash light', $asset->description);
        $response->assertRedirect(route('assets.show', $asset));
    }

    public function test_update_persists_description_on_asset(): void
    {
        $user = $this->manager();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        $asset = Asset::create([
            'name' => 'Test Fixture',
            'description' => 'Original description',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->put(route('assets.update', $asset), [
            'name' => 'Test Fixture',
            'description' => 'Updated description',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
        ]);

        $response->assertRedirect(route('assets.show', $asset));
        $this->assertSame('Updated description', $asset->fresh()->description);
    }

    public function test_show_page_displays_description(): void
    {
        $user = $this->scanner();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        $asset = Asset::create([
            'name' => 'Test Fixture',
            'description' => 'Visible on detail page',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', $asset));

        $response->assertOk();
        $response->assertSee('Visible on detail page');
    }

    public function test_search_matches_description(): void
    {
        $user = $this->scanner();
        $itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        Asset::create([
            'name' => 'Quiet Name',
            'description' => 'UniquePhraseForSearch',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'UniquePhraseForSearch']));

        $response->assertOk();
        $response->assertSee('Quiet Name');
        $response->assertSee('<mark class="bg-brand/30 text-inherit rounded-sm px-0.5">UniquePhraseForSearch</mark>', false);
    }
}

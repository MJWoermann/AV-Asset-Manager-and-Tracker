<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\ItemTypeAlias;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTypeDeleteTest extends TestCase
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

    public function test_edit_form_shows_delete_confirm_with_replacement_options(): void
    {
        $user = $this->manager();
        $keep = ItemType::create(['name' => 'Keep Type', 'slug' => 'keep-type']);
        $delete = ItemType::create(['name' => 'Delete Type', 'slug' => 'delete-type']);

        $response = $this->actingAs($user)->get(route('item-types.edit', $delete));

        $response->assertOk();
        $response->assertSee('Delete item type', false);
        $response->assertSee('confirm-item-type-deletion', false);
        $response->assertSee('name="replacement_item_type_id"', false);
        $response->assertSee($keep->name, false);
        $response->assertDontSee('<option value="'.$delete->id.'"', false);
    }

    public function test_last_item_type_cannot_be_deleted_from_ui(): void
    {
        $user = $this->manager();
        $only = ItemType::create(['name' => 'Only Type', 'slug' => 'only-type']);

        $response = $this->actingAs($user)->get(route('item-types.edit', $only));

        $response->assertOk();
        $response->assertSee('Create another item type before deleting', false);
        $response->assertDontSee('Delete item type', false);
    }

    public function test_manager_can_delete_item_type_and_reassign_assets(): void
    {
        $user = $this->manager();
        $keep = ItemType::create(['name' => 'Keep Type', 'slug' => 'keep-type']);
        $delete = ItemType::create(['name' => 'Delete Type', 'slug' => 'delete-type']);
        $asset = Asset::create([
            'name' => 'Fixture A',
            'item_type_id' => $delete->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->delete(route('item-types.destroy', $delete), [
            'replacement_item_type_id' => $keep->id,
        ]);

        $response->assertRedirect(route('item-types.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('item_types', ['id' => $delete->id]);
        $this->assertSame($keep->id, $asset->fresh()->item_type_id);
        $this->assertDatabaseHas('item_type_aliases', [
            'former_name' => 'Delete Type',
            'former_slug' => 'delete-type',
            'item_type_id' => $keep->id,
        ]);
        $this->assertSame(1, ItemTypeAlias::query()->count());
    }

    public function test_delete_requires_replacement_item_type(): void
    {
        $user = $this->manager();
        $keep = ItemType::create(['name' => 'Keep Type', 'slug' => 'keep-type']);
        $delete = ItemType::create(['name' => 'Delete Type', 'slug' => 'delete-type']);

        $response = $this->actingAs($user)->from(route('item-types.edit', $delete))
            ->delete(route('item-types.destroy', $delete));

        $response->assertRedirect(route('item-types.edit', $delete));
        $response->assertSessionHasErrors('replacement_item_type_id');
        $this->assertDatabaseHas('item_types', ['id' => $delete->id]);
        $this->assertDatabaseHas('item_types', ['id' => $keep->id]);
    }

    public function test_replacement_cannot_be_the_type_being_deleted(): void
    {
        $user = $this->manager();
        ItemType::create(['name' => 'Keep Type', 'slug' => 'keep-type']);
        $delete = ItemType::create(['name' => 'Delete Type', 'slug' => 'delete-type']);

        $response = $this->actingAs($user)->from(route('item-types.edit', $delete))
            ->delete(route('item-types.destroy', $delete), [
                'replacement_item_type_id' => $delete->id,
            ]);

        $response->assertRedirect(route('item-types.edit', $delete));
        $response->assertSessionHasErrors('replacement_item_type_id');
        $this->assertDatabaseHas('item_types', ['id' => $delete->id]);
    }

    public function test_scanner_cannot_delete_item_type(): void
    {
        $user = $this->scanner();
        $keep = ItemType::create(['name' => 'Keep Type', 'slug' => 'keep-type']);
        $delete = ItemType::create(['name' => 'Delete Type', 'slug' => 'delete-type']);

        $response = $this->actingAs($user)->delete(route('item-types.destroy', $delete), [
            'replacement_item_type_id' => $keep->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('item_types', ['id' => $delete->id]);
    }
}

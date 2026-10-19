<?php

namespace Tests\Feature;

use App\Models\AssetList;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUpdateAssetListsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    public function test_admin_can_bulk_update_list_department(): void
    {
        $user = $this->admin();
        $target = Department::factory()->create([
            'name' => 'Lighting',
            'slug' => 'lighting',
            'is_active' => true,
        ]);

        $a = AssetList::create([
            'name' => 'Inventory A',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        $b = AssetList::create([
            'name' => 'Event B',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('lists.bulk-update'), [
            'action' => 'update',
            'asset_list_ids' => [$a->id, $b->id],
            'update_department' => '1',
            'department_id' => $target->id,
        ]);

        $response->assertRedirect(route('lists.index'));
        $response->assertSessionHas('status');

        $this->assertSame($target->id, (int) $a->fresh()->department_id);
        $this->assertSame($target->id, (int) $b->fresh()->department_id);
    }

    public function test_non_admin_cannot_bulk_update_list_department(): void
    {
        $user = $this->manager();
        $target = Department::factory()->create([
            'name' => 'Audio',
            'slug' => 'audio',
            'is_active' => true,
        ]);
        $list = AssetList::create([
            'name' => 'Stock',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        $originalDepartmentId = (int) $list->department_id;

        $response = $this->actingAs($user)->post(route('lists.bulk-update'), [
            'action' => 'update',
            'asset_list_ids' => [$list->id],
            'update_department' => '1',
            'department_id' => $target->id,
        ]);

        $response->assertForbidden();
        $this->assertSame($originalDepartmentId, (int) $list->fresh()->department_id);
    }

    public function test_admin_sees_list_bulk_department_controls(): void
    {
        $user = $this->admin();
        AssetList::create([
            'name' => 'Visible list',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('lists.index'));

        $response->assertOk();
        $response->assertSee('Update department');
        $response->assertSee('data-bulk-list-id', false);
        $response->assertSee('name="update_department"', false);
    }

    public function test_manager_does_not_see_list_bulk_department_controls(): void
    {
        $user = $this->manager();
        AssetList::create([
            'name' => 'Manager list',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('lists.index'));

        $response->assertOk();
        $response->assertDontSee('Update department');
        $response->assertDontSee('data-bulk-list-id', false);
    }

    public function test_bulk_update_requires_department_selection(): void
    {
        $user = $this->admin();
        $list = AssetList::create([
            'name' => 'Needs field',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->from(route('lists.index'))->post(route('lists.bulk-update'), [
            'action' => 'update',
            'asset_list_ids' => [$list->id],
        ]);

        $response->assertRedirect(route('lists.index'));
        $response->assertSessionHasErrors('update_department');
    }
}

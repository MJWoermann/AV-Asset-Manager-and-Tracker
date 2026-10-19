<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUpdateItemTypesTest extends TestCase
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

    public function test_admin_can_bulk_update_item_type_department(): void
    {
        $user = $this->admin();
        $target = Department::factory()->create([
            'name' => 'Lighting',
            'slug' => 'lighting',
            'is_active' => true,
        ]);

        $a = ItemType::create([
            'name' => 'Type A',
            'slug' => 'type-a',
        ]);
        $b = ItemType::create([
            'name' => 'Type B',
            'slug' => 'type-b',
        ]);

        $response = $this->actingAs($user)->post(route('item-types.bulk-update'), [
            'action' => 'update',
            'item_type_ids' => [$a->id, $b->id],
            'update_department' => '1',
            'department_id' => $target->id,
        ]);

        $response->assertRedirect(route('item-types.index'));
        $response->assertSessionHas('status');

        $this->assertSame($target->id, (int) $a->fresh()->department_id);
        $this->assertSame($target->id, (int) $b->fresh()->department_id);
    }

    public function test_non_admin_cannot_bulk_update_item_type_department(): void
    {
        $user = $this->manager();
        $target = Department::factory()->create([
            'name' => 'Audio',
            'slug' => 'audio',
            'is_active' => true,
        ]);
        $type = ItemType::create([
            'name' => 'Console',
            'slug' => 'console',
        ]);
        $originalDepartmentId = (int) $type->department_id;

        $response = $this->actingAs($user)->post(route('item-types.bulk-update'), [
            'action' => 'update',
            'item_type_ids' => [$type->id],
            'update_department' => '1',
            'department_id' => $target->id,
        ]);

        $response->assertForbidden();
        $this->assertSame($originalDepartmentId, (int) $type->fresh()->department_id);
    }

    public function test_admin_sees_item_type_bulk_department_controls(): void
    {
        $user = $this->admin();
        ItemType::create([
            'name' => 'Visible type',
            'slug' => 'visible-type',
        ]);

        $response = $this->actingAs($user)->get(route('item-types.index'));

        $response->assertOk();
        $response->assertSee('Update department');
        $response->assertSee('data-bulk-item-type-id', false);
        $response->assertSee('name="update_department"', false);
    }

    public function test_manager_does_not_see_item_type_bulk_department_controls(): void
    {
        $user = $this->manager();
        ItemType::create([
            'name' => 'Manager type',
            'slug' => 'manager-type',
        ]);

        $response = $this->actingAs($user)->get(route('item-types.index'));

        $response->assertOk();
        $response->assertDontSee('Update department');
        $response->assertDontSee('data-bulk-item-type-id', false);
    }

    public function test_bulk_update_requires_department_selection(): void
    {
        $user = $this->admin();
        $type = ItemType::create([
            'name' => 'Needs field',
            'slug' => 'needs-field',
        ]);

        $response = $this->actingAs($user)->from(route('item-types.index'))->post(route('item-types.bulk-update'), [
            'action' => 'update',
            'item_type_ids' => [$type->id],
        ]);

        $response->assertRedirect(route('item-types.index'));
        $response->assertSessionHasErrors('update_department');
    }
}

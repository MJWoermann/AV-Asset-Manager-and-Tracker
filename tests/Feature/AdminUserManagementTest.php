<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function scanner(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_admin_can_delete_non_admin_user(): void
    {
        $admin = $this->admin();
        $target = $this->scanner();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $target));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', 'User deleted.');
        $this->assertModelMissing($target);
    }

    public function test_admin_cannot_delete_another_admin(): void
    {
        $admin = $this->admin();
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $otherAdmin));

        $response->assertForbidden();
        $this->assertModelExists($otherAdmin);
    }

    public function test_non_admin_cannot_delete_users(): void
    {
        $scanner = $this->scanner();
        $target = User::factory()->create();
        $target->assignRole('inventory_manager');

        $response = $this->actingAs($scanner)->delete(route('admin.users.destroy', $target));

        $response->assertForbidden();
        $this->assertModelExists($target);
    }

    public function test_users_index_shows_delete_only_for_non_admins(): void
    {
        $admin = $this->admin();
        $scanner = User::factory()->create(['name' => 'Scanner User']);
        $scanner->assignRole('scanner');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Delete', false);
        $response->assertSee(route('admin.users.destroy', $scanner), false);
        $response->assertDontSee(route('admin.users.destroy', $admin), false);
    }
}

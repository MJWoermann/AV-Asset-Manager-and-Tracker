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
        $response->assertSee('action="'.route('admin.users.destroy', $scanner).'"', false);
        $response->assertDontSee('action="'.route('admin.users.destroy', $admin).'"', false);
    }

    public function test_create_user_form_marks_required_fields_and_password_rules(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee('Required field', false);
        $response->assertSee('At least 8 characters, one uppercase letter, and one number or symbol.', false);
        $response->assertDontSee('Leave the password blank unless you want to change it.', false);
    }

    public function test_edit_user_form_notes_password_is_optional(): void
    {
        $admin = $this->admin();
        $target = $this->scanner();

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $target));

        $response->assertOk();
        $response->assertSee('Leave the password blank unless you want to change it. Only enter a new password if you intend to update it.', false);
        $response->assertSee('Optional. Only fill this in to change the password.', false);
    }

    public function test_store_rejects_passwords_that_fail_complexity_rules(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Weak Password User',
                'email' => 'weak@example.com',
                'password' => 'password',
                'role' => 'scanner',
                'theme' => 'light',
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_update_rejects_passwords_that_fail_complexity_rules(): void
    {
        $admin = $this->admin();
        $target = $this->scanner();
        $originalHash = $target->password;

        $response = $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'password',
                'role' => 'scanner',
                'theme' => 'light',
            ]);

        $response->assertRedirect(route('admin.users.edit', $target));
        $response->assertSessionHasErrors('password');
        $this->assertSame($originalHash, $target->refresh()->password);
    }
}

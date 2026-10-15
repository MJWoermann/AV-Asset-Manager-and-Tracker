<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_routes_return_404_when_disabled(): void
    {
        config(['app.allow_registration' => false]);

        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_screen_can_be_rendered_when_enabled(): void
    {
        config(['app.allow_registration' => true]);

        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register_when_enabled_but_cannot_access_app_without_role(): void
    {
        config(['app.allow_registration' => true]);
        $this->seed(RoleSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasAnyRole(['admin', 'inventory_manager', 'scanner']));

        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('assets.index'))->assertForbidden();
    }
}

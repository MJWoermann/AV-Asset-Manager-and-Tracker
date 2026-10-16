<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TokenAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_user_with_role_can_obtain_api_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'integration',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'abilities', 'user' => ['id', 'email', 'roles']])
            ->assertJsonPath('abilities', ['*'])
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_unauthenticated_api_request_includes_www_authenticate_bearer(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_password_issue_accepts_scoped_abilities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'scoped',
            'abilities' => ['user:read', 'assets:read'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('abilities', ['user:read', 'assets:read']);

        $token = $response->json('token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/assets')
            ->assertOk();
    }

    public function test_scoped_token_is_denied_without_required_ability(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        $token = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'read-only-user',
            'abilities' => ['user:read'],
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/assets')
            ->assertForbidden()
            ->assertJsonPath('message', 'Authenticated but token lacks required ability.');
    }

    public function test_any_valid_token_can_revoke_itself_without_ability(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        $token = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'narrow',
            'abilities' => ['user:read'],
        ])->json('token');

        $this->withToken($token)
            ->deleteJson('/api/auth/token')
            ->assertOk()
            ->assertJsonPath('message', 'Token revoked.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_roleless_user_cannot_obtain_api_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'integration',
        ])->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_roleless_sanctum_user_cannot_access_api_routes(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/assets')->assertForbidden();
        $this->getJson('/api/lists')->assertForbidden();
        $this->getJson('/api/user')->assertForbidden();
    }

    public function test_scanner_sanctum_user_can_list_assets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/assets')->assertOk();
    }

    public function test_scanner_with_assets_read_ability_can_list_assets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        Sanctum::actingAs($user, ['assets:read']);

        $this->getJson('/api/assets')->assertOk();
    }
}

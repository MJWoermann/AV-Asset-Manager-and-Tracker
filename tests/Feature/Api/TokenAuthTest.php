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
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'roles']]);

        $this->assertNotEmpty($response->json('token'));
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

        Sanctum::actingAs($user);

        $this->getJson('/api/assets')->assertForbidden();
        $this->getJson('/api/lists')->assertForbidden();
        $this->getJson('/api/user')->assertForbidden();
    }

    public function test_scanner_sanctum_user_can_list_assets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        Sanctum::actingAs($user);

        $this->getJson('/api/assets')->assertOk();
    }
}

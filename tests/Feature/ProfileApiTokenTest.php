<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ProfileApiTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(string $role = 'scanner'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_profile_page_lists_api_tokens(): void
    {
        $user = $this->userWithRole();
        $user->createToken('existing', ['assets:read']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('API tokens')
            ->assertSee('existing')
            ->assertSee('assets:read');
    }

    public function test_user_can_create_api_token_with_password(): void
    {
        $user = $this->userWithRole();

        $response = $this->actingAs($user)
            ->post('/profile/api-tokens', [
                'name' => 'CI bot',
                'password' => 'password',
                'abilities' => ['user:read', 'lists:read'],
            ]);

        $response
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'api-token-created')
            ->assertSessionHas('api_token_plain');

        $plain = session('api_token_plain');
        $this->assertIsString($plain);
        $this->assertNotEmpty($plain);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'CI bot',
        ]);

        $token = $user->tokens()->where('name', 'CI bot')->first();
        $this->assertNotNull($token);
        $this->assertSame(['user:read', 'lists:read'], $token->abilities);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee($plain);
    }

    public function test_creating_api_token_requires_current_password(): void
    {
        $user = $this->userWithRole();

        $this->actingAs($user)
            ->from('/profile')
            ->post('/profile/api-tokens', [
                'name' => 'CI bot',
                'password' => 'wrong-password',
                'abilities' => ['*'],
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_plaintext_token_is_not_shown_after_refresh(): void
    {
        $user = $this->userWithRole();
        $user->createToken('existing', ['*']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('existing')
            ->assertDontSee('api-token-created');
    }

    public function test_user_can_revoke_own_api_token(): void
    {
        $user = $this->userWithRole();
        $token = $user->createToken('to-revoke', ['scan'])->accessToken;

        $this->actingAs($user)
            ->delete('/profile/api-tokens/'.$token->id)
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'api-token-revoked');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->id,
        ]);
    }

    public function test_user_cannot_revoke_another_users_api_token(): void
    {
        $owner = $this->userWithRole();
        $other = $this->userWithRole();
        $token = $owner->createToken('secret', ['*'])->accessToken;

        $this->actingAs($other)
            ->delete('/profile/api-tokens/'.$token->id)
            ->assertNotFound();

        $this->assertInstanceOf(PersonalAccessToken::class, PersonalAccessToken::query()->find($token->id));
    }
}

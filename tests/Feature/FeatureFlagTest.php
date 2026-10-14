<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'feature:asset_connections'])
            ->get('/__test/feature/asset-connections', fn () => response('feature-ok'));

        Route::middleware(['web', 'auth', 'feature:email_integration'])
            ->get('/__test/feature/email-integration', fn () => response('email-feature-ok'));
    }

    public function test_profile_can_enable_asset_connections_feature_flag(): void
    {
        $user = User::factory()->create(['theme' => 'light']);

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'light',
                'features' => [
                    'asset_connections' => '1',
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertTrue($user->hasFeature('asset_connections'));
        $this->assertTrue(data_get($user->preferences, 'features.asset_connections'));
    }

    public function test_profile_can_disable_asset_connections_feature_flag(): void
    {
        $user = User::factory()->create([
            'theme' => 'light',
            'preferences' => [
                'features' => [
                    'asset_connections' => true,
                ],
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'light',
                'features' => [
                    'asset_connections' => '0',
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertFalse($user->hasFeature('asset_connections'));
        $this->assertFalse(data_get($user->preferences, 'features.asset_connections'));
    }

    public function test_feature_middleware_returns_404_when_flag_is_off(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasFeature('asset_connections'));

        $this->actingAs($user)
            ->get('/__test/feature/asset-connections')
            ->assertNotFound();
    }

    public function test_feature_middleware_allows_access_when_flag_is_on(): void
    {
        $user = User::factory()->create([
            'preferences' => [
                'features' => [
                    'asset_connections' => true,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get('/__test/feature/asset-connections')
            ->assertOk()
            ->assertSee('feature-ok');
    }

    public function test_email_integration_feature_flag_can_be_toggled_and_gates_middleware(): void
    {
        $user = User::factory()->create(['theme' => 'light']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'light',
                'features' => [
                    'email_integration' => '1',
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertTrue($user->hasFeature('email_integration'));

        $this->actingAs($user)
            ->get('/__test/feature/email-integration')
            ->assertOk()
            ->assertSee('email-feature-ok');

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'light',
                'features' => [
                    'email_integration' => '0',
                ],
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertFalse($user->hasFeature('email_integration'));

        $this->actingAs($user)
            ->get('/__test/feature/email-integration')
            ->assertNotFound();
    }
}

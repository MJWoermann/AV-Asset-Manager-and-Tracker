<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SsoSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SsoAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.azure.client_id' => 'azure-client-id',
            'services.azure.client_secret' => 'azure-client-secret',
            'services.azure.redirect' => 'http://localhost/auth/azure/callback',
            'services.azure.tenant' => '00000000-0000-0000-0000-000000000001',
        ]);
    }

    protected function admin(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function enableGoogleSso(): void
    {
        SsoSettings::put(true, false, ['example.com']);
    }

    protected function fakeGoogleUser(string $email, bool $emailVerified = true): SocialiteUser
    {
        return SocialiteUser::fake([
            'id' => 'google-123',
            'nickname' => 'sso-user',
            'name' => 'SSO User',
            'email' => $email,
            'email_verified' => $emailVerified,
        ]);
    }

    public function test_login_page_hides_sso_buttons_when_sso_is_not_configured(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertDontSee('Continue with Google');
        $response->assertDontSee('Continue with Microsoft');
    }

    public function test_login_page_shows_google_button_when_sso_is_visible(): void
    {
        $this->enableGoogleSso();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Continue with Google');
        $response->assertSee('Or sign in with password');
    }

    public function test_guest_cannot_view_sso_settings(): void
    {
        $this->get(route('admin.sso.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_update_sso_settings(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put(route('admin.sso.update'), [
            'google_enabled' => '1',
            'azure_enabled' => '1',
            'allowed_domains' => "example.com\n@corp.test",
        ]);

        $response->assertRedirect(route('admin.sso.edit'));
        $response->assertSessionHas('status', 'SSO settings saved.');

        $settings = SsoSettings::get();
        $this->assertTrue($settings['google_enabled']);
        $this->assertTrue($settings['azure_enabled']);
        $this->assertSame(['example.com', 'corp.test'], $settings['allowed_domains']);
    }

    public function test_sso_redirect_is_denied_when_provider_is_disabled(): void
    {
        SsoSettings::put(false, false, ['example.com']);

        $this->get(route('auth.social.redirect', ['provider' => 'google']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_sso_callback_rejects_unknown_email(): void
    {
        $this->enableGoogleSso();
        Socialite::fake('google', $this->fakeGoogleUser('unknown@example.com'));

        $this->get(route('auth.social.callback', ['provider' => 'google']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sso_callback_rejects_disallowed_domain(): void
    {
        $this->enableGoogleSso();
        User::factory()->create(['email' => 'user@other.com']);
        Socialite::fake('google', $this->fakeGoogleUser('user@other.com'));

        $this->get(route('auth.social.callback', ['provider' => 'google']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sso_callback_logs_in_existing_user_and_verifies_email(): void
    {
        $this->enableGoogleSso();
        $user = User::factory()->unverified()->create(['email' => 'staff@example.com']);
        Socialite::fake('google', $this->fakeGoogleUser('staff@example.com'));

        $this->get(route('auth.social.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_password_login_still_works_when_sso_is_enabled(): void
    {
        $this->enableGoogleSso();
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}

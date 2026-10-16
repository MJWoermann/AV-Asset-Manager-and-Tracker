<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TrustedExternalDomains;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(string $role = 'scanner'): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole($role);

        return $user;
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->userWithRole();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->userWithRole();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'theme' => 'light',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = $this->userWithRole();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'theme' => 'light',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = $this->userWithRole();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = $this->userWithRole();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_roleless_user_cannot_access_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertForbidden();
    }

    public function test_user_can_enable_scan_noise_preference(): void
    {
        $user = $this->userWithRole();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'light',
                'scan_noise' => '1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertTrue($user->wantsScanNoise());
        $this->assertTrue(data_get($user->preferences, 'scan_noise'));
    }

    public function test_user_can_disable_scan_noise_preference(): void
    {
        $user = $this->userWithRole();
        $user->preferences = ['scan_noise' => true];
        $user->save();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'light',
                'scan_noise' => '0',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertFalse($user->wantsScanNoise());
        $this->assertFalse(data_get($user->preferences, 'scan_noise'));
    }

    public function test_profile_update_without_scan_noise_preserves_existing_preference(): void
    {
        $user = $this->userWithRole();
        $user->preferences = ['scan_noise' => true];
        $user->save();

        $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Kept Noise',
                'email' => $user->email,
                'theme' => 'light',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Kept Noise', $user->name);
        $this->assertTrue($user->wantsScanNoise());
    }

    public function test_profile_page_shows_scan_noise_option(): void
    {
        $user = $this->userWithRole();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Enable scan noise', false)
            ->assertSee('name="scan_noise"', false)
            ->assertSee('Enable scan vibration', false)
            ->assertSee('name="scan_vibrate"', false);
    }

    public function test_user_can_enable_scan_vibrate_preference(): void
    {
        $user = $this->userWithRole();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'light',
                'scan_vibrate' => '1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertTrue($user->wantsScanVibrate());
        $this->assertTrue(data_get($user->preferences, 'scan_vibrate'));
    }

    public function test_user_can_disable_scan_vibrate_preference(): void
    {
        $user = $this->userWithRole();
        $user->preferences = ['scan_vibrate' => true];
        $user->save();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'light',
                'scan_vibrate' => '0',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertFalse($user->wantsScanVibrate());
        $this->assertFalse(data_get($user->preferences, 'scan_vibrate'));
    }

    public function test_profile_update_without_scan_vibrate_preserves_existing_preference(): void
    {
        $user = $this->userWithRole();
        $user->preferences = ['scan_vibrate' => true];
        $user->save();

        $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Kept Vibrate',
                'email' => $user->email,
                'theme' => 'light',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Kept Vibrate', $user->name);
        $this->assertTrue($user->wantsScanVibrate());
    }

    public function test_profile_page_lists_trusted_external_domains(): void
    {
        $user = $this->userWithRole();
        $user->preferences = TrustedExternalDomains::mergeAdd(null, 'https://docs.vendor.com/manual.pdf');
        $user->save();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Trusted external domains', false)
            ->assertSee('docs.vendor.com', false);
    }

    public function test_user_can_remove_trusted_external_domain_from_profile(): void
    {
        $user = $this->userWithRole();
        $user->preferences = [
            'trusted_external_domains' => ['docs.vendor.com', 'other.example.org'],
            'scan_noise' => true,
        ];
        $user->save();

        $this->actingAs($user)
            ->from('/profile')
            ->delete(route('preferences.trusted-external-domains.destroy'), [
                'host' => 'docs.vendor.com',
            ])
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame(['other.example.org'], TrustedExternalDomains::hosts($user->preferences));
        $this->assertTrue($user->wantsScanNoise());
    }
}

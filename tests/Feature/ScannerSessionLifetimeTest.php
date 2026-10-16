<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyScannerSessionLifetime;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScannerSessionLifetimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_scanner_receives_extended_session_lifetime_hint_cookie(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        $minutes = (int) config('session.lifetime_scanner');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertPlainCookie(ApplyScannerSessionLifetime::COOKIE, (string) $minutes);
    }

    public function test_inventory_manager_receives_extended_session_lifetime_hint_cookie(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $minutes = (int) config('session.lifetime_scanner');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertPlainCookie(ApplyScannerSessionLifetime::COOKIE, (string) $minutes);
    }

    public function test_admin_only_user_does_not_receive_extended_session_hint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertCookieMissing(ApplyScannerSessionLifetime::COOKIE);
    }

    public function test_hint_cookie_raises_session_lifetime_config_before_request(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        $scannerLifetime = (int) config('session.lifetime_scanner');
        $this->assertGreaterThan((int) config('session.lifetime'), $scannerLifetime);

        $this->withCookie(ApplyScannerSessionLifetime::COOKIE, (string) $scannerLifetime)
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame($scannerLifetime, (int) config('session.lifetime'));
    }
}

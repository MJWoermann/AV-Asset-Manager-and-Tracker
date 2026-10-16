<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guest_is_redirected_from_hub_routes(): void
    {
        $this->get(route('data.index'))->assertRedirect(route('login'));
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_data_and_reports_hubs(): void
    {
        $user = $this->userWithRole('scanner');

        $this->actingAs($user)->get(route('data.index'))
            ->assertOk()
            ->assertSee('Locations')
            ->assertSee('Manufacturers')
            ->assertDontSee('Item Types');

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Compare')
            ->assertSee('Unknown scans');
    }

    public function test_inventory_manager_sees_manager_data_links(): void
    {
        $user = $this->userWithRole('inventory_manager');

        $this->actingAs($user)->get(route('data.index'))
            ->assertOk()
            ->assertSee('Item Types')
            ->assertSee('Field sets')
            ->assertSee('Files');
    }

    public function test_admin_can_view_admin_hub(): void
    {
        $user = $this->userWithRole('admin');

        $this->actingAs($user)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Manage users')
            ->assertSee('Export columns')
            ->assertSee('Storage')
            ->assertSee('SSO');
    }

    public function test_non_admin_cannot_view_admin_hub(): void
    {
        $user = $this->userWithRole('inventory_manager');

        $this->actingAs($user)->get(route('admin.index'))->assertForbidden();
    }

    public function test_mobile_nav_links_to_hubs_instead_of_sub_pages(): void
    {
        $user = $this->userWithRole('admin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('data.index'), false);
        $response->assertSee(route('reports.index'), false);
        $response->assertSee(route('admin.index'), false);

        // Desktop dropdowns still list sub-pages; the mobile drawer should only link hubs.
        $content = $response->getContent();
        $matched = preg_match(
            '#class="hidden sm:hidden border-t border-brand-charcoal".*?</nav>#s',
            $content,
            $matches
        );
        $this->assertSame(1, $matched);
        $mobileNav = $matches[0];

        $this->assertStringContainsString(route('data.index'), $mobileNav);
        $this->assertStringContainsString(route('reports.index'), $mobileNav);
        $this->assertStringContainsString(route('admin.index'), $mobileNav);
        $this->assertStringNotContainsString(route('locations.index'), $mobileNav);
        $this->assertStringNotContainsString(route('reports.compare'), $mobileNav);
        $this->assertStringNotContainsString(route('admin.users.index'), $mobileNav);
    }
}

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
        $this->get(route('maintenance.index'))->assertRedirect(route('login'));
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_data_reports_and_maintenance_hubs(): void
    {
        $user = $this->userWithRole('scanner');

        $this->actingAs($user)->get(route('data.index'))
            ->assertOk()
            ->assertSee('Locations')
            ->assertSee('Manufacturers')
            ->assertSee('Reference data used across assets, lists, and scanning.')
            ->assertDontSee('Critical spares register, kits, and usage history.')
            ->assertDontSee('Open and completed repair work orders linked to faults.')
            ->assertDontSee('Item Types')
            ->assertDontSee('Repair procedures');

        $this->actingAs($user)->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Repairs, procedures, and critical spares.')
            ->assertSee('Repairs')
            ->assertSee('Spares')
            ->assertSee('Critical spares register, kits, and usage history.')
            ->assertSee('Open and completed repair work orders linked to faults.')
            ->assertDontSee('Reusable SOP checklists scoped to models, types, or spares.');

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Compare')
            ->assertSee('Unknown scans')
            ->assertDontSee('Checkout holdings');
    }

    public function test_inventory_manager_sees_manager_data_and_maintenance_links(): void
    {
        $user = $this->userWithRole('inventory_manager');

        $this->actingAs($user)->get(route('data.index'))
            ->assertOk()
            ->assertSee('Item Types')
            ->assertSee('Field sets')
            ->assertSee('Files')
            ->assertDontSee('Reusable SOP checklists scoped to models, types, or spares.');

        $this->actingAs($user)->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Repairs')
            ->assertSee('Spares')
            ->assertSee('Repair procedures')
            ->assertSee('Reusable SOP checklists scoped to models, types, or spares.');
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
        $response->assertSee(route('maintenance.index'), false);
        $response->assertSee(route('reports.index'), false);
        $response->assertSee(route('reports.checkouts'), false);
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
        $this->assertStringContainsString(route('maintenance.index'), $mobileNav);
        $this->assertStringContainsString(route('reports.index'), $mobileNav);
        $this->assertStringContainsString(route('admin.index'), $mobileNav);
        $this->assertStringNotContainsString(route('locations.index'), $mobileNav);
        $this->assertStringNotContainsString(route('repairs.index'), $mobileNav);
        $this->assertStringNotContainsString(route('spares.index'), $mobileNav);
        $this->assertStringNotContainsString(route('reports.compare'), $mobileNav);
        $this->assertStringNotContainsString(route('reports.checkouts'), $mobileNav);
        $this->assertStringNotContainsString(route('admin.users.index'), $mobileNav);
    }

    public function test_department_controls_live_on_profile_not_navigation(): void
    {
        $admin = $this->userWithRole('admin');

        $dashboard = $this->actingAs($admin)->get(route('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertDontSee('Active department', false);
        $dashboard->assertDontSee('Departments to show', false);
        $dashboard->assertDontSee(route('preferences.active-department'), false);
        $dashboard->assertDontSee(route('preferences.department-visibility'), false);

        $profile = $this->actingAs($admin)->get(route('profile.edit'));
        $profile->assertOk();
        $profile->assertSee('Active department', false);
        $profile->assertSee('Departments to show', false);
        $profile->assertSee('Clear all to show every department', false);
        $profile->assertSee(route('preferences.active-department'), false);
        $profile->assertSee(route('preferences.department-visibility'), false);
    }

    public function test_non_admin_sees_active_department_on_profile_without_visibility(): void
    {
        $user = $this->userWithRole('scanner');

        $profile = $this->actingAs($user)->get(route('profile.edit'));
        $profile->assertOk();
        $profile->assertSee('Active department', false);
        $profile->assertDontSee('Departments to show', false);
        $profile->assertDontSee(route('preferences.department-visibility'), false);
        $profile->assertDontSee(
            'No Department configured for your profile. Please request an assignment from your IT support.',
            false
        );
    }

    public function test_non_admin_without_departments_sees_assignment_notice(): void
    {
        $user = $this->userWithRole('scanner');
        $this->actingAs($user);
        $user->departments()->sync([]);

        $message = 'No Department configured for your profile. Please request an assignment from your IT support.';

        $dashboard = $this->get(route('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee($message, false);

        $profile = $this->get(route('profile.edit'));
        $profile->assertOk();
        $profile->assertSee($message, false);
        $profile->assertDontSee('Active department', false);
        $profile->assertDontSee(route('preferences.active-department'), false);
    }

    public function test_admin_never_sees_no_department_notice(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);
        $admin->departments()->sync([]);

        $message = 'No Department configured for your profile. Please request an assignment from your IT support.';

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($message, false);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee($message, false)
            ->assertSee('Departments to show', false);
    }
}

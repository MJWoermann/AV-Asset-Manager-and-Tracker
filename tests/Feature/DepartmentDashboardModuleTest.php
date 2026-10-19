<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\User;
use App\Support\DashboardModules;
use App\Support\DepartmentVisibility;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentDashboardModuleTest extends TestCase
{
    use RefreshDatabase;

    protected ItemType $itemType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
    }

    protected function makeDepartment(string $name, ?string $color = null, bool $active = true): Department
    {
        return Department::factory()->create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'color' => $color,
            'is_active' => $active,
        ]);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function inventoryManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    /**
     * @param  list<string>  $enabled
     */
    protected function withDashboardModules(User $user, array $enabled): User
    {
        $available = DashboardModules::availableFor($user);
        $user->preferences = array_merge($user->preferences ?? [], [
            DashboardModules::PREFERENCE_KEY => [
                'order' => $available,
                'enabled' => $enabled,
            ],
        ]);
        $user->save();

        return $user->fresh();
    }

    public function test_module_is_available_only_to_admins(): void
    {
        $admin = $this->admin();
        $manager = $this->inventoryManager();
        $scanner = $this->scanner();

        $this->assertContains(
            DashboardModules::ITEMS_USERS_BY_DEPARTMENT,
            DashboardModules::availableFor($admin)
        );
        $this->assertNotContains(
            DashboardModules::ITEMS_USERS_BY_DEPARTMENT,
            DashboardModules::availableFor($manager)
        );
        $this->assertNotContains(
            DashboardModules::ITEMS_USERS_BY_DEPARTMENT,
            DashboardModules::availableFor($scanner)
        );
    }

    public function test_admin_dashboard_shows_unscoped_department_counts_with_colours(): void
    {
        $deptA = $this->makeDepartment('Alpha', '#00adb7');
        $deptB = $this->makeDepartment('Bravo', '#2563eb');
        $inactive = $this->makeDepartment('Zulu Inactive', '#16a34a', false);

        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        $deptA->users()->sync([$memberA->id]);
        $deptB->users()->sync([$memberA->id, $memberB->id]);

        Asset::withoutDepartmentScope()->create([
            'department_id' => $deptA->id,
            'name' => 'A Parent',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
        ]);
        $parent = Asset::withoutDepartmentScope()->create([
            'department_id' => $deptB->id,
            'name' => 'B Parent',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
        ]);
        Asset::withoutDepartmentScope()->create([
            'department_id' => $deptB->id,
            'parent_id' => $parent->id,
            'name' => 'B Child',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
        ]);
        Asset::withoutDepartmentScope()->create([
            'department_id' => $inactive->id,
            'name' => 'Inactive Asset',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $admin = $this->withDashboardModules($this->admin(), [
            DashboardModules::ITEMS_USERS_BY_DEPARTMENT,
        ]);
        // Single-dept visibility must not hide other departments from this module.
        $admin->preferences = DepartmentVisibility::mergePreference($admin->preferences, [$deptA->id]);
        $admin->preferences = array_merge($admin->preferences, [
            DashboardModules::PREFERENCE_KEY => [
                'order' => DashboardModules::availableFor($admin),
                'enabled' => [DashboardModules::ITEMS_USERS_BY_DEPARTMENT],
            ],
        ]);
        $admin->save();

        $response = $this->actingAs($admin->fresh())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Items &amp; users by department', false);
        $response->assertSee('Alpha');
        $response->assertSee('Bravo');
        $response->assertSee('Zulu Inactive');

        $rows = collect($response->viewData('itemsUsersByDepartment'))->keyBy('id');
        $this->assertSame(1, $rows[$deptA->id]['item_count']);
        $this->assertSame(1, $rows[$deptA->id]['user_count']);
        $this->assertSame('#00adb7', $rows[$deptA->id]['color']);
        $this->assertSame(2, $rows[$deptB->id]['item_count']);
        $this->assertSame(2, $rows[$deptB->id]['user_count']);
        $this->assertSame('#2563eb', $rows[$deptB->id]['color']);
        $this->assertSame(1, $rows[$inactive->id]['item_count']);
        $this->assertFalse($rows[$inactive->id]['is_active']);
    }

    public function test_inventory_manager_cannot_see_department_module_even_if_forced_in_preferences(): void
    {
        $this->makeDepartment('Alpha', '#00adb7');
        $manager = $this->inventoryManager();
        $manager->preferences = [
            DashboardModules::PREFERENCE_KEY => [
                'order' => [DashboardModules::ITEMS_USERS_BY_DEPARTMENT, DashboardModules::QUICK_LINKS],
                'enabled' => [DashboardModules::ITEMS_USERS_BY_DEPARTMENT, DashboardModules::QUICK_LINKS],
            ],
        ];
        $manager->save();

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Items &amp; users by department', false);
        $this->assertSame([], $response->viewData('itemsUsersByDepartment'));
        $this->assertNotContains(
            DashboardModules::ITEMS_USERS_BY_DEPARTMENT,
            collect($response->viewData('modules'))->pluck('id')->all()
        );
    }

    public function test_scanner_cannot_enable_department_module_via_preferences(): void
    {
        $scanner = $this->scanner();
        $available = DashboardModules::availableFor($scanner);

        $this->assertNotContains(DashboardModules::ITEMS_USERS_BY_DEPARTMENT, $available);

        $this->actingAs($scanner)
            ->from(route('dashboard'))
            ->patch(route('preferences.dashboard-modules'), [
                'order' => array_values(array_unique([
                    DashboardModules::ITEMS_USERS_BY_DEPARTMENT,
                    ...$available,
                ])),
                'enabled' => [
                    DashboardModules::ITEMS_USERS_BY_DEPARTMENT,
                    DashboardModules::QUICK_LINKS,
                ],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['order.0', 'enabled.0']);

        $this->assertNull(
            ($scanner->fresh()->preferences ?? [])[DashboardModules::PREFERENCE_KEY] ?? null
        );

        $resolved = DashboardModules::resolve([
            'order' => [DashboardModules::ITEMS_USERS_BY_DEPARTMENT, DashboardModules::QUICK_LINKS],
            'enabled' => [DashboardModules::ITEMS_USERS_BY_DEPARTMENT, DashboardModules::QUICK_LINKS],
        ], $available);

        $this->assertNotContains(
            DashboardModules::ITEMS_USERS_BY_DEPARTMENT,
            array_column($resolved, 'id')
        );
    }
}

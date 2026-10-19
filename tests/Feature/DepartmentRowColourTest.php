<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\User;
use App\Support\DepartmentRowColourSettings;
use App\Support\DepartmentVisibility;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentRowColourTest extends TestCase
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

    protected function makeDepartment(string $name, ?string $color = null): Department
    {
        return Department::factory()->create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'color' => $color,
            'is_active' => true,
        ]);
    }

    protected function scannerIn(Department ...$departments): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');
        $user->departments()->sync(collect($departments)->pluck('id')->all());

        return $user;
    }

    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function enableRowColours(): void
    {
        DepartmentRowColourSettings::put(true);
    }

    public function test_admin_can_enable_row_colour_setting(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->patch(route('admin.departments.row-colours'), ['enabled' => 1])
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHas('status');

        $this->assertTrue(DepartmentRowColourSettings::enabled());

        $this->actingAs($admin)
            ->patch(route('admin.departments.row-colours'), ['enabled' => 0])
            ->assertRedirect(route('admin.departments.index'));

        $this->assertFalse(DepartmentRowColourSettings::enabled());
    }

    public function test_admin_can_save_department_colour_and_rejects_invalid_hex(): void
    {
        $admin = $this->adminUser();
        $department = $this->makeDepartment('Lighting');

        $this->actingAs($admin)
            ->put(route('admin.departments.update', $department), [
                'name' => 'Lighting',
                'is_active' => 1,
                'color' => '#00adb7',
            ])
            ->assertRedirect(route('admin.departments.index'));

        $this->assertSame('#00adb7', $department->fresh()->color);

        $this->actingAs($admin)
            ->from(route('admin.departments.edit', $department))
            ->put(route('admin.departments.update', $department), [
                'name' => 'Lighting',
                'is_active' => 1,
                'color' => '#ff00ff',
            ])
            ->assertRedirect(route('admin.departments.edit', $department))
            ->assertSessionHasErrors('color');

        $this->assertSame('#00adb7', $department->fresh()->color);
    }

    public function test_assets_index_omits_colours_when_setting_disabled(): void
    {
        $deptA = $this->makeDepartment('Alpha', '#00adb7');
        $deptB = $this->makeDepartment('Bravo', '#2563eb');
        $user = $this->scannerIn($deptA, $deptB);

        Asset::withoutDepartmentScope()->create([
            'department_id' => $deptA->id,
            'name' => 'Tint Candidate',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertViewHas('departmentColours', []);
        $this->assertFalse($user->shouldShowDepartmentRowColours());
    }

    public function test_assets_index_omits_colours_for_single_department_user(): void
    {
        $this->enableRowColours();
        $deptA = $this->makeDepartment('Alpha', '#00adb7');
        $this->makeDepartment('Bravo', '#2563eb');
        $user = $this->scannerIn($deptA);

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertViewHas('departmentColours', []);
        $this->assertSame(1, $user->visibleDepartmentCountForRowColours());
    }

    public function test_assets_index_passes_colour_map_for_multi_department_user(): void
    {
        $this->enableRowColours();
        $deptA = $this->makeDepartment('Alpha', '#00adb7');
        $deptB = $this->makeDepartment('Bravo', '#2563eb');
        $user = $this->scannerIn($deptA, $deptB);

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertViewHas('departmentColours', [
            $deptA->id => '#00adb7',
            $deptB->id => '#2563eb',
        ]);
        $this->assertTrue($user->shouldShowDepartmentRowColours());
    }

    public function test_unrestricted_admin_sees_colours_when_two_active_departments_exist(): void
    {
        $this->enableRowColours();
        $deptA = $this->makeDepartment('Alpha', '#00adb7');
        $deptB = $this->makeDepartment('Bravo', '#2563eb');
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('assets.index'));

        $response->assertOk();
        $colours = $response->viewData('departmentColours');
        $this->assertSame('#00adb7', $colours[$deptA->id] ?? null);
        $this->assertSame('#2563eb', $colours[$deptB->id] ?? null);
        $this->assertGreaterThanOrEqual(2, $admin->visibleDepartmentCountForRowColours());
    }

    public function test_admin_with_single_visibility_preference_does_not_see_row_colours(): void
    {
        $this->enableRowColours();
        $deptA = $this->makeDepartment('Alpha', '#00adb7');
        $this->makeDepartment('Bravo', '#2563eb');
        $admin = $this->adminUser();
        $admin->preferences = DepartmentVisibility::mergePreference($admin->preferences, [$deptA->id]);
        $admin->save();

        $response = $this->actingAs($admin->fresh())->get(route('assets.index'));

        $response->assertOk();
        $response->assertViewHas('departmentColours', []);
        $this->assertSame(1, $admin->fresh()->visibleDepartmentCountForRowColours());
    }

    public function test_external_repairer_never_gets_row_colours(): void
    {
        $this->enableRowColours();
        $deptA = $this->makeDepartment('Alpha', '#00adb7');
        $deptB = $this->makeDepartment('Bravo', '#2563eb');
        $user = User::factory()->create();
        $user->assignRole('external_repairer');
        $user->departments()->sync([$deptA->id, $deptB->id]);

        $this->assertSame(0, $user->visibleDepartmentCountForRowColours());
        $this->assertFalse($user->shouldShowDepartmentRowColours());
    }

    public function test_department_without_colour_is_omitted_from_browse_map(): void
    {
        $this->enableRowColours();
        $deptA = $this->makeDepartment('Alpha', '#00adb7');
        $deptB = $this->makeDepartment('Bravo', null);
        $user = $this->scannerIn($deptA, $deptB);

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $this->assertSame(
            [$deptA->id => '#00adb7'],
            $response->viewData('departmentColours')
        );
    }
}

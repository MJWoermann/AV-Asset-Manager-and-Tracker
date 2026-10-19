<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\ScanSession;
use App\Models\User;
use App\Support\AssetTableColumns;
use App\Support\DepartmentVisibility;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentIsolationTest extends TestCase
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

    protected function makeDepartment(string $name): Department
    {
        return Department::factory()->create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
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

    protected function makeAsset(Department $department, array $attributes = []): Asset
    {
        return Asset::withoutDepartmentScope()->create(array_merge([
            'department_id' => $department->id,
            'name' => 'Dept Asset',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-'.uniqid(),
        ], $attributes));
    }

    public function test_non_admin_cannot_show_or_update_other_department_asset(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $user = $this->scannerIn($deptA);
        $assetB = $this->makeAsset($deptB, ['name' => 'Hidden Asset']);

        $this->actingAs($user)
            ->get(route('assets.show', $assetB))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('assets.update', $assetB), [
                'name' => 'Hacked',
                'item_type_id' => $this->itemType->id,
                'status' => AssetStatus::Available->value,
                'quantity' => 1,
            ])
            ->assertNotFound();
    }

    public function test_non_admin_cannot_show_other_department_list(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $user = $this->scannerIn($deptA);
        $listB = AssetList::withoutDepartmentScope()->create([
            'department_id' => $deptB->id,
            'name' => 'Secret List',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('lists.show', $listB))
            ->assertNotFound();
    }

    public function test_non_admin_in_multiple_departments_sees_union_on_asset_index(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $deptC = $this->makeDepartment('Charlie');
        $user = $this->scannerIn($deptA, $deptB);

        $this->makeAsset($deptA, ['name' => 'Asset Alpha']);
        $this->makeAsset($deptB, ['name' => 'Asset Bravo']);
        $this->makeAsset($deptC, ['name' => 'Asset Charlie']);

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('Asset Alpha');
        $response->assertSee('Asset Bravo');
        $response->assertDontSee('Asset Charlie');
    }

    public function test_create_stamps_active_department_id(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $user = $this->scannerIn($deptA, $deptB);

        $this->actingAs($user);
        session([DepartmentVisibility::SESSION_KEY => $deptB->id]);

        $response = $this->post(route('assets.store'), [
            'name' => 'Stamped Asset',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available->value,
            'quantity' => 1,
        ]);

        $response->assertRedirect();
        $asset = Asset::withoutDepartmentScope()->where('name', 'Stamped Asset')->first();
        $this->assertNotNull($asset);
        $this->assertSame($deptB->id, (int) $asset->department_id);
    }

    public function test_admin_with_no_visibility_preference_sees_all_departments(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $admin = $this->adminUser();

        $this->makeAsset($deptA, ['name' => 'Asset Alpha']);
        $this->makeAsset($deptB, ['name' => 'Asset Bravo']);

        $response = $this->actingAs($admin)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('Asset Alpha');
        $response->assertSee('Asset Bravo');
    }

    public function test_admin_visibility_preference_filters_and_404s_hidden_department(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $admin = $this->adminUser();
        $assetA = $this->makeAsset($deptA, ['name' => 'Asset Alpha']);
        $assetB = $this->makeAsset($deptB, ['name' => 'Asset Bravo']);

        $admin->preferences = DepartmentVisibility::mergePreference($admin->preferences, [$deptA->id]);
        $admin->save();

        $this->actingAs($admin)->get(route('assets.index'))
            ->assertOk()
            ->assertSee('Asset Alpha')
            ->assertDontSee('Asset Bravo');

        $this->actingAs($admin)->get(route('assets.show', $assetB))->assertNotFound();
        $this->actingAs($admin)->get(route('assets.show', $assetA))->assertOk();

        $admin->preferences = DepartmentVisibility::mergePreference($admin->preferences, []);
        $admin->save();

        $this->actingAs($admin)->get(route('assets.show', $assetB))->assertOk();
    }

    public function test_admin_visibility_preference_merge_preserves_other_keys(): void
    {
        $admin = $this->adminUser();
        $dept = $this->makeDepartment('Alpha');

        $admin->preferences = [
            AssetTableColumns::PREFERENCE_KEY => ['name', 'status'],
            'features' => ['email_integration' => true],
        ];
        $admin->save();

        $this->actingAs($admin)
            ->patch(route('preferences.department-visibility'), [
                'visible_department_ids' => [$dept->id],
            ])
            ->assertRedirect();

        $admin->refresh();
        $this->assertSame(['name', 'status'], $admin->preferences[AssetTableColumns::PREFERENCE_KEY]);
        $this->assertTrue($admin->preferences['features']['email_integration']);
        $this->assertSame([$dept->id], $admin->preferences[DepartmentVisibility::PREFERENCE_KEY]);
        $this->assertTrue($admin->departments->contains('id', $dept->id));
    }

    public function test_admin_department_visibility_stays_in_sync_with_manage_users(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $admin = $this->adminUser();
        $actor = $this->adminUser();

        $this->actingAs($admin)
            ->patch(route('preferences.department-visibility'), [
                'visible_department_ids' => [$deptA->id],
            ])
            ->assertRedirect();

        $admin->refresh();
        $this->assertSame([$deptA->id], $admin->preferences[DepartmentVisibility::PREFERENCE_KEY]);
        $this->assertEqualsCanonicalizing([$deptA->id], $admin->departments->pluck('id')->map(fn ($id) => (int) $id)->all());

        $this->actingAs($actor)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
                'theme' => 'light',
                'department_ids' => [$deptB->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $admin->refresh();
        $this->assertSame([$deptB->id], $admin->preferences[DepartmentVisibility::PREFERENCE_KEY]);
        $this->assertEqualsCanonicalizing([$deptB->id], $admin->departments->pluck('id')->map(fn ($id) => (int) $id)->all());

        $this->actingAs($actor)
            ->get(route('admin.users.edit', $admin))
            ->assertOk()
            ->assertSee('value="'.$deptB->id.'"', false);
    }

    public function test_scan_does_not_match_barcode_from_other_department(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $user = $this->scannerIn($deptA);

        $this->makeAsset($deptB, [
            'name' => 'Other Dept Asset',
            'tp_barcode' => 'TP-CROSS-DEPT',
        ]);

        $event = AssetList::withoutDepartmentScope()->create([
            'department_id' => $deptA->id,
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        session([DepartmentVisibility::SESSION_KEY => $deptA->id]);

        $session = ScanSession::create([
            'department_id' => $deptA->id,
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        $response = $this->postJson(route('scan.scan', $session), [
            'code' => 'TP-CROSS-DEPT',
        ]);

        $response->assertOk();
        $response->assertJsonPath('unknown', true);
        $response->assertJsonPath('inventory_status', 'unknown_asset');
    }

    public function test_admin_hidden_department_scan_does_not_match(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $admin = $this->adminUser();

        $admin->preferences = DepartmentVisibility::mergePreference($admin->preferences, [$deptA->id]);
        $admin->save();

        $this->makeAsset($deptB, [
            'name' => 'Hidden Asset',
            'tp_barcode' => 'TP-HIDDEN-DEPT',
        ]);

        $event = AssetList::withoutDepartmentScope()->create([
            'department_id' => $deptA->id,
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);
        session([DepartmentVisibility::SESSION_KEY => $deptA->id]);

        $session = ScanSession::create([
            'department_id' => $deptA->id,
            'user_id' => $admin->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        $response = $this->postJson(route('scan.scan', $session), [
            'code' => 'TP-HIDDEN-DEPT',
        ]);

        $response->assertOk();
        $response->assertJsonPath('unknown', true);
    }

    public function test_shared_location_usable_across_departments(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $userA = $this->scannerIn($deptA);
        $userB = $this->scannerIn($deptB);
        $location = Location::create([
            'name' => 'Shared Room',
            'type' => LocationType::Room,
            'is_portable' => false,
        ]);
        $typeA = ItemType::withoutDepartmentScope()->create([
            'department_id' => $deptA->id,
            'name' => 'Alpha Fixture',
            'slug' => 'alpha-fixture',
        ]);
        $typeB = ItemType::withoutDepartmentScope()->create([
            'department_id' => $deptB->id,
            'name' => 'Bravo Fixture',
            'slug' => 'bravo-fixture',
        ]);

        $this->actingAs($userA);
        session([DepartmentVisibility::SESSION_KEY => $deptA->id]);
        $this->post(route('assets.store'), [
            'name' => 'Asset A',
            'item_type_id' => $typeA->id,
            'location_id' => $location->id,
            'status' => AssetStatus::Available->value,
            'quantity' => 1,
        ])->assertRedirect();

        $this->actingAs($userB);
        session([DepartmentVisibility::SESSION_KEY => $deptB->id]);
        $this->post(route('assets.store'), [
            'name' => 'Asset B',
            'item_type_id' => $typeB->id,
            'location_id' => $location->id,
            'status' => AssetStatus::Available->value,
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertSame(2, Asset::withoutDepartmentScope()->where('location_id', $location->id)->count());
        $this->assertDatabaseHas('assets', ['name' => 'Asset A', 'department_id' => $deptA->id]);
        $this->assertDatabaseHas('assets', ['name' => 'Asset B', 'department_id' => $deptB->id]);
    }

    public function test_non_admin_cannot_see_or_edit_other_department_item_type(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        $user->departments()->sync([$deptA->id]);

        ItemType::withoutDepartmentScope()->create([
            'department_id' => $deptA->id,
            'name' => 'Visible Type',
            'slug' => 'visible-type',
        ]);
        $typeB = ItemType::withoutDepartmentScope()->create([
            'department_id' => $deptB->id,
            'name' => 'Hidden Type',
            'slug' => 'hidden-type',
        ]);

        $this->actingAs($user)
            ->get(route('item-types.index'))
            ->assertOk()
            ->assertSee('Visible Type')
            ->assertDontSee('Hidden Type');

        $this->actingAs($user)
            ->get(route('item-types.edit', $typeB))
            ->assertNotFound();
    }

    public function test_create_item_type_stamps_active_department_id(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        $user->departments()->sync([$deptA->id, $deptB->id]);

        $this->actingAs($user);
        session([DepartmentVisibility::SESSION_KEY => $deptB->id]);

        $response = $this->post(route('item-types.store'), [
            'name' => 'Stamped Type',
            'description' => null,
        ]);

        $response->assertRedirect(route('item-types.index'));
        $type = ItemType::withoutDepartmentScope()->where('name', 'Stamped Type')->first();
        $this->assertNotNull($type);
        $this->assertSame($deptB->id, (int) $type->department_id);
    }

    public function test_member_can_switch_active_department(): void
    {
        $deptA = $this->makeDepartment('Alpha');
        $deptB = $this->makeDepartment('Bravo');
        $user = $this->scannerIn($deptA, $deptB);

        $this->actingAs($user)
            ->patch(route('preferences.active-department'), [
                'department_id' => $deptB->id,
            ])
            ->assertRedirect();

        $this->assertSame($deptB->id, (int) session(DepartmentVisibility::SESSION_KEY));
    }

    public function test_admin_can_manage_departments_and_user_membership(): void
    {
        $admin = $this->adminUser();
        $dept = $this->makeDepartment('Lighting');

        $this->actingAs($admin)
            ->get(route('admin.departments.index'))
            ->assertOk()
            ->assertSee('Lighting');

        $target = User::factory()->create();
        $target->assignRole('scanner');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'scanner',
                'theme' => 'light',
                'department_ids' => [$dept->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($target->fresh()->departments->contains('id', $dept->id));
    }
}

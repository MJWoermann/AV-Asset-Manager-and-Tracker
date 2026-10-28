<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\StockMovementType;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\UnknownScan;
use App\Models\User;
use App\Services\StockMovementService;
use App\Support\AccessPermissions;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
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

    public function test_admin_can_create_a_custom_role_and_assign_it(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Hire desk',
                'permissions' => ['assets:read', 'assets:write'],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('status', 'Role created.');

        $role = Role::findByName('Hire desk');
        $this->assertTrue($role->hasPermissionTo('assets:read'));
        $this->assertFalse($role->hasPermissionTo('assets:delete'));

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Hire User',
                'email' => 'hire@example.com',
                'password' => 'Secret123!',
                'password_confirmation' => 'Secret123!',
                'role' => 'Hire desk',
                'theme' => 'light',
                'department_ids' => [$this->generalDepartment()->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'hire@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('Hire desk'));

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Hire desk', false);
    }

    public function test_scanner_can_edit_an_asset_and_cannot_delete_it(): void
    {
        $scanner = $this->userWithRole('scanner');
        $itemType = ItemType::factory()->create();
        $asset = Asset::factory()->create([
            'name' => 'Scanner Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $this->actingAs($scanner)
            ->put(route('assets.update', $asset), [
                'name' => 'Scanner Fixture',
                'description' => 'Edited by scanner',
                'item_type_id' => $itemType->id,
                'status' => AssetStatus::Available->value,
            ])
            ->assertRedirect(route('assets.show', $asset));

        $this->assertSame('Edited by scanner', $asset->fresh()->description);

        $this->actingAs($scanner)
            ->delete(route('assets.destroy', $asset))
            ->assertForbidden();

        $this->assertModelExists($asset);
    }

    public function test_custom_role_with_asset_delete_can_delete_an_asset(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Asset removers',
            'permissions' => ['assets:read', 'assets:write', 'assets:delete'],
        ])->assertRedirect(route('admin.roles.index'));

        $user = $this->userWithRole('Asset removers');
        $asset = Asset::factory()->create();

        $this->actingAs($user)
            ->delete(route('assets.destroy', $asset))
            ->assertRedirect(route('assets.index'));

        $this->assertModelMissing($asset);
    }

    public function test_api_delete_requires_role_permission_even_when_token_is_unrestricted(): void
    {
        $scanner = $this->userWithRole('scanner');
        $scanner->departments()->sync([$this->generalDepartment()->id]);
        $asset = Asset::factory()->create([
            'department_id' => $this->generalDepartment()->id,
        ]);

        Sanctum::actingAs($scanner, ['*']);

        $this->deleteJson('/api/assets/'.$asset->id)->assertForbidden();
        $this->assertModelExists($asset);
    }

    public function test_developer_feature_comes_from_the_role_for_non_admins(): void
    {
        $scanner = $this->userWithRole('scanner');

        $this->actingAs($scanner)
            ->get(route('rigging.events.index'))
            ->assertNotFound();

        $scanner = $this->enableFeature($scanner, 'rigging');

        $this->actingAs($scanner)
            ->get(route('rigging.events.index'))
            ->assertOk();
    }

    public function test_admin_profile_toggle_still_enables_a_developer_feature(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->patch(route('profile.update'), [
                'name' => $admin->name,
                'email' => $admin->email,
                'theme' => 'light',
                'features' => [
                    'rigging' => '1',
                ],
            ])
            ->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $this->assertTrue($admin->hasFeature('rigging'));
    }

    public function test_non_admin_profile_post_does_not_enable_developer_features(): void
    {
        $scanner = $this->userWithRole('scanner');

        $this->actingAs($scanner)
            ->patch(route('profile.update'), [
                'name' => $scanner->name,
                'email' => $scanner->email,
                'theme' => 'light',
                'features' => [
                    'rigging' => '1',
                ],
            ])
            ->assertRedirect(route('profile.edit'));

        $scanner->refresh();
        $this->assertFalse($scanner->hasFeature('rigging'));
        $this->assertNull(data_get($scanner->preferences, 'features.rigging'));
    }

    public function test_external_repairer_cannot_open_the_asset_index(): void
    {
        $repairer = $this->userWithRole('external_repairer');

        $this->actingAs($repairer)
            ->get(route('assets.index'))
            ->assertForbidden();
    }

    public function test_system_role_cannot_be_deleted_or_renamed(): void
    {
        $admin = $this->userWithRole('admin');
        $scanner = Role::findByName('scanner');

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $scanner))
            ->assertRedirect()
            ->assertSessionHasErrors('role');

        $this->assertModelExists($scanner);

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $scanner), [
                'name' => 'Floor scanner',
                'permissions' => AccessPermissions::defaultsFor('scanner'),
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame('scanner', $scanner->fresh()->name);
    }

    public function test_role_assigned_to_a_user_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Busy role',
            'permissions' => ['assets:read'],
        ]);

        $role = Role::findByName('Busy role');
        $this->userWithRole('Busy role');

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHasErrors('role');

        $this->assertModelExists($role);
    }

    public function test_unused_custom_role_can_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Empty role',
            'permissions' => ['assets:read'],
        ]);

        $role = Role::findByName('Empty role');

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertModelMissing($role);
    }

    public function test_inventory_manager_purge_and_stock_adjust_follow_role_permissions(): void
    {
        $manager = $this->enableFeature($this->userWithRole('inventory_manager'), 'stock_consumables');
        $asset = Asset::factory()->create([
            'item_type_id' => ItemType::factory()->stockTracked()->create()->id,
            'quantity' => 10,
        ]);
        $movement = app(StockMovementService::class)->record($asset, [
            'type' => StockMovementType::Count,
            'quantity' => 9,
        ], $manager);

        UnknownScan::create([
            'scanned_code' => 'KEEP-ME',
            'user_id' => $manager->id,
            'quantity' => 1,
        ]);

        $this->actingAs($manager)
            ->delete(route('reports.unknown-scans.destroy-all'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->patch(route('assets.stock-movements.update', [$asset, $movement]), [
                'type' => 'count',
                'quantity' => 4,
                'recorded_at' => now()->toDateString(),
            ])
            ->assertForbidden();

        $role = Role::findByName('inventory_manager');
        $role->givePermissionTo(['reports:purge', 'stock:adjust']);
        $manager = $manager->fresh();

        $this->actingAs($manager)
            ->delete(route('reports.unknown-scans.destroy-all'))
            ->assertRedirect(route('reports.unknown-scans'));

        $this->assertDatabaseCount('unknown_scans', 0);

        $this->actingAs($manager)
            ->patch(route('assets.stock-movements.update', [$asset, $movement]), [
                'type' => 'count',
                'quantity' => 4,
                'recorded_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock']));

        $this->actingAs($manager)
            ->delete(route('assets.stock-movements.destroy', [$asset, $movement]))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock']));

        $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);
    }

    public function test_custom_role_can_purge_unknown_scans_and_adjust_stock(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Stock editors',
            'permissions' => [
                'assets:read',
                'reports:read',
                'reports:purge',
                'stock:adjust',
                'features:stock_consumables',
            ],
        ])->assertRedirect(route('admin.roles.index'));

        $user = $this->userWithRole('Stock editors');
        $asset = Asset::factory()->create([
            'item_type_id' => ItemType::factory()->stockTracked()->create()->id,
            'quantity' => 6,
        ]);
        $movement = app(StockMovementService::class)->record($asset, [
            'type' => StockMovementType::Count,
            'quantity' => 6,
        ], $user);

        UnknownScan::create([
            'scanned_code' => 'CUSTOM-PURGE',
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.unknown-scans.destroy-all'))
            ->assertRedirect(route('reports.unknown-scans'));

        $this->actingAs($user)
            ->delete(route('assets.stock-movements.destroy', [$asset, $movement]))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock']));
    }

    public function test_admin_role_form_locks_operational_access_and_hides_developer_features(): void
    {
        $admin = $this->userWithRole('admin');
        $role = Role::findByName('admin');

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertDontSee('Developer features')
            ->assertSee('Administrators have every operational permission', false);

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', Role::findByName('inventory_manager')))
            ->assertOk()
            ->assertSee('Developer features')
            ->assertSee('reports:purge', false)
            ->assertSee('stock:adjust', false);
    }
}

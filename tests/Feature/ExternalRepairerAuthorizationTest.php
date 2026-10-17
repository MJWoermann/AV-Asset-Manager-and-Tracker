<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceLogType;
use App\Enums\RepairStatus;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\ItemType;
use App\Models\Repair;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalRepairerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function externalRepairer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('external_repairer');

        return $user;
    }

    protected function makeAsset(): Asset
    {
        $itemType = ItemType::query()->create([
            'name' => 'Type '.fake()->unique()->numerify('###'),
            'slug' => 'type-'.fake()->unique()->numerify('###'),
        ]);

        return Asset::factory()->create([
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Maintenance,
        ]);
    }

    public function test_external_repairer_forbidden_on_assets_index(): void
    {
        $user = $this->externalRepairer();

        $this->actingAs($user)->get(route('assets.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('scan.index'))->assertForbidden();
        $this->actingAs($user)->get(route('lists.index'))->assertForbidden();
    }

    public function test_external_repairer_can_view_own_assigned_repair_and_asset(): void
    {
        $user = $this->externalRepairer();
        $asset = $this->makeAsset();
        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);
        $repair = Repair::factory()->create([
            'asset_id' => $asset->id,
            'maintenance_log_id' => $fault->id,
            'status' => RepairStatus::Open,
            'assignee_user_id' => $user->id,
            'summary' => 'Assigned job',
        ]);

        $this->actingAs($user)->get(route('repairs.index'))
            ->assertOk()
            ->assertSee('Assigned job');

        $this->actingAs($user)->get(route('repairs.show', $repair))->assertOk();
        $this->actingAs($user)->get(route('assets.show', $asset))->assertOk();
        $this->actingAs($user)->get(route('maintenance.index'))->assertOk();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_external_repairer_forbidden_on_unassigned_repair_and_asset(): void
    {
        $user = $this->externalRepairer();
        $other = $this->externalRepairer();
        $asset = $this->makeAsset();
        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);
        $repair = Repair::factory()->create([
            'asset_id' => $asset->id,
            'maintenance_log_id' => $fault->id,
            'status' => RepairStatus::Open,
            'assignee_user_id' => $other->id,
            'summary' => 'Someone else',
        ]);

        $this->actingAs($user)->get(route('repairs.show', $repair))->assertForbidden();
        $this->actingAs($user)->get(route('assets.show', $asset))->assertForbidden();
        $this->actingAs($user)->get(route('repairs.index'))
            ->assertOk()
            ->assertDontSee('Someone else');
    }

    public function test_external_repairer_cannot_create_hire_checkout(): void
    {
        $user = $this->externalRepairer();
        $asset = $this->makeAsset();
        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);
        Repair::factory()->create([
            'asset_id' => $asset->id,
            'maintenance_log_id' => $fault->id,
            'status' => RepairStatus::Open,
            'assignee_user_id' => $user->id,
        ]);

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => 'hire',
            'checked_out_to_name' => 'Nope',
        ])->assertForbidden();
    }

    public function test_admin_can_create_external_repairer_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'External Tech',
            'email' => 'external@example.com',
            'password' => 'Secret123!',
            'role' => 'external_repairer',
            'theme' => 'light',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $created = User::query()->where('email', 'external@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('external_repairer'));
    }
}

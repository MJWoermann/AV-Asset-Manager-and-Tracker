<?php

namespace Tests\Feature\Api;

use App\Enums\AssetStatus;
use App\Enums\CheckoutPurpose;
use App\Enums\MaintenanceLogType;
use App\Enums\RepairStatus;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\AssetMaintenanceLog;
use App\Models\ItemType;
use App\Models\Repair;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function userWithRoleAndAbilities(string $role, array $abilities): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->departments()->sync([$this->generalDepartment()->id]);

        Sanctum::actingAs($user, $abilities);

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
            'department_id' => $this->generalDepartment()->id,
            'status' => AssetStatus::Available,
        ]);
    }

    public function test_hire_checkout_and_return_via_api(): void
    {
        $user = $this->userWithRoleAndAbilities('inventory_manager', ['checkouts:write']);
        $asset = $this->makeAsset();
        $assignee = User::factory()->create();
        $assignee->assignRole('scanner');

        $checkoutResponse = $this->postJson("/api/assets/{$asset->id}/checkouts", [
            'purpose' => CheckoutPurpose::Hire->value,
            'checked_out_to_user_id' => $assignee->id,
            'notes' => 'Weekend hire',
        ]);

        $checkoutResponse
            ->assertCreated()
            ->assertJsonPath('purpose', CheckoutPurpose::Hire->value)
            ->assertJsonPath('checked_out_to_user_id', $assignee->id)
            ->assertJsonPath('returned_at', null);

        $checkoutId = $checkoutResponse->json('id');
        $this->assertNotNull($checkoutId);

        $asset->refresh();
        $this->assertSame(AssetStatus::OnEvent, $asset->status);

        $returnResponse = $this->postJson("/api/assets/{$asset->id}/checkouts/{$checkoutId}/return", [
            'notes' => 'Back in rack',
        ]);

        $returnResponse
            ->assertOk()
            ->assertJsonPath('id', $checkoutId);

        $this->assertNotNull($returnResponse->json('returned_at'));

        $checkout = AssetCheckout::query()->findOrFail($checkoutId);
        $this->assertNotNull($checkout->returned_at);
        $this->assertSame($user->id, $checkout->returned_by_user_id);

        $asset->refresh();
        $this->assertSame(AssetStatus::Available, $asset->status);
    }

    public function test_return_is_blocked_while_linked_repair_is_open(): void
    {
        $user = $this->userWithRoleAndAbilities('inventory_manager', ['checkouts:write']);
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
        ]);

        $checkoutResponse = $this->postJson("/api/assets/{$asset->id}/checkouts", [
            'purpose' => CheckoutPurpose::Repair->value,
            'checked_out_to_name' => 'Guest Repairer',
            'repair_id' => $repair->id,
        ]);

        $checkoutResponse->assertCreated();
        $checkoutId = $checkoutResponse->json('id');

        $this->postJson("/api/assets/{$asset->id}/checkouts/{$checkoutId}/return")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['checkout']);

        $this->assertNull(AssetCheckout::query()->findOrFail($checkoutId)->returned_at);
        $this->assertSame(AssetStatus::Maintenance, $asset->fresh()->status);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $asset = $this->makeAsset();

        $this->postJson("/api/assets/{$asset->id}/checkouts", [
            'purpose' => CheckoutPurpose::Hire->value,
            'checked_out_to_name' => 'Guest',
        ])
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer');
    }

    public function test_returns_403_when_token_lacks_checkouts_write_ability(): void
    {
        $this->userWithRoleAndAbilities('scanner', ['checkouts:read', 'assets:read']);
        $asset = $this->makeAsset();

        $this->postJson("/api/assets/{$asset->id}/checkouts", [
            'purpose' => CheckoutPurpose::Hire->value,
            'checked_out_to_name' => 'Guest',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Authenticated but token lacks required ability.');
    }

    public function test_returns_403_when_user_lacks_staff_role(): void
    {
        $user = User::factory()->create();
        $user->departments()->sync([$this->generalDepartment()->id]);
        Sanctum::actingAs($user, ['checkouts:write']);

        $asset = $this->makeAsset();

        $this->postJson("/api/assets/{$asset->id}/checkouts", [
            'purpose' => CheckoutPurpose::Hire->value,
            'checked_out_to_name' => 'Guest',
        ])->assertForbidden();
    }

    public function test_return_requires_checkouts_write_ability(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('inventory_manager');
        $manager->departments()->sync([$this->generalDepartment()->id]);

        $asset = $this->makeAsset();
        $checkout = AssetCheckout::factory()->create([
            'asset_id' => $asset->id,
            'department_id' => $asset->department_id,
            'checked_out_by_user_id' => $manager->id,
            'purpose' => CheckoutPurpose::Hire,
            'returned_at' => null,
        ]);

        Sanctum::actingAs($manager, ['checkouts:read']);

        $this->postJson("/api/assets/{$asset->id}/checkouts/{$checkout->id}/return")
            ->assertForbidden();
    }
}

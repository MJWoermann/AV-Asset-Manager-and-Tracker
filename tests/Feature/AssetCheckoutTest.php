<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\CheckoutPurpose;
use App\Enums\MaintenanceLogType;
use App\Enums\RepairStatus;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\AssetMaintenanceLog;
use App\Models\AuditLog;
use App\Models\ItemType;
use App\Models\Repair;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

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
            'status' => AssetStatus::Available,
        ]);
    }

    public function test_hire_checkout_and_return_updates_status_and_audit(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();
        $assignee = User::factory()->create();
        $assignee->assignRole('scanner');

        $checkoutResponse = $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => CheckoutPurpose::Hire->value,
            'checked_out_to_user_id' => $assignee->id,
            'notes' => 'Weekend hire',
        ]);

        $checkoutResponse->assertRedirect(route('assets.show', $asset));

        $checkout = AssetCheckout::query()->first();
        $this->assertNotNull($checkout);
        $this->assertNull($checkout->returned_at);
        $this->assertSame(CheckoutPurpose::Hire, $checkout->purpose);
        $this->assertSame(AssetStatus::Available->value, $checkout->asset_status_before);

        $asset->refresh();
        $this->assertSame(AssetStatus::OnEvent, $asset->status);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $asset->id,
            'action' => 'asset_checked_out',
        ]);

        $returnResponse = $this->actingAs($user)->post(route('assets.checkouts.return', [$asset, $checkout]), [
            'notes' => 'Back in rack',
        ]);

        $returnResponse->assertRedirect(route('assets.show', $asset));
        $checkout->refresh();
        $this->assertNotNull($checkout->returned_at);
        $asset->refresh();
        $this->assertSame(AssetStatus::Available, $asset->status);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $asset->id,
            'action' => 'asset_returned',
        ]);
    }

    public function test_guest_assignee_checkout_requires_name_and_validates_email(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => CheckoutPurpose::Other->value,
            'checked_out_to_email' => 'not-an-email',
        ])->assertSessionHasErrors(['checked_out_to_name', 'checked_out_to_email']);

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => CheckoutPurpose::Other->value,
            'checked_out_to_name' => 'Acme Tech',
            'checked_out_to_email' => 'tech@example.com',
            'checked_out_to_company' => 'Acme',
        ])->assertRedirect(route('assets.show', $asset));

        $this->assertDatabaseHas('asset_checkouts', [
            'asset_id' => $asset->id,
            'checked_out_to_name' => 'Acme Tech',
            'checked_out_to_email' => 'tech@example.com',
            'checked_out_to_company' => 'Acme',
            'checked_out_to_user_id' => null,
            'purpose' => CheckoutPurpose::Other->value,
        ]);

        $asset->refresh();
        $this->assertSame(AssetStatus::OnEvent, $asset->status);
    }

    public function test_repair_checkout_blocks_return_while_repair_open(): void
    {
        $user = $this->manager();
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

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => CheckoutPurpose::Repair->value,
            'checked_out_to_name' => 'Guest Repairer',
            'repair_id' => $repair->id,
        ])->assertRedirect(route('assets.show', $asset));

        $checkout = AssetCheckout::query()->first();
        $this->assertNotNull($checkout);
        $asset->refresh();
        $this->assertSame(AssetStatus::Maintenance, $asset->status);

        $this->actingAs($user)->post(route('assets.checkouts.return', [$asset, $checkout]))
            ->assertSessionHasErrors('checkout');

        $checkout->refresh();
        $this->assertNull($checkout->returned_at);
    }

    public function test_completing_repair_auto_returns_linked_checkout(): void
    {
        $user = $this->manager();
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

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => CheckoutPurpose::Repair->value,
            'checked_out_to_user_id' => $user->id,
            'repair_id' => $repair->id,
        ])->assertRedirect();

        $checkout = AssetCheckout::query()->first();
        $this->assertNotNull($checkout);

        $this->actingAs($user)->post(route('repairs.complete', $repair), [
            'resolution_summary' => 'Fixed and returned',
            'asset_status' => AssetStatus::Available->value,
        ])->assertRedirect(route('repairs.show', $repair));

        $checkout->refresh();
        $this->assertNotNull($checkout->returned_at);
        $repair->refresh();
        $this->assertSame(RepairStatus::Completed, $repair->status);
        $asset->refresh();
        $this->assertSame(AssetStatus::Available, $asset->status);
    }

    public function test_create_repair_with_checkout(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => CheckoutPurpose::Repair->value,
            'checked_out_to_name' => 'Bench Tech',
            'create_repair' => '1',
            'repair_summary' => 'Power supply RMA',
        ])->assertRedirect(route('assets.show', $asset));

        $checkout = AssetCheckout::query()->first();
        $this->assertNotNull($checkout?->repair_id);
        $this->assertDatabaseHas('repairs', [
            'id' => $checkout->repair_id,
            'summary' => 'Power supply RMA',
            'status' => RepairStatus::Open->value,
        ]);
        $this->assertSame(AssetStatus::Maintenance, $asset->fresh()->status);
    }

    public function test_audit_logs_are_append_only_for_checkout(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => CheckoutPurpose::Hire->value,
            'checked_out_to_name' => 'Guest',
        ]);

        $log = AuditLog::query()->where('action', 'asset_checked_out')->first();
        $this->assertNotNull($log);

        $this->expectException(\RuntimeException::class);
        $log->update(['action' => 'tampered']);
    }
}

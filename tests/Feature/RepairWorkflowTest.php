<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Enums\MaintenanceLogType;
use App\Enums\RepairStatus;
use App\Enums\SparePartMovementType;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\File;
use App\Models\ItemType;
use App\Models\Repair;
use App\Models\RepairProcedure;
use App\Models\RepairProcedureStep;
use App\Models\SparePart;
use App\Models\User;
use App\Services\FileAttachmentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairWorkflowTest extends TestCase
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

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    protected function makeAsset(?ItemType $itemType = null): Asset
    {
        $itemType ??= ItemType::query()->create([
            'name' => 'Type '.fake()->unique()->numerify('###'),
            'slug' => 'type-'.fake()->unique()->numerify('###'),
        ]);

        return Asset::factory()->create([
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);
    }

    public function test_start_repair_from_existing_fault(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
            'summary' => 'Broken latch',
        ]);

        $response = $this->actingAs($user)->post(route('assets.repairs.store', $asset), [
            'maintenance_log_id' => $fault->id,
            'summary' => 'Fix latch',
        ]);

        $repair = Repair::query()->first();
        $this->assertNotNull($repair);
        $response->assertRedirect(route('repairs.show', $repair));
        $this->assertSame(RepairStatus::Open, $repair->status);
        $this->assertSame($fault->id, $repair->maintenance_log_id);
    }

    public function test_start_repair_from_spare_creates_fault(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $spare = SparePart::factory()->create(['quantity_on_hand' => 4]);

        $response = $this->actingAs($user)->post(route('spares.repairs.store', $spare), [
            'asset_id' => $asset->id,
            'summary' => 'Replace latch kit',
        ]);

        $repair = Repair::query()->first();
        $this->assertNotNull($repair);
        $response->assertRedirect(route('repairs.show', $repair));

        $this->assertDatabaseHas('asset_maintenance_logs', [
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault->value,
            'summary' => 'Replace latch kit',
        ]);
        $this->assertSame($repair->maintenance_log_id, AssetMaintenanceLog::query()->first()->id);
    }

    public function test_fault_create_with_start_repair_now_redirects_to_repair(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        $response = $this->actingAs($user)->post(route('assets.maintenance-logs.store', $asset), [
            'type' => MaintenanceLogType::Fault->value,
            'summary' => 'PSU failure',
            'start_repair_now' => '1',
        ]);

        $repair = Repair::query()->first();
        $this->assertNotNull($repair);
        $response->assertRedirect(route('repairs.show', $repair));
        $this->assertSame('PSU failure', $repair->summary);
    }

    public function test_matching_procedure_clones_steps_hybrid_without_leaves_blank(): void
    {
        $user = $this->manager();
        $itemType = ItemType::query()->create([
            'name' => 'Mixer',
            'slug' => 'mixer-'.fake()->unique()->numerify('###'),
        ]);
        $asset = $this->makeAsset($itemType);

        $procedure = RepairProcedure::factory()->create([
            'title' => 'Mixer PSU swap',
            'item_type_id' => $itemType->id,
            'safety_notes' => 'Isolate power',
        ]);
        RepairProcedureStep::factory()->create([
            'repair_procedure_id' => $procedure->id,
            'sort_order' => 0,
            'title' => 'Isolate power',
            'is_required' => true,
        ]);
        RepairProcedureStep::factory()->create([
            'repair_procedure_id' => $procedure->id,
            'sort_order' => 1,
            'title' => 'Swap PSU',
            'is_required' => true,
        ]);

        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);

        $this->actingAs($user)->post(route('assets.repairs.store', $asset), [
            'maintenance_log_id' => $fault->id,
            'repair_procedure_id' => $procedure->id,
        ])->assertRedirect();

        $repair = Repair::query()->with('steps')->first();
        $this->assertCount(2, $repair->steps);
        $this->assertSame('Isolate power', $repair->steps->first()->title);

        $otherAsset = $this->makeAsset();
        $manual = File::factory()->create(['category' => FileCategory::Manual, 'title' => 'Unit manual']);
        app(FileAttachmentService::class)->link($manual, $otherAsset);

        $otherFault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $otherAsset->id,
            'type' => MaintenanceLogType::Fault,
        ]);

        $this->actingAs($user)->post(route('assets.repairs.store', $otherAsset), [
            'maintenance_log_id' => $otherFault->id,
        ])->assertRedirect();

        $blankRepair = Repair::query()->where('asset_id', $otherAsset->id)->with('steps')->first();
        $this->assertCount(0, $blankRepair->steps);
        $this->assertNull($blankRepair->repair_procedure_id);

        $this->actingAs($user)
            ->get(route('repairs.show', $blankRepair))
            ->assertOk()
            ->assertSee('Unit manual')
            ->assertSee('Reference docs');
    }

    public function test_complete_appends_service_log_and_optional_status_change(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $asset->update(['status' => AssetStatus::Maintenance]);

        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);

        $this->actingAs($user)->post(route('assets.repairs.store', $asset), [
            'maintenance_log_id' => $fault->id,
            'summary' => 'PSU repair',
        ]);

        $repair = Repair::query()->first();
        $step = $repair->steps()->create([
            'sort_order' => 0,
            'title' => 'Verify',
            'is_required' => true,
        ]);

        $this->actingAs($user)->patch(route('repairs.steps.update', [$repair, $step]), [
            'completed' => '1',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('repairs.complete', $repair), [
            'resolution_summary' => 'PSU replaced and tested',
            'asset_status' => AssetStatus::Available->value,
        ])->assertRedirect(route('repairs.show', $repair));

        $repair->refresh();
        $asset->refresh();
        $this->assertSame(RepairStatus::Completed, $repair->status);
        $this->assertSame(AssetStatus::Available, $asset->status);
        $this->assertTrue(
            AssetMaintenanceLog::query()
                ->where('asset_id', $asset->id)
                ->where('type', MaintenanceLogType::Service)
                ->exists()
        );
    }

    public function test_cancel_does_not_reverse_stock(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $spare = SparePart::factory()->create(['quantity_on_hand' => 5]);
        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);

        $this->actingAs($user)->post(route('assets.repairs.store', $asset), [
            'maintenance_log_id' => $fault->id,
        ]);
        $repair = Repair::query()->first();

        $this->actingAs($user)->post(route('repairs.parts.store', $repair), [
            'spare_part_id' => $spare->id,
            'type' => SparePartMovementType::Use->value,
            'quantity' => 2,
            'reason' => 'Consumed during diagnosis',
        ])->assertRedirect();

        $spare->refresh();
        $this->assertSame(3, $spare->quantity_on_hand);

        $this->actingAs($user)->post(route('repairs.cancel', $repair), [
            'reason' => 'Waiting on OEM part',
        ])->assertRedirect();

        $spare->refresh();
        $repair->refresh();
        $this->assertSame(3, $spare->quantity_on_hand);
        $this->assertSame(RepairStatus::Cancelled, $repair->status);
        $this->assertTrue(
            AssetMaintenanceLog::query()
                ->where('asset_id', $asset->id)
                ->where('type', MaintenanceLogType::Note)
                ->exists()
        );
    }

    public function test_part_use_writes_repair_id_and_decrements_stock(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $spare = SparePart::factory()->create(['quantity_on_hand' => 5]);
        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);

        $this->actingAs($user)->post(route('assets.repairs.store', $asset), [
            'maintenance_log_id' => $fault->id,
        ]);
        $repair = Repair::query()->first();

        $this->actingAs($user)->post(route('repairs.parts.store', $repair), [
            'spare_part_id' => $spare->id,
            'type' => SparePartMovementType::Use->value,
            'quantity' => 1,
            'reason' => 'Issued on repair',
        ])->assertRedirect();

        $spare->refresh();
        $this->assertSame(4, $spare->quantity_on_hand);
        $this->assertDatabaseHas('spare_part_movements', [
            'spare_part_id' => $spare->id,
            'repair_id' => $repair->id,
            'against_asset_id' => $asset->id,
            'type' => SparePartMovementType::Use->value,
        ]);
    }

    public function test_scanner_can_progress_but_cannot_author_procedures(): void
    {
        $scanner = $this->scanner();
        $manager = $this->manager();

        $this->actingAs($scanner)->get(route('repair-procedures.create'))->assertForbidden();
        $this->actingAs($scanner)->post(route('repair-procedures.store'), [
            'title' => 'Nope',
        ])->assertForbidden();

        $this->actingAs($manager)->post(route('repair-procedures.store'), [
            'title' => 'Latch SOP',
            'steps' => [
                ['title' => 'Remove cover', 'is_required' => true],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('repair_procedures', ['title' => 'Latch SOP']);

        $asset = $this->makeAsset();
        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);

        $this->actingAs($scanner)->post(route('assets.repairs.store', $asset), [
            'maintenance_log_id' => $fault->id,
        ])->assertRedirect();

        $repair = Repair::query()->first();
        $this->actingAs($scanner)->get(route('repairs.show', $repair))->assertOk();
    }

    public function test_cannot_start_second_open_repair_on_same_fault(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $fault = AssetMaintenanceLog::factory()->create([
            'asset_id' => $asset->id,
            'type' => MaintenanceLogType::Fault,
        ]);

        $this->actingAs($user)->post(route('assets.repairs.store', $asset), [
            'maintenance_log_id' => $fault->id,
            'summary' => 'First',
        ])->assertRedirect();

        $this->actingAs($user)->from(route('assets.show', [$asset, 'tab' => 'maintenance']))
            ->post(route('assets.repairs.store', $asset), [
                'maintenance_log_id' => $fault->id,
                'summary' => 'Second',
            ])
            ->assertSessionHasErrors('maintenance_log_id');

        $this->assertSame(1, Repair::query()->count());
    }

    public function test_data_hub_lists_repairs_for_all_roles_and_procedures_for_managers(): void
    {
        $this->actingAs($this->scanner())->get(route('data.index'))
            ->assertOk()
            ->assertSee('Repairs')
            ->assertDontSee('Repair procedures');

        $this->actingAs($this->manager())->get(route('data.index'))
            ->assertOk()
            ->assertSee('Repairs')
            ->assertSee('Repair procedures');
    }
}

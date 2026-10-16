<?php

namespace Database\Factories;

use App\Enums\MaintenanceLogType;
use App\Enums\RepairStatus;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\Repair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repair>
 */
class RepairFactory extends Factory
{
    protected $model = Repair::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'maintenance_log_id' => fn (array $attributes) => AssetMaintenanceLog::factory()->create([
                'asset_id' => $attributes['asset_id'],
                'type' => MaintenanceLogType::Fault,
            ])->id,
            'repair_procedure_id' => null,
            'status' => RepairStatus::Open,
            'assignee_user_id' => null,
            'summary' => fake()->sentence(4),
            'notes' => null,
            'resolution_summary' => null,
            'started_at' => now(),
            'completed_at' => null,
            'completed_by_user_id' => null,
            'asset_status_before' => null,
            'asset_status_after' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => RepairStatus::InProgress,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => RepairStatus::Completed,
            'completed_at' => now(),
            'resolution_summary' => 'Resolved',
        ]);
    }
}

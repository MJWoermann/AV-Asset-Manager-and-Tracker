<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceLogType;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetMaintenanceLog>
 */
class AssetMaintenanceLogFactory extends Factory
{
    protected $model = AssetMaintenanceLog::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'user_id' => User::factory(),
            'type' => MaintenanceLogType::Note,
            'summary' => fake()->sentence(6),
            'body' => fake()->optional()->paragraph(),
            'occurred_at' => now(),
            'status_before' => null,
            'status_after' => null,
        ];
    }

    public function fault(): static
    {
        return $this->state(fn (): array => [
            'type' => MaintenanceLogType::Fault,
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (): array => [
            'type' => MaintenanceLogType::Service,
        ]);
    }

    public function withStatusChange(AssetStatus $before, AssetStatus $after): static
    {
        return $this->state(fn (): array => [
            'type' => MaintenanceLogType::Fault,
            'status_before' => $before->value,
            'status_after' => $after->value,
        ]);
    }
}

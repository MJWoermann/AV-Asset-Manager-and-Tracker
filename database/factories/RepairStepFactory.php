<?php

namespace Database\Factories;

use App\Models\Repair;
use App\Models\RepairStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairStep>
 */
class RepairStepFactory extends Factory
{
    protected $model = RepairStep::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'repair_id' => Repair::factory(),
            'sort_order' => 0,
            'title' => fake()->sentence(4),
            'body' => fake()->optional()->paragraph(),
            'is_required' => true,
            'completed_at' => null,
            'completed_by_user_id' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'completed_at' => now(),
        ]);
    }
}

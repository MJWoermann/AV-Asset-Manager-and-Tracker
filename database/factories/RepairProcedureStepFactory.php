<?php

namespace Database\Factories;

use App\Models\RepairProcedure;
use App\Models\RepairProcedureStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairProcedureStep>
 */
class RepairProcedureStepFactory extends Factory
{
    protected $model = RepairProcedureStep::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'repair_procedure_id' => RepairProcedure::factory(),
            'sort_order' => 0,
            'title' => fake()->sentence(4),
            'body' => fake()->optional()->paragraph(),
            'is_required' => true,
        ];
    }
}

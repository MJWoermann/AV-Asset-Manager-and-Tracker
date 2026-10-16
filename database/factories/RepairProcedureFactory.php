<?php

namespace Database\Factories;

use App\Models\RepairProcedure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairProcedure>
 */
class RepairProcedureFactory extends Factory
{
    protected $model = RepairProcedure::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'summary' => fake()->optional()->sentence(),
            'safety_notes' => fake()->optional()->paragraph(),
            'tools_required' => fake()->optional()->sentence(),
            'estimated_minutes' => fake()->optional()->numberBetween(15, 180),
            'manufacturer_model_id' => null,
            'item_type_id' => null,
            'spare_part_id' => null,
        ];
    }
}

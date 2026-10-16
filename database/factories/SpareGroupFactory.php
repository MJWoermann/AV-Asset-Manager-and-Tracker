<?php

namespace Database\Factories;

use App\Models\SpareGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpareGroup>
 */
class SpareGroupFactory extends Factory
{
    protected $model = SpareGroup::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'location_id' => null,
            'asset_id' => null,
            'notes' => null,
        ];
    }
}

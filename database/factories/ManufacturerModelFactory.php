<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManufacturerModel>
 */
class ManufacturerModelFactory extends Factory
{
    protected $model = ManufacturerModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'manufacturer_id' => Manufacturer::factory(),
            'name' => fake()->unique()->bothify('Model-???-##'),
        ];
    }
}

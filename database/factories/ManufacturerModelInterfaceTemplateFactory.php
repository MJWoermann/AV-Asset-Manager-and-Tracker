<?php

namespace Database\Factories;

use App\Models\ManufacturerModel;
use App\Models\ManufacturerModelInterfaceTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManufacturerModelInterfaceTemplate>
 */
class ManufacturerModelInterfaceTemplateFactory extends Factory
{
    protected $model = ManufacturerModelInterfaceTemplate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'manufacturer_model_id' => ManufacturerModel::factory(),
            'name' => fake()->randomElement(['LAN 1', 'HDMI Out', 'SDI In', 'Dante', 'Power']),
            'sort_order' => fake()->numberBetween(0, 50),
            'notes' => null,
            'connection_type_id' => null,
        ];
    }
}

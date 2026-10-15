<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetInterface;
use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetInterface>
 */
class AssetInterfaceFactory extends Factory
{
    protected $model = AssetInterface::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'name' => fake()->randomElement(['LAN 1', 'HDMI Out', 'SDI In', 'Aux']),
            'connection_type_id' => ConnectionType::query()->inRandomOrder()->value('id')
                ?? ConnectionType::factory(),
            'sort_order' => fake()->numberBetween(0, 50),
            'item_type_interface_template_id' => null,
            'notes' => null,
        ];
    }
}

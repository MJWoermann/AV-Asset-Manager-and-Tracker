<?php

namespace Database\Factories;

use App\Models\ItemType;
use App\Models\ItemTypeInterfaceTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemTypeInterfaceTemplate>
 */
class ItemTypeInterfaceTemplateFactory extends Factory
{
    protected $model = ItemTypeInterfaceTemplate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'item_type_id' => ItemType::factory(),
            'name' => fake()->randomElement(['LAN 1', 'HDMI Out', 'SDI In', 'Dante', 'Power']),
            'sort_order' => fake()->numberBetween(0, 50),
            'notes' => null,
        ];
    }
}

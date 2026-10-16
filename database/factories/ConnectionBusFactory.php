<?php

namespace Database\Factories;

use App\Models\ConnectionBus;
use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConnectionBus>
 */
class ConnectionBusFactory extends Factory
{
    protected $model = ConnectionBus::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'connection_type_id' => ConnectionType::factory()->busCapable(),
            'name' => fake()->unique()->words(3, true).' bus',
            'notes' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ConnectionType;
use App\Support\ConnectionTypeIcons;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConnectionType>
 */
class ConnectionTypeFactory extends Factory
{
    protected $model = ConnectionType::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(ConnectionTypeIcons::keys()),
            'allows_protocol' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function withProtocol(): static
    {
        return $this->state(fn (): array => [
            'allows_protocol' => true,
            'icon' => 'custom',
        ]);
    }
}

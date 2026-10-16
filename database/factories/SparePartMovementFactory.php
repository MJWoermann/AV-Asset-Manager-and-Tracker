<?php

namespace Database\Factories;

use App\Enums\SparePartMovementType;
use App\Models\SparePart;
use App\Models\SparePartMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SparePartMovement>
 */
class SparePartMovementFactory extends Factory
{
    protected $model = SparePartMovement::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $before = fake()->numberBetween(0, 20);
        $after = fake()->numberBetween(0, 20);

        return [
            'spare_part_id' => SparePart::factory(),
            'type' => SparePartMovementType::Count,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'quantity_delta' => $after - $before,
            'against_asset_id' => null,
            'reason' => null,
            'ticket_ref' => null,
            'quoting_status' => null,
            'user_id' => null,
            'notes' => null,
            'recorded_at' => now(),
        ];
    }
}

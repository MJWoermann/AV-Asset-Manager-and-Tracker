<?php

namespace Database\Factories;

use App\Models\RigEvent;
use App\Models\RigEventMotorPatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RigEventMotorPatch>
 */
class RigEventMotorPatchFactory extends Factory
{
    protected $model = RigEventMotorPatch::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'rig_event_id' => RigEvent::factory(),
            'sort_order' => 0,
            'motor_label' => 'M'.fake()->numberBetween(1, 16),
            'patch_to' => 'Ch '.fake()->numberBetween(1, 48),
            'notes' => null,
        ];
    }
}

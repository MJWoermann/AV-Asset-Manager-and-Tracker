<?php

namespace Database\Factories;

use App\Enums\RigEventStatus;
use App\Models\RigEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RigEvent>
 */
class RigEventFactory extends Factory
{
    protected $model = RigEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Rig',
            'venue_space' => fake()->optional()->randomElement(['Auditorium', 'Hall A', 'Gallery']),
            'event_date' => fake()->optional()->date(),
            'status' => RigEventStatus::Draft,
            'engineer_required' => false,
            'engineer_acquired' => false,
            'process_checklist' => [],
            'created_by' => User::factory(),
        ];
    }

    public function signedOff(): static
    {
        return $this->state(fn (): array => [
            'status' => RigEventStatus::SignedOff,
            'structure_signed_name' => fake()->name(),
            'structure_signed_at' => now(),
            'structure_notes' => 'Works completed per approved plan.',
        ]);
    }
}

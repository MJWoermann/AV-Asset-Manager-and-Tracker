<?php

namespace Database\Factories;

use App\Models\RiggingCalculationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiggingCalculationLog>
 */
class RiggingCalculationLogFactory extends Factory
{
    protected $model = RiggingCalculationLog::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'calculator' => 'two_point_hang',
            'title' => 'Sample calc',
            'inputs' => ['W' => 100, 'L' => 10, 'x' => 4],
            'results' => ['RA' => 60, 'RB' => 40],
            'units' => ['force' => 'kg', 'length' => 'm'],
            'rig_event_id' => null,
            'created_by' => User::factory(),
        ];
    }
}

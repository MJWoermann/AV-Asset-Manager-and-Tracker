<?php

namespace App\Support;

class RiggingCalculators
{
    public const G = 9.80665;

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            'two_point_hang' => '2-point hang',
            'lever' => 'Lever (Class I / II / III)',
            'bridle_two_leg' => 'Bridle (two-leg)',
            'bridle_multi_leg' => 'Bridle (multi-leg)',
            'sling_angle_factor' => 'Sling angle factor',
            'dead_hang' => 'Dead hang / WLL check',
            'beam_point_load' => 'Beam point loads',
            'udl_multi_point' => 'UDL multi-point',
            'projector_lens' => 'Projector lens / throw',
            'unit_convert' => 'Unit convert',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    public static function label(string $key): string
    {
        return self::definitions()[$key] ?? $key;
    }
}

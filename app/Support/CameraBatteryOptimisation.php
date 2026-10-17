<?php

namespace App\Support;

class CameraBatteryOptimisation
{
    public const PREFERENCE_KEY = 'camera_battery_optimisation';

    /**
     * @param  array<string, mixed>|null  $preferences
     */
    public static function enabled(?array $preferences): bool
    {
        return BoolPreference::enabled($preferences, self::PREFERENCE_KEY);
    }

    /**
     * Merge the camera battery-optimisation flag into preferences without wiping other keys.
     *
     * @param  array<string, mixed>|null  $preferences
     * @return array<string, mixed>
     */
    public static function mergeIntoPreferences(?array $preferences, bool $enabled): array
    {
        return BoolPreference::mergeIntoPreferences($preferences, self::PREFERENCE_KEY, $enabled);
    }
}

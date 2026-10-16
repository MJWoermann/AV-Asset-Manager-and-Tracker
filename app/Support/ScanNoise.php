<?php

namespace App\Support;

class ScanNoise
{
    public const PREFERENCE_KEY = 'scan_noise';

    /**
     * @param  array<string, mixed>|null  $preferences
     */
    public static function enabled(?array $preferences): bool
    {
        return (bool) ($preferences[self::PREFERENCE_KEY] ?? false);
    }

    /**
     * Merge the scan-noise flag into preferences without wiping other keys.
     *
     * @param  array<string, mixed>|null  $preferences
     * @return array<string, mixed>
     */
    public static function mergeIntoPreferences(?array $preferences, bool $enabled): array
    {
        $preferences ??= [];
        $preferences[self::PREFERENCE_KEY] = $enabled;

        return $preferences;
    }
}

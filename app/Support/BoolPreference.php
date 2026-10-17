<?php

namespace App\Support;

class BoolPreference
{
    /**
     * @param  array<string, mixed>|null  $preferences
     */
    public static function enabled(?array $preferences, string $key): bool
    {
        return (bool) ($preferences[$key] ?? false);
    }

    /**
     * Merge a boolean preference without wiping other keys.
     *
     * @param  array<string, mixed>|null  $preferences
     * @return array<string, mixed>
     */
    public static function mergeIntoPreferences(?array $preferences, string $key, bool $enabled): array
    {
        $preferences ??= [];
        $preferences[$key] = $enabled;

        return $preferences;
    }
}

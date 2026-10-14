<?php

namespace App\Support;

class DeveloperFeatures
{
    public const PREFERENCE_KEY = 'features';

    /**
     * Known developer feature keys and profile labels.
     *
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            'asset_connections' => 'Asset connections',
            'ru_visualiser' => 'RU visualiser',
            'shelf_tag_generator' => 'Shelf tag generator',
            'stock_consumables' => 'Stock / consumables',
            'email_integration' => 'Email integration',
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

    /**
     * @param  array<string, mixed>|null  $preferences
     */
    public static function enabled(?array $preferences, string $key): bool
    {
        if (! self::isValid($key)) {
            return false;
        }

        $features = $preferences[self::PREFERENCE_KEY] ?? null;

        if (! is_array($features)) {
            return false;
        }

        return (bool) ($features[$key] ?? false);
    }

    /**
     * Merge feature booleans into preferences without wiping other keys
     * (e.g. asset_table_columns).
     *
     * @param  array<string, mixed>|null  $preferences
     * @param  array<string, bool>  $features
     * @return array<string, mixed>
     */
    public static function mergeIntoPreferences(?array $preferences, array $features): array
    {
        $preferences ??= [];
        $existing = is_array($preferences[self::PREFERENCE_KEY] ?? null)
            ? $preferences[self::PREFERENCE_KEY]
            : [];

        foreach (self::keys() as $key) {
            if (array_key_exists($key, $features)) {
                $existing[$key] = (bool) $features[$key];
            }
        }

        $preferences[self::PREFERENCE_KEY] = $existing;

        return $preferences;
    }
}

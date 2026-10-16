<?php

namespace App\Support;

class DepartmentVisibility
{
    public const PREFERENCE_KEY = 'visible_department_ids';

    public const SESSION_KEY = 'active_department_id';

    /**
     * Admin preference → scope ids. Null means unrestricted (show all).
     *
     * @param  array<string, mixed>|null  $preferences
     * @return list<int>|null
     */
    public static function scopeIdsFromPreferences(?array $preferences): ?array
    {
        $ids = $preferences[self::PREFERENCE_KEY] ?? null;

        if (! is_array($ids) || $ids === []) {
            return null;
        }

        return self::normalizeIds($ids);
    }

    /**
     * @param  array<int|string>|null  $ids
     * @return list<int>
     */
    public static function normalizeIds(?array $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        $normalized = [];

        foreach ($ids as $id) {
            if ($id === null || $id === '') {
                continue;
            }

            $normalized[] = (int) $id;
        }

        return array_values(array_unique(array_filter($normalized, fn (int $id) => $id > 0)));
    }

    /**
     * Merge visible_department_ids into preferences without wiping other keys.
     *
     * @param  array<string, mixed>|null  $preferences
     * @param  list<int>  $departmentIds  Empty clears the filter (show all).
     * @return array<string, mixed>
     */
    public static function mergePreference(?array $preferences, array $departmentIds): array
    {
        $preferences = is_array($preferences) ? $preferences : [];
        $normalized = self::normalizeIds($departmentIds);

        if ($normalized === []) {
            unset($preferences[self::PREFERENCE_KEY]);
        } else {
            $preferences[self::PREFERENCE_KEY] = $normalized;
        }

        return $preferences;
    }
}

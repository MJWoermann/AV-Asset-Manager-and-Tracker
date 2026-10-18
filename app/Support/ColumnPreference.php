<?php

namespace App\Support;

class ColumnPreference
{
    /**
     * Filter selected keys to an allow-list, fall back to defaults, and force a required key first.
     *
     * @param  list<string>|null  $selected
     * @param  list<string>  $allowed
     * @param  list<string>  $defaults
     * @return list<string>
     */
    public static function resolve(
        ?array $selected,
        array $allowed,
        array $defaults,
        string $required = 'name',
    ): array {
        $columns = collect($selected ?? [])
            ->filter(fn ($key) => is_string($key) && in_array($key, $allowed, true))
            ->values()
            ->all();

        if ($columns === []) {
            $columns = $defaults;
        }

        if ($required !== '' && ! in_array($required, $columns, true)) {
            array_unshift($columns, $required);
        }

        return array_values(array_unique($columns));
    }
}

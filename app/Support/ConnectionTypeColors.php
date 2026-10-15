<?php

namespace App\Support;

class ConnectionTypeColors
{
    /**
     * Curated palette shown on the connection type form.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            '#2563eb',
            '#00adb7',
            '#3dcbd4',
            '#005556',
            '#16a34a',
            '#dcaa0b',
            '#ea580c',
            '#dc2626',
            '#910e6b',
            '#7c3aed',
            '#64748b',
            '#000000',
        ];
    }

    public static function isValid(string $color): bool
    {
        return in_array(strtolower($color), self::keys(), true);
    }

    /**
     * Palette for a form, including a current non-palette value when editing.
     *
     * @return list<string>
     */
    public static function optionsFor(?string $current = null): array
    {
        $colors = self::keys();
        $current = $current !== null ? strtolower($current) : null;

        if ($current !== null && $current !== '' && ! in_array($current, $colors, true)) {
            $colors[] = $current;
        }

        return $colors;
    }
}

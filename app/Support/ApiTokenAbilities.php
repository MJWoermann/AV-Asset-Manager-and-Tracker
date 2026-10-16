<?php

namespace App\Support;

class ApiTokenAbilities
{
    public const FULL = '*';

    /**
     * Allow-listed Sanctum token abilities and human labels.
     *
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            'user:read' => 'Read current user',
            'assets:read' => 'List and view assets',
            'assets:write' => 'Create and update assets',
            'locations:read' => 'List and view locations',
            'locations:write' => 'Create, update, and delete locations',
            'catalog:read' => 'View manufacturers, models, item types, field sets',
            'catalog:write' => 'Manage manufacturers, models, item types, field sets',
            'lists:read' => 'List and view inventory/event lists',
            'lists:write' => 'Create, update, and delete lists',
            'lists:compare' => 'Compare scanned and inventory lists',
            'scan' => 'Record scans into a scan session',
            'scan-sessions' => 'Start, update, and close scan sessions',
            'spares:read' => 'List and view spares and groups',
            'spares:write' => 'Create and update spares, groups, supersede',
            'repairs:read' => 'View repairs and procedures',
            'repairs:write' => 'Create and update repairs; manage procedures',
            'files:read' => 'Search, download, and view files',
            'files:write' => 'Upload, link, and delete files',
            'reports:read' => 'Unknown scans, exports, dashboard stats',
            'reports:write' => 'Update unknown scans; admin purge/dedupe',
            'audit:read' => 'Read asset audit history',
            'admin:read' => 'View users and departments',
            'admin:write' => 'Manage users and departments',
            'connections:read' => 'View asset connections and maps',
            'connections:write' => 'Manage interfaces, connections, buses',
            'stock:read' => 'View stock levels and guest tokens',
            'stock:write' => 'Record stock movements and rotate tokens',
            'labels:read' => 'View shelf tag layouts and PDFs',
            'labels:write' => 'Manage shelf tag layouts',
            'rigging:read' => 'View rigging events and calculators',
            'rigging:write' => 'Manage rigging events and calculations',
            'email:ops' => 'Apply or reject inbound email actions',
            self::FULL => 'Full access',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Named scopes only (excludes wildcard full access).
     *
     * @return list<string>
     */
    public static function scoped(): array
    {
        return array_values(array_filter(
            self::all(),
            fn (string $ability): bool => $ability !== self::FULL,
        ));
    }

    public static function label(string $ability): string
    {
        return self::definitions()[$ability] ?? $ability;
    }

    public static function isAllowed(string $ability): bool
    {
        return array_key_exists($ability, self::definitions());
    }

    /**
     * Normalize omitted/empty abilities to full access; collapse selections that include *.
     *
     * @param  list<string>|null  $abilities
     * @return list<string>
     */
    public static function normalize(?array $abilities): array
    {
        if ($abilities === null || $abilities === []) {
            return [self::FULL];
        }

        $abilities = array_values(array_unique(array_map('strval', $abilities)));

        if (in_array(self::FULL, $abilities, true)) {
            return [self::FULL];
        }

        return $abilities;
    }
}

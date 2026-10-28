<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessPermissions
{
    public const FEATURE_PREFIX = 'features:';

    /**
     * @var list<string>
     */
    public const SYSTEM_ROLES = [
        'admin',
        'inventory_manager',
        'scanner',
        'external_repairer',
    ];

    /**
     * Operational permissions, grouped for the role editor.
     * Keys match API token abilities except where a sibling is stricter than that ability.
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'Account' => [
                'user:read' => 'Read current user',
            ],
            'Assets' => [
                'assets:read' => 'List and view assets',
                'assets:write' => 'Create and update assets',
                'assets:delete' => 'Delete assets, maintenance logs, and duplicate tools',
                'audit:read' => 'Read asset audit history',
            ],
            'Locations' => [
                'locations:read' => 'List and view locations',
                'locations:write' => 'Create locations',
                'locations:update' => 'Update, delete, and bulk-update locations',
            ],
            'Catalogue' => [
                'catalog:read' => 'View manufacturers, models, item types, field sets',
                'catalog:write' => 'Create manufacturers and models',
                'catalog:update' => 'Edit the catalogue, item types, and field sets',
                'catalog:bulk' => 'Bulk-update item types',
            ],
            'Lists and scan' => [
                'lists:read' => 'List and view inventory/event lists',
                'lists:write' => 'Create, update, and delete lists',
                'lists:bulk' => 'Bulk-update lists',
                'lists:compare' => 'Compare scanned and inventory lists',
                'scan' => 'Record scans into a scan session',
                'scan-sessions' => 'Start, update, and close scan sessions',
            ],
            'Spares' => [
                'spares:read' => 'List and view spares and groups',
                'spares:write' => 'Record spare movements',
                'spares:manage' => 'Create and update spares, groups, and supersede',
            ],
            'Repairs' => [
                'repairs:read' => 'View repairs',
                'repairs:write' => 'Create and update repairs',
                'repairs:procedures' => 'Manage repair procedures',
            ],
            'Files' => [
                'files:read' => 'Search, download, and view linked files',
                'files:write' => 'Upload and link files',
                'files:manage' => 'File library index and delete',
            ],
            'Reports' => [
                'reports:read' => 'Unknown scans, dashboard stats',
                'reports:write' => 'Update and link unknown scans',
                'reports:export' => 'Export assets, lists, and checkout holdings',
                'reports:purge' => 'Purge and dedupe unknown scans',
                'import:run' => 'Run spreadsheet imports',
            ],
            'Connections' => [
                'connections:read' => 'View asset connections and maps',
                'connections:write' => 'Manage interfaces, connections, buses, and rack placements',
            ],
            'Stock' => [
                'stock:read' => 'View stock levels and guest tokens',
                'stock:write' => 'Record stock movements',
                'stock:manage' => 'Rotate guest tokens and print kanban tags',
                'stock:adjust' => 'Edit or delete a stock movement',
            ],
            'Labels' => [
                'labels:read' => 'View shelf tag layouts and PDFs',
                'labels:write' => 'Manage shelf tag layouts',
            ],
            'Rigging' => [
                'rigging:read' => 'View rigging events and calculators',
                'rigging:write' => 'Manage rigging events and calculations',
            ],
            'Email' => [
                'email:ops' => 'Apply or reject inbound email actions',
            ],
            'Administration' => [
                'admin:read' => 'View users and departments',
                'admin:write' => 'Manage users, departments, and roles',
                'admin:settings' => 'Export columns, storage, SSO, and email settings',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        $definitions = [];

        foreach (self::groups() as $permissions) {
            foreach ($permissions as $name => $label) {
                $definitions[$name] = $label;
            }
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    public static function operational(): array
    {
        return array_keys(self::definitions());
    }

    public static function isOperational(string $permission): bool
    {
        return array_key_exists($permission, self::definitions());
    }

    public static function featureName(string $feature): string
    {
        return self::FEATURE_PREFIX.$feature;
    }

    /**
     * @return array<string, string>
     */
    public static function featureDefinitions(): array
    {
        $definitions = [];

        foreach (DeveloperFeatures::definitions() as $key => $label) {
            $definitions[self::featureName($key)] = $label;
        }

        return $definitions;
    }

    public static function isFeature(string $permission): bool
    {
        return array_key_exists($permission, self::featureDefinitions());
    }

    public static function featureKey(string $permission): ?string
    {
        if (! self::isFeature($permission)) {
            return null;
        }

        return substr($permission, strlen(self::FEATURE_PREFIX));
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(self::operational(), array_keys(self::featureDefinitions()));
    }

    public static function label(string $permission): string
    {
        return self::definitions()[$permission]
            ?? self::featureDefinitions()[$permission]
            ?? $permission;
    }

    public static function isSystemRole(string $name): bool
    {
        return in_array($name, self::SYSTEM_ROLES, true);
    }

    public static function roleLabel(string $name): string
    {
        return match ($name) {
            'admin' => 'Administrator',
            'inventory_manager' => 'Inventory manager',
            'scanner' => 'Scanner',
            'external_repairer' => 'External repairer',
            default => $name,
        };
    }

    /**
     * Default operational permissions for a built-in role.
     * Admin is not listed: that role passes every operational check.
     * Developer features are not seeded.
     *
     * @return list<string>
     */
    public static function defaultsFor(string $role): array
    {
        $scanner = [
            'user:read',
            'assets:read',
            'assets:write',
            'audit:read',
            'locations:read',
            'locations:write',
            'catalog:read',
            'catalog:write',
            'lists:read',
            'lists:compare',
            'scan',
            'scan-sessions',
            'spares:read',
            'spares:write',
            'repairs:read',
            'repairs:write',
            'files:read',
            'files:write',
            'reports:read',
            'reports:write',
            'connections:read',
            'stock:read',
            'stock:write',
            'rigging:read',
            'rigging:write',
        ];

        $manager = array_values(array_unique(array_merge($scanner, [
            'assets:delete',
            'locations:update',
            'catalog:update',
            'lists:write',
            'files:manage',
            'spares:manage',
            'import:run',
            'reports:export',
            'repairs:procedures',
            'connections:write',
            'stock:manage',
            'labels:read',
            'labels:write',
            'email:ops',
        ])));

        return match ($role) {
            'scanner' => $scanner,
            'inventory_manager' => $manager,
            'external_repairer' => [
                'assets:read',
                'repairs:read',
                'repairs:write',
            ],
            default => [],
        };
    }

    /**
     * Create permission rows and assign built-in defaults only when that role has none yet.
     */
    public static function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::all() as $name) {
            Permission::findOrCreate($name);
        }

        foreach (self::SYSTEM_ROLES as $roleName) {
            $role = Role::findOrCreate($roleName);

            if ($roleName === 'admin' || $role->permissions()->exists()) {
                continue;
            }

            $defaults = self::defaultsFor($roleName);

            if ($defaults !== []) {
                $role->syncPermissions($defaults);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

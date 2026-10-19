<?php

namespace App\Support;

use App\Models\User;

class DashboardModules
{
    public const PREFERENCE_KEY = 'dashboard_modules';

    public const QUICK_LINKS = 'quick_links';

    public const STOCKTAKE_PULSE = 'stocktake_pulse';

    public const ASSETS_NEEDING_ATTENTION = 'assets_needing_attention';

    public const ASSETS_BY_LOCATION = 'assets_by_location';

    public const DATA_QUALITY = 'data_quality';

    public const LIST_ACTIVITY = 'list_activity';

    public const MY_REPAIRS = 'my_repairs';

    public const SPARE_MOVEMENTS_RECENT = 'spare_movements_recent';

    public const CONSUMABLES_STATS = 'consumables_stats';

    public const CONNECTION_COVERAGE = 'connection_coverage';

    public const RIGGING_UPCOMING = 'rigging_upcoming';

    public const WARRANTY_EXPIRY = 'warranty_expiry';

    public const TEST_TAG_EXPIRY = 'test_tag_expiry';

    public const ASSETS_BY_STATUS = 'assets_by_status';

    public const ASSETS_BY_TYPE = 'assets_by_type';

    public const ITEMS_USERS_BY_DEPARTMENT = 'items_users_by_department';

    public const INBOUND_EMAILS = 'inbound_emails';

    public const LATEST_CHANGES = 'latest_changes';

    public const MAINTENANCE_ACTIVITIES = 'maintenance_activities';

    public const SPARES_STATS = 'spares_stats';

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            self::QUICK_LINKS => 'Quick links',
            self::STOCKTAKE_PULSE => 'Stocktake pulse',
            self::ASSETS_NEEDING_ATTENTION => 'Assets needing attention',
            self::MAINTENANCE_ACTIVITIES => 'Maintenance activities',
            self::MY_REPAIRS => 'My repairs',
            self::SPARES_STATS => 'Spares statistics',
            self::SPARE_MOVEMENTS_RECENT => 'Recent spare usage',
            self::CONSUMABLES_STATS => 'Consumables statistics',
            self::ASSETS_BY_STATUS => 'Assets by status',
            self::ASSETS_BY_LOCATION => 'Assets by location',
            self::ASSETS_BY_TYPE => 'Assets by type',
            self::ITEMS_USERS_BY_DEPARTMENT => 'Items & users by department',
            self::DATA_QUALITY => 'Catalogue completeness',
            self::LIST_ACTIVITY => 'Lists snapshot',
            self::CONNECTION_COVERAGE => 'Connection coverage',
            self::RIGGING_UPCOMING => 'Rigging events',
            self::WARRANTY_EXPIRY => 'Warranty expiry',
            self::TEST_TAG_EXPIRY => 'Test & Tag expiry',
            self::INBOUND_EMAILS => 'Inbound emails',
            self::LATEST_CHANGES => 'Latest changes',
        ];
    }

    /**
     * Default module order for users without saved preferences.
     *
     * @return list<string>
     */
    public static function defaultOrder(): array
    {
        return [
            self::QUICK_LINKS,
            self::STOCKTAKE_PULSE,
            self::ASSETS_NEEDING_ATTENTION,
            self::MAINTENANCE_ACTIVITIES,
            self::MY_REPAIRS,
            self::SPARES_STATS,
            self::SPARE_MOVEMENTS_RECENT,
            self::CONSUMABLES_STATS,
            self::ASSETS_BY_STATUS,
            self::ASSETS_BY_LOCATION,
            self::ASSETS_BY_TYPE,
            self::ITEMS_USERS_BY_DEPARTMENT,
            self::DATA_QUALITY,
            self::LIST_ACTIVITY,
            self::CONNECTION_COVERAGE,
            self::RIGGING_UPCOMING,
            self::WARRANTY_EXPIRY,
            self::TEST_TAG_EXPIRY,
            self::INBOUND_EMAILS,
            self::LATEST_CHANGES,
        ];
    }

    /**
     * Modules enabled by default for new users (others remain available but off).
     *
     * @return list<string>
     */
    public static function defaultEnabled(): array
    {
        return [
            self::QUICK_LINKS,
            self::STOCKTAKE_PULSE,
            self::ASSETS_NEEDING_ATTENTION,
            self::MAINTENANCE_ACTIVITIES,
            self::SPARES_STATS,
            self::CONSUMABLES_STATS,
            self::ASSETS_BY_STATUS,
            self::ASSETS_BY_LOCATION,
            self::ITEMS_USERS_BY_DEPARTMENT,
            self::LATEST_CHANGES,
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return self::defaultOrder();
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Module keys available to this user (role / feature gated).
     *
     * @return list<string>
     */
    public static function availableFor(?User $user): array
    {
        $keys = self::keys();

        if ($user === null || ! $user->hasRole('admin')) {
            $keys = array_values(array_filter(
                $keys,
                fn (string $key): bool => $key !== self::ITEMS_USERS_BY_DEPARTMENT
            ));
        }

        if ($user === null
            || ! $user->hasFeature('email_integration')
            || ! $user->hasAnyRole(['admin', 'inventory_manager'])) {
            $keys = array_values(array_filter(
                $keys,
                fn (string $key): bool => $key !== self::INBOUND_EMAILS
            ));
        }

        if ($user === null || ! StockTracking::enabledFor($user)) {
            $keys = array_values(array_filter(
                $keys,
                fn (string $key): bool => $key !== self::CONSUMABLES_STATS
            ));
        }

        if ($user === null || ! $user->hasFeature('asset_connections')) {
            $keys = array_values(array_filter(
                $keys,
                fn (string $key): bool => $key !== self::CONNECTION_COVERAGE
            ));
        }

        if ($user === null || ! $user->hasFeature('rigging')) {
            $keys = array_values(array_filter(
                $keys,
                fn (string $key): bool => $key !== self::RIGGING_UPCOMING
            ));
        }

        return $keys;
    }

    public static function label(string $key): string
    {
        return self::definitions()[$key] ?? $key;
    }

    /**
     * Resolve stored preference into ordered module rows for the given available keys.
     *
     * Preference shape:
     * [
     *   'order' => list<string>,
     *   'enabled' => list<string>,
     * ]
     *
     * @param  array<string, mixed>|null  $stored
     * @param  list<string>  $available
     * @return list<array{id: string, label: string, enabled: bool}>
     */
    public static function resolve(?array $stored, array $available): array
    {
        $available = array_values(array_filter(
            $available,
            fn ($key): bool => is_string($key) && isset(self::definitions()[$key])
        ));

        $order = [];
        $enabled = null;

        if (is_array($stored)) {
            $rawOrder = $stored['order'] ?? null;
            if (is_array($rawOrder)) {
                $order = collect($rawOrder)
                    ->filter(fn ($key) => is_string($key) && in_array($key, $available, true))
                    ->unique()
                    ->values()
                    ->all();
            }

            if (array_key_exists('enabled', $stored) && is_array($stored['enabled'])) {
                $enabled = collect($stored['enabled'])
                    ->filter(fn ($key) => is_string($key) && in_array($key, $available, true))
                    ->unique()
                    ->values()
                    ->all();
            }
        } else {
            $order = array_values(array_filter(
                self::defaultOrder(),
                fn (string $key): bool => in_array($key, $available, true)
            ));
            $enabled = array_values(array_filter(
                self::defaultEnabled(),
                fn (string $key): bool => in_array($key, $available, true)
            ));
        }

        foreach ($available as $key) {
            if (! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        $enabled ??= $available;

        return array_map(
            fn (string $id): array => [
                'id' => $id,
                'label' => self::label($id),
                'enabled' => in_array($id, $enabled, true),
            ],
            $order
        );
    }

    /**
     * @param  list<array{id: string, label: string, enabled: bool}>  $modules
     * @return list<string>
     */
    public static function enabledIds(array $modules): array
    {
        return collect($modules)
            ->filter(fn (array $module): bool => ($module['enabled'] ?? false) === true)
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * Normalize request payload into a preference value.
     *
     * @param  list<string>  $order
     * @param  list<string>|null  $enabled
     * @param  list<string>  $available
     * @return array{order: list<string>, enabled: list<string>}
     */
    public static function normalize(array $order, ?array $enabled, array $available): array
    {
        $resolved = self::resolve([
            'order' => $order,
            'enabled' => $enabled ?? [],
        ], $available);

        return [
            'order' => array_column($resolved, 'id'),
            'enabled' => self::enabledIds($resolved),
        ];
    }
}

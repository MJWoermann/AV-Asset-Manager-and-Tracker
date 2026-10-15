<?php

namespace App\Support;

use App\Models\User;

class DashboardModules
{
    public const PREFERENCE_KEY = 'dashboard_modules';

    public const QUICK_LINKS = 'quick_links';

    public const WARRANTY_EXPIRY = 'warranty_expiry';

    public const TEST_TAG_EXPIRY = 'test_tag_expiry';

    public const ASSETS_BY_STATUS = 'assets_by_status';

    public const ASSETS_BY_TYPE = 'assets_by_type';

    public const INBOUND_EMAILS = 'inbound_emails';

    public const LATEST_CHANGES = 'latest_changes';

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            self::QUICK_LINKS => 'Quick links',
            self::WARRANTY_EXPIRY => 'Warranty expiry',
            self::TEST_TAG_EXPIRY => 'Test & Tag expiry',
            self::ASSETS_BY_STATUS => 'Assets by status',
            self::ASSETS_BY_TYPE => 'Assets by type',
            self::INBOUND_EMAILS => 'Inbound emails',
            self::LATEST_CHANGES => 'Latest changes',
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return array_keys(self::definitions());
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
        $keys = self::defaults();

        if ($user === null
            || ! $user->hasFeature('email_integration')
            || ! $user->hasAnyRole(['admin', 'inventory_manager'])) {
            $keys = array_values(array_filter(
                $keys,
                fn (string $key): bool => $key !== self::INBOUND_EMAILS
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

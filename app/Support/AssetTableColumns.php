<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetListItem;

class AssetTableColumns
{
    public const PREFERENCE_KEY = 'asset_table_columns';

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            'name' => 'Name',
            'item_type' => 'Type',
            'status' => 'Status',
            'tp_barcode' => 'TP Barcode',
            'fmi_ast' => 'FMI AST#',
            'rig_tag' => 'RIG Tag #',
            'serial_number' => 'Serial Number',
            'location' => 'Location',
            'manufacturer' => 'Manufacturer',
            'model' => 'Model',
            'ip_address' => 'IP Address',
            'mac_address' => 'MAC Address',
            'quantity' => 'Quantity',
            'test_tag_expiry' => 'Test & Tag',
            'parent' => 'Parent',
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return ['name', 'item_type', 'status', 'tp_barcode', 'location'];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @param  list<string>|null  $selected
     * @return list<string>
     */
    public static function resolve(?array $selected): array
    {
        $allowed = self::keys();
        $columns = collect($selected ?? [])
            ->filter(fn ($key) => is_string($key) && in_array($key, $allowed, true))
            ->values()
            ->all();

        if ($columns === []) {
            $columns = self::defaults();
        }

        if (! in_array('name', $columns, true)) {
            array_unshift($columns, 'name');
        }

        return array_values(array_unique($columns));
    }

    public static function label(string $key): string
    {
        return self::definitions()[$key] ?? $key;
    }

    public static function value(Asset $asset, string $column, ?AssetListItem $item = null): string
    {
        return match ($column) {
            'name' => (string) $asset->name,
            'item_type' => (string) ($asset->itemType?->name ?? ''),
            'status' => (string) ($asset->status?->label() ?? ''),
            'tp_barcode' => (string) ($asset->tp_barcode ?? ''),
            'fmi_ast' => (string) ($asset->fmi_ast ?? ''),
            'rig_tag' => (string) ($asset->rig_tag ?? ''),
            'serial_number' => (string) ($asset->serial_number ?? ''),
            'location' => (string) ($asset->location?->name ?? ''),
            'manufacturer' => (string) ($asset->manufacturer ?? ''),
            'model' => (string) ($asset->model ?? ''),
            'ip_address' => (string) ($asset->ip_address ?? ''),
            'mac_address' => (string) ($asset->mac_address ?? ''),
            'quantity' => (string) ($item?->quantity ?? $asset->quantity ?? ''),
            'test_tag_expiry' => $asset->test_tag_expiry?->format('Y-m-d') ?? '',
            'parent' => (string) ($asset->parent?->name ?? ''),
            default => '',
        };
    }

    public static function isMono(string $column): bool
    {
        return in_array($column, [
            'tp_barcode',
            'fmi_ast',
            'rig_tag',
            'serial_number',
            'ip_address',
            'mac_address',
        ], true);
    }
}

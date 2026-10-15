<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetListItem;
use App\Models\Setting;

class AssetExportColumns
{
    public const ASSETS_KEY = 'export_asset_columns';

    public const LISTS_KEY = 'export_list_columns';

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            'name' => 'Name',
            'description' => 'Description',
            'item_type' => 'Item Type',
            'status' => 'Status',
            'manufacturer' => 'Manufacturer',
            'model' => 'Model',
            'serial_number' => 'Serial Number',
            'fmi_ast' => 'FMI AST#',
            'tp_barcode' => 'TP Barcode',
            'rig_tag' => 'RIG Tag #',
            'ip_address' => 'IP Address',
            'mac_address' => 'MAC Address',
            'quantity' => 'Quantity',
            'location' => 'Location',
            'test_tag_expiry' => 'Test & Tag',
            'parent' => 'Parent',
            'notes' => 'Notes',
        ];
    }

    /**
     * Defaults match the previous hard-coded export columns.
     *
     * @return list<string>
     */
    public static function defaults(): array
    {
        return [
            'name',
            'description',
            'item_type',
            'status',
            'manufacturer',
            'model',
            'serial_number',
            'fmi_ast',
            'tp_barcode',
            'rig_tag',
            'ip_address',
            'mac_address',
            'quantity',
        ];
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

    /**
     * @return list<string>
     */
    public static function forAssets(): array
    {
        return self::resolve(Setting::getValue(self::ASSETS_KEY));
    }

    /**
     * @return list<string>
     */
    public static function forLists(): array
    {
        return self::resolve(Setting::getValue(self::LISTS_KEY));
    }

    /**
     * @param  list<string>  $columns
     */
    public static function putAssets(array $columns): void
    {
        Setting::putValue(self::ASSETS_KEY, self::resolve($columns));
    }

    /**
     * @param  list<string>  $columns
     */
    public static function putLists(array $columns): void
    {
        Setting::putValue(self::LISTS_KEY, self::resolve($columns));
    }

    public static function label(string $key): string
    {
        return self::definitions()[$key] ?? $key;
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    public static function headings(array $columns): array
    {
        return array_map(fn (string $column) => self::label($column), $columns);
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    public static function relationsFor(array $columns): array
    {
        $relations = [];

        if (in_array('item_type', $columns, true)) {
            $relations[] = 'itemType';
        }

        if (in_array('location', $columns, true)) {
            $relations[] = 'location';
        }

        if (in_array('parent', $columns, true)) {
            $relations[] = 'parent';
        }

        if (in_array('manufacturer', $columns, true)) {
            $relations[] = 'manufacturer';
        }

        if (in_array('model', $columns, true)) {
            $relations[] = 'manufacturerModel';
        }

        return $relations;
    }

    public static function value(Asset $asset, string $column, ?AssetListItem $item = null): string
    {
        return match ($column) {
            'name' => (string) $asset->name,
            'description' => (string) ($asset->description ?? ''),
            'item_type' => (string) ($asset->itemType?->name ?? ''),
            'status' => (string) ($asset->status?->value ?? ''),
            'manufacturer' => (string) ($asset->manufacturer?->name ?? ''),
            'model' => (string) ($asset->manufacturerModel?->name ?? ''),
            'serial_number' => (string) ($asset->serial_number ?? ''),
            'fmi_ast' => (string) ($asset->fmi_ast ?? ''),
            'tp_barcode' => (string) ($asset->tp_barcode ?? ''),
            'rig_tag' => (string) ($asset->rig_tag ?? ''),
            'ip_address' => (string) ($asset->ip_address ?? ''),
            'mac_address' => (string) ($asset->mac_address ?? ''),
            'quantity' => (string) ($item?->quantity ?? $asset->quantity ?? ''),
            'location' => (string) ($asset->location?->name ?? ''),
            'test_tag_expiry' => $asset->test_tag_expiry?->format('Y-m-d') ?? '',
            'parent' => (string) ($asset->parent?->name ?? ''),
            'notes' => (string) ($asset->notes ?? ''),
            default => '',
        };
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    public static function row(Asset $asset, array $columns, ?AssetListItem $item = null): array
    {
        return array_map(
            fn (string $column) => self::value($asset, $column, $item),
            $columns,
        );
    }
}

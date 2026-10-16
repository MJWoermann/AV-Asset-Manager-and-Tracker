<?php

namespace App\Support;

use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetListItem;
use App\Models\CustomFieldDefinition;
use App\Models\Location;
use App\Models\Setting;
use Illuminate\Support\Collection;

class AssetExportColumns
{
    public const ASSETS_KEY = 'export_asset_columns';

    public const LISTS_KEY = 'export_list_columns';

    public const CUSTOM_PREFIX = 'custom.';

    /**
     * Core (non-custom) export columns and labels.
     *
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
            'purchase_date' => 'Purchase Date',
            'warranty_expiry' => 'Warranty Expiry',
            'supplier' => 'Supplier',
            'replacement_cost' => 'Replacement Cost',
            'fmi_ast' => 'FMI AST#',
            'tp_barcode' => 'TP Barcode',
            'rig_tag' => 'RIG Tag #',
            'ip_address' => 'IP Address',
            'mac_address' => 'MAC Address',
            'quantity' => 'Quantity',
            'min_stock_level' => 'Min Stock Level',
            'max_stock_level' => 'Max Stock Level',
            'location' => 'Location',
            'location_site' => 'Location: Site',
            'location_level' => 'Location: Level',
            'location_room' => 'Location: Room',
            'location_rack' => 'Location: Rack',
            'test_tag_expiry' => 'Test & Tag',
            'parent' => 'Parent',
            'parent_fmi_ast' => 'Parent FMI AST#',
            'parent_tp_barcode' => 'Parent TP Barcode',
            'notes' => 'Notes',
            'ru_height' => 'RU Height',
        ];
    }

    /**
     * Defaults for list exports when unset (legacy subset).
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
    public static function allCoreKeys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Core column keys only (admin checklist of built-in fields).
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return self::allCoreKeys();
    }

    /**
     * All selectable keys: core fields plus every custom field definition.
     *
     * @return list<string>
     */
    public static function allowedKeys(?Collection $definitions = null): array
    {
        return array_values(array_unique([
            ...self::allCoreKeys(),
            ...self::customFieldKeys($definitions),
        ]));
    }

    /**
     * Non-layout custom field definitions used as export columns.
     *
     * @return Collection<int, CustomFieldDefinition>
     */
    public static function customFieldDefinitions(): Collection
    {
        return CustomFieldDefinition::query()
            ->with('fieldSet:id,name')
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Custom fields grouped by set for the admin picker.
     *
     * @return list<array{name: string, fields: list<array{key: string, label: string}>}>
     */
    public static function customFieldsBySet(?Collection $definitions = null): array
    {
        $definitions ??= self::customFieldDefinitions();

        return $definitions
            ->groupBy(fn (CustomFieldDefinition $definition): string => (string) ($definition->fieldSet?->name ?: 'Custom fields'))
            ->sortKeys()
            ->map(function (Collection $fields, string $setName): array {
                return [
                    'name' => $setName,
                    'fields' => $fields
                        ->map(fn (CustomFieldDefinition $definition): array => [
                            'key' => self::CUSTOM_PREFIX.$definition->id,
                            'label' => $definition->name,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function customFieldKeys(?Collection $definitions = null): array
    {
        $definitions ??= self::customFieldDefinitions();

        return $definitions
            ->map(fn (CustomFieldDefinition $definition): string => self::CUSTOM_PREFIX.$definition->id)
            ->values()
            ->all();
    }

    /**
     * @param  list<string>|null  $selected
     * @return list<string>
     */
    public static function resolve(?array $selected, ?Collection $definitions = null): array
    {
        $allowed = self::allowedKeys($definitions);
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

    public static function label(string $key, ?Collection $customDefinitions = null): string
    {
        if (str_starts_with($key, self::CUSTOM_PREFIX)) {
            $id = (int) substr($key, strlen(self::CUSTOM_PREFIX));
            $definitions = $customDefinitions ?? self::customFieldDefinitions();
            /** @var CustomFieldDefinition|null $definition */
            $definition = $definitions->firstWhere('id', $id);

            if ($definition === null) {
                return $key;
            }

            $setName = $definition->fieldSet?->name;

            return $setName
                ? $definition->name.' ('.$setName.')'
                : $definition->name;
        }

        return self::definitions()[$key] ?? $key;
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    public static function headings(array $columns, ?Collection $customDefinitions = null): array
    {
        return array_map(
            fn (string $column) => self::label($column, $customDefinitions),
            $columns,
        );
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

        if (self::needsLocation($columns)) {
            $relations[] = 'location.parent.parent.parent';
        }

        if (self::needsParent($columns)) {
            $relations[] = 'parent';
        }

        if (in_array('manufacturer', $columns, true)) {
            $relations[] = 'manufacturer';
        }

        if (in_array('model', $columns, true)) {
            $relations[] = 'manufacturerModel';
        }

        if (self::needsCustomFields($columns)) {
            $relations[] = 'customFieldValues';
        }

        return $relations;
    }

    /**
     * @param  list<string>  $columns
     */
    public static function needsLocation(array $columns): bool
    {
        return collect($columns)->contains(
            fn (string $column): bool => in_array($column, [
                'location',
                'location_site',
                'location_level',
                'location_room',
                'location_rack',
            ], true)
        );
    }

    /**
     * @param  list<string>  $columns
     */
    public static function needsParent(array $columns): bool
    {
        return collect($columns)->contains(
            fn (string $column): bool => in_array($column, [
                'parent',
                'parent_fmi_ast',
                'parent_tp_barcode',
            ], true)
        );
    }

    /**
     * @param  list<string>  $columns
     */
    public static function needsCustomFields(array $columns): bool
    {
        return collect($columns)->contains(
            fn (string $column): bool => str_starts_with($column, self::CUSTOM_PREFIX)
        );
    }

    public static function value(Asset $asset, string $column, ?AssetListItem $item = null): string
    {
        if (str_starts_with($column, self::CUSTOM_PREFIX)) {
            $definitionId = (int) substr($column, strlen(self::CUSTOM_PREFIX));

            if (! $asset->relationLoaded('customFieldValues')) {
                return '';
            }

            $match = $asset->customFieldValues->firstWhere('custom_field_definition_id', $definitionId);

            return (string) ($match?->value ?? '');
        }

        return match ($column) {
            'name' => (string) $asset->name,
            'description' => (string) ($asset->description ?? ''),
            'item_type' => (string) ($asset->itemType?->name ?? ''),
            'status' => (string) ($asset->status?->value ?? ''),
            'manufacturer' => (string) ($asset->manufacturer?->name ?? ''),
            'model' => (string) ($asset->manufacturerModel?->name ?? ''),
            'serial_number' => (string) ($asset->serial_number ?? ''),
            'purchase_date' => $asset->purchase_date?->format('Y-m-d') ?? '',
            'warranty_expiry' => $asset->warranty_expiry?->format('Y-m-d') ?? '',
            'supplier' => (string) ($asset->supplier ?? ''),
            'replacement_cost' => $asset->replacement_cost !== null ? (string) $asset->replacement_cost : '',
            'fmi_ast' => (string) ($asset->fmi_ast ?? ''),
            'tp_barcode' => (string) ($asset->tp_barcode ?? ''),
            'rig_tag' => (string) ($asset->rig_tag ?? ''),
            'ip_address' => (string) ($asset->ip_address ?? ''),
            'mac_address' => (string) ($asset->mac_address ?? ''),
            'quantity' => (string) ($item?->quantity ?? $asset->quantity ?? ''),
            'min_stock_level' => $asset->min_stock_level !== null ? (string) $asset->min_stock_level : '',
            'max_stock_level' => $asset->max_stock_level !== null ? (string) $asset->max_stock_level : '',
            'location' => $asset->location?->breadcrumb() ?? '',
            'location_site' => self::locationNameByType($asset->location, LocationType::Site),
            'location_level' => self::locationNameByType($asset->location, LocationType::Level),
            'location_room' => self::locationNameByType($asset->location, LocationType::Room),
            'location_rack' => self::locationNameByType($asset->location, LocationType::Rack),
            'test_tag_expiry' => $asset->test_tag_expiry?->format('Y-m-d') ?? '',
            'parent' => (string) ($asset->parent?->name ?? ''),
            'parent_fmi_ast' => (string) ($asset->parent?->fmi_ast ?? ''),
            'parent_tp_barcode' => (string) ($asset->parent?->tp_barcode ?? ''),
            'notes' => (string) ($asset->notes ?? ''),
            'ru_height' => $asset->ru_height !== null ? (string) $asset->ru_height : '',
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
            fn (string $column) => CsvFormula::escape(self::value($asset, $column, $item)),
            $columns,
        );
    }

    protected static function locationNameByType(?Location $location, LocationType $type): string
    {
        $current = $location;

        while ($current) {
            if ($current->type === $type) {
                return (string) $current->name;
            }

            $current = $current->relationLoaded('parent')
                ? $current->parent
                : $current->parent()->first();
        }

        return '';
    }
}

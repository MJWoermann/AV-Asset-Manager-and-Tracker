<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\CustomFieldDefinition;
use Illuminate\Support\Collection;

class ShelfTagFieldKeys
{
    /**
     * Core asset field keys available in the designer dropdown.
     *
     * @return array<string, string>
     */
    public static function core(): array
    {
        return [
            'name' => 'Name',
            'fmi_ast' => 'FMI AST#',
            'tp_barcode' => 'TP barcode',
            'rig_tag' => 'Rig tag',
            'serial_number' => 'Serial number',
            'manufacturer' => 'Manufacturer',
            'model' => 'Model',
            'location.name' => 'Location',
            'itemType.name' => 'Item type',
            'status' => 'Status',
            'notes' => 'Notes',
        ];
    }

    /**
     * @return list<string>
     */
    public static function coreKeys(): array
    {
        return array_keys(self::core());
    }

    public static function isCustomKey(string $key): bool
    {
        return str_starts_with($key, 'custom:');
    }

    public static function customSlug(string $key): ?string
    {
        if (! self::isCustomKey($key)) {
            return null;
        }

        $slug = substr($key, strlen('custom:'));

        return $slug !== '' ? $slug : null;
    }

    public static function isValidKey(string $key): bool
    {
        if (in_array($key, self::coreKeys(), true)) {
            return true;
        }

        $slug = self::customSlug($key);

        return $slug !== null && (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Resolve a field key to a printable string for an asset.
     */
    public static function resolve(Asset $asset, string $key): string
    {
        if (self::isCustomKey($key)) {
            $slug = self::customSlug($key);
            if ($slug === null) {
                return '';
            }

            $value = $asset->customFieldValues
                ->first(fn ($row) => $row->definition?->slug === $slug);

            return trim((string) ($value?->value ?? ''));
        }

        return match ($key) {
            'name' => (string) ($asset->name ?? ''),
            'fmi_ast' => (string) ($asset->fmi_ast ?? ''),
            'tp_barcode' => (string) ($asset->tp_barcode ?? ''),
            'rig_tag' => (string) ($asset->rig_tag ?? ''),
            'serial_number' => (string) ($asset->serial_number ?? ''),
            'manufacturer' => (string) ($asset->manufacturer?->name ?? ''),
            'model' => (string) ($asset->manufacturerModel?->name ?? ''),
            'location.name' => (string) ($asset->location?->name ?? ''),
            'itemType.name' => (string) ($asset->itemType?->name ?? ''),
            'status' => $asset->status instanceof \BackedEnum
                ? (method_exists($asset->status, 'label') ? $asset->status->label() : (string) $asset->status->value)
                : (string) ($asset->status ?? ''),
            'notes' => (string) ($asset->notes ?? ''),
            default => '',
        };
    }

    /**
     * Options for the designer: core fields plus known custom field definitions.
     *
     * @return array<string, string>
     */
    public static function optionsForDesigner(): array
    {
        $options = self::core();

        /** @var Collection<int, CustomFieldDefinition> $definitions */
        $definitions = CustomFieldDefinition::query()
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->orderBy('name')
            ->get(['name', 'slug']);

        foreach ($definitions as $definition) {
            $options['custom:'.$definition->slug] = 'Custom: '.$definition->name;
        }

        return $options;
    }
}

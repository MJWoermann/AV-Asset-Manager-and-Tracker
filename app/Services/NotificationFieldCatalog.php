<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\CustomFieldDefinition;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationFieldCatalog
{
    /**
     * @return array{core: list<array{key: string, label: string}>, custom: list<array{key: string, label: string}>}
     */
    public function catalog(): array
    {
        return [
            'core' => $this->coreFields(),
            'custom' => $this->customFields(),
        ];
    }

    /**
     * @return list<string>
     */
    public function allKeys(): array
    {
        return collect($this->coreFields())
            ->merge($this->customFields())
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function coreFields(): array
    {
        return [
            ['key' => 'asset.name', 'label' => 'Asset name'],
            ['key' => 'asset.mac_address', 'label' => 'MAC address'],
            ['key' => 'asset.ip_address', 'label' => 'IP address'],
            ['key' => 'asset.fmi_ast', 'label' => 'FMI AST#'],
            ['key' => 'asset.tp_barcode', 'label' => 'TP barcode'],
            ['key' => 'asset.rig_tag', 'label' => 'RIG tag'],
            ['key' => 'asset.serial_number', 'label' => 'Serial number'],
            ['key' => 'asset.status', 'label' => 'Status'],
            ['key' => 'asset.location', 'label' => 'Location'],
            ['key' => 'asset.item_type', 'label' => 'Item type'],
            ['key' => 'asset.url', 'label' => 'Asset URL'],
            ['key' => 'asset.warranty_expiry', 'label' => 'Warranty expiry'],
            ['key' => 'asset.test_tag_expiry', 'label' => 'Test & Tag expiry'],
            ['key' => 'asset.quantity', 'label' => 'Quantity on hand'],
            ['key' => 'asset.min_stock_level', 'label' => 'Min stock level'],
            ['key' => 'user.name', 'label' => 'Acting user'],
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function customFields(): array
    {
        return CustomFieldDefinition::query()
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->unique('slug')
            ->map(fn (CustomFieldDefinition $definition): array => [
                'key' => 'custom.'.$definition->slug,
                'label' => $definition->name,
            ])
            ->values()
            ->all();
    }

    public function looksLikeMac(string $key, ?string $label = null): bool
    {
        $haystack = strtolower($key.' '.($label ?? ''));

        return str_contains($haystack, 'mac');
    }

    /**
     * @param  list<string>|null  $selectedFields
     * @return array<string, string>
     */
    public function resolve(Asset $asset, ?User $user = null, ?array $selectedFields = null): array
    {
        $asset->loadMissing(['itemType', 'location', 'customFieldValues.definition']);

        $all = [
            'asset.name' => (string) ($asset->name ?? ''),
            'asset.mac_address' => (string) ($asset->mac_address ?? ''),
            'asset.ip_address' => (string) ($asset->ip_address ?? ''),
            'asset.fmi_ast' => (string) ($asset->fmi_ast ?? ''),
            'asset.tp_barcode' => (string) ($asset->tp_barcode ?? ''),
            'asset.rig_tag' => (string) ($asset->rig_tag ?? ''),
            'asset.serial_number' => (string) ($asset->serial_number ?? ''),
            'asset.status' => (string) ($asset->status?->value ?? $asset->status ?? ''),
            'asset.location' => (string) ($asset->location?->name ?? ''),
            'asset.item_type' => (string) ($asset->itemType?->name ?? ''),
            'asset.url' => route('assets.show', $asset),
            'asset.warranty_expiry' => $asset->warranty_expiry?->format('Y-m-d') ?? '',
            'asset.test_tag_expiry' => $asset->test_tag_expiry?->format('Y-m-d') ?? '',
            'asset.quantity' => $asset->quantity === null ? '' : (string) $asset->quantity,
            'asset.min_stock_level' => $asset->min_stock_level === null ? '' : (string) $asset->min_stock_level,
            'user.name' => (string) ($user?->name ?? ''),
        ];

        foreach ($asset->customFieldValues as $value) {
            $slug = $value->definition?->slug;
            if (! filled($slug)) {
                continue;
            }
            $all['custom.'.$slug] = (string) ($value->value ?? '');
        }

        foreach ($this->customFields() as $field) {
            $all[$field['key']] ??= '';
        }

        if ($selectedFields === null) {
            return $all;
        }

        return collect($selectedFields)
            ->mapWithKeys(fn (string $key): array => [$key => (string) ($all[$key] ?? '')])
            ->all();
    }

    /**
     * @param  array<string, string>  $values
     */
    public function render(string $template, array $values): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z0-9_.]+)\s*\}\}/i',
            function (array $matches) use ($values): string {
                $key = $matches[1];
                $value = $values[$key] ?? '';

                return $value === '' ? '—' : $value;
            },
            $template
        );
    }

    /**
     * @param  list<string>  $requiredFields
     * @param  array<string, string>  $values
     */
    public function missingRequiredFields(array $requiredFields, array $values): array
    {
        return collect($requiredFields)
            ->filter(fn (string $key): bool => trim((string) ($values[$key] ?? '')) === '')
            ->values()
            ->all();
    }

    /**
     * Extract placeholder keys referenced in subject/body.
     *
     * @return list<string>
     */
    public function placeholdersIn(string ...$parts): array
    {
        $found = [];
        foreach ($parts as $part) {
            if (preg_match_all('/\{\{\s*([a-z0-9_.]+)\s*\}\}/i', $part, $matches)) {
                foreach ($matches[1] as $key) {
                    $found[] = $key;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * @return Collection<int, array{id: int, slug: string, name: string, item_type_ids: list<int>}>
     */
    public function macCustomFieldMetaForTypes(): Collection
    {
        return CustomFieldDefinition::query()
            ->with(['fieldSet.itemTypes:id'])
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->get()
            ->filter(fn (CustomFieldDefinition $definition): bool => $this->looksLikeMac(
                'custom.'.$definition->slug,
                $definition->name
            ))
            ->map(fn (CustomFieldDefinition $definition): array => [
                'id' => $definition->id,
                'slug' => $definition->slug,
                'name' => $definition->name,
                'item_type_ids' => $definition->fieldSet?->itemTypes
                    ? $definition->fieldSet->itemTypes->pluck('id')->map(fn ($id) => (int) $id)->all()
                    : [],
            ])
            ->values();
    }
}

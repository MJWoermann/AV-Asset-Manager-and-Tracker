<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportService
{
    public const CREATE_NEW_FIELD = 'create_new_field';

    /** @var list<string> */
    public const DUPLICATE_KEYS = [
        'serial_number',
        'fmi_ast',
        'mac_address',
        'rig_tag',
        'tp_barcode',
    ];

    /** @var list<string> */
    public const META_FIELDS = [
        'item_type',
        'location_level',
        'location_room',
        'location_rack',
    ];

    /** @var array<string, string> */
    public const ASSET_FIELDS = [
        'name' => 'Name',
        'item_type' => 'Item Type',
        'location_level' => 'Location: Level',
        'location_room' => 'Location: Room',
        'location_rack' => 'Location: Rack',
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
        'device_sn' => 'Device SN',
        'ip_address' => 'IP Address',
        'mac_address' => 'MAC Address',
        'test_tag_expiry' => 'Test & Tag Expiry',
        'quantity' => 'Quantity',
        'notes' => 'Notes',
    ];

    /**
     * Ensure each target field is mapped from at most one source column.
     * create_new_field may be selected multiple times (once per source column).
     *
     * @param  array<string, string|null>  $mapping
     * @return array<string, string>
     */
    public function validateMapping(array $mapping): array
    {
        $cleaned = [];
        $usedTargets = [];

        foreach ($mapping as $column => $field) {
            if ($field === null || $field === '') {
                continue;
            }

            if ($field !== self::CREATE_NEW_FIELD && ! array_key_exists($field, self::ASSET_FIELDS)) {
                throw ValidationException::withMessages([
                    "mapping.{$column}" => "Unknown target field [{$field}].",
                ]);
            }

            if ($field !== self::CREATE_NEW_FIELD) {
                if (isset($usedTargets[$field])) {
                    throw ValidationException::withMessages([
                        "mapping.{$column}" => self::ASSET_FIELDS[$field].' is already mapped from column "'.$usedTargets[$field].'". Each field can only be used once.',
                    ]);
                }
                $usedTargets[$field] = (string) $column;
            }

            $cleaned[(string) $column] = $field;
        }

        if (! in_array('name', $cleaned, true)) {
            throw ValidationException::withMessages([
                'mapping' => 'Map a column to Name before importing.',
            ]);
        }

        if (! in_array('item_type', $cleaned, true)) {
            throw ValidationException::withMessages([
                'mapping' => 'Map a column to Item Type before importing.',
            ]);
        }

        return $cleaned;
    }

    /**
     * @param  Collection<int, Collection<string, mixed>|array<string, mixed>>  $rows
     * @param  array<string, string>  $mapping
     * @return list<array{
     *     index: int,
     *     payload: array<string, mixed>,
     *     custom_fields: array<string, string>,
     *     item_type_name: string,
     *     location_level: ?string,
     *     location_room: ?string,
     *     location_rack: ?string,
     *     existing_id: ?int,
     *     matched_on: list<string>,
     *     existing_summary: ?array<string, mixed>
     * }>
     */
    public function prepareRows(Collection $rows, array $mapping): array
    {
        $prepared = [];

        foreach ($rows as $index => $row) {
            $row = Collection::make($row);
            $payload = [];
            $customFields = [];
            $itemTypeName = null;
            $level = null;
            $room = null;
            $rack = null;

            foreach ($mapping as $column => $field) {
                $value = $this->cellValue($row, (string) $column);
                if ($value === null || $value === '') {
                    continue;
                }

                if ($field === self::CREATE_NEW_FIELD) {
                    $customFields[(string) $column] = (string) $value;

                    continue;
                }

                if ($field === 'item_type') {
                    $itemTypeName = (string) $value;

                    continue;
                }

                if ($field === 'location_level') {
                    $level = (string) $value;

                    continue;
                }

                if ($field === 'location_room') {
                    $room = (string) $value;

                    continue;
                }

                if ($field === 'location_rack') {
                    $rack = (string) $value;

                    continue;
                }

                if ($field === 'status') {
                    $slug = Str::slug((string) $value, '_');
                    $value = AssetStatus::tryFrom($slug)?->value ?? AssetStatus::Available->value;
                }

                $payload[$field] = $value;
            }

            if (empty($payload['name']) || ! $itemTypeName) {
                continue;
            }

            $match = $this->findExistingMatch($payload);

            $prepared[] = [
                'index' => (int) $index,
                'payload' => $payload,
                'custom_fields' => $customFields,
                'item_type_name' => $itemTypeName,
                'location_level' => $level,
                'location_room' => $room,
                'location_rack' => $rack,
                'existing_id' => $match['asset']?->id,
                'matched_on' => $match['matched_on'],
                'existing_summary' => $match['asset'] ? [
                    'id' => $match['asset']->id,
                    'name' => $match['asset']->name,
                    'serial_number' => $match['asset']->serial_number,
                    'fmi_ast' => $match['asset']->fmi_ast,
                    'mac_address' => $match['asset']->mac_address,
                    'rig_tag' => $match['asset']->rig_tag,
                    'tp_barcode' => $match['asset']->tp_barcode,
                ] : null,
            ];
        }

        return $prepared;
    }

    /**
     * @param  list<array<string, mixed>>  $prepared
     * @param  array<int|string, string>  $actions  row index => update|replace|skip
     * @return array{created: int, updated: int, replaced: int, skipped: int}
     */
    public function commit(array $prepared, array $actions = [], bool $dryRun = false, string $defaultDuplicateAction = 'update'): array
    {
        $counts = ['created' => 0, 'updated' => 0, 'replaced' => 0, 'skipped' => 0];

        $runner = function () use ($prepared, $actions, $dryRun, $defaultDuplicateAction, &$counts): void {
            foreach ($prepared as $row) {
                $existingId = $row['existing_id'] ?? null;

                if ($existingId) {
                    $action = $actions[$row['index']] ?? $defaultDuplicateAction;
                    if (! in_array($action, ['update', 'replace', 'skip'], true)) {
                        $action = $defaultDuplicateAction;
                    }

                    if ($action === 'skip') {
                        $counts['skipped']++;

                        continue;
                    }

                    if ($dryRun) {
                        $counts[$action === 'replace' ? 'replaced' : 'updated']++;

                        continue;
                    }

                    $asset = Asset::query()->find($existingId);
                    if (! $asset) {
                        $counts['skipped']++;

                        continue;
                    }

                    $this->applyRow($asset, $row, $action === 'replace');
                    $counts[$action === 'replace' ? 'replaced' : 'updated']++;

                    continue;
                }

                if ($dryRun) {
                    $counts['created']++;

                    continue;
                }

                $this->applyRow(null, $row, false);
                $counts['created']++;
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function applyRow(?Asset $existing, array $row, bool $replace): Asset
    {
        $itemType = $this->resolveItemType($row['item_type_name']);
        $locationId = $this->resolveLocation(
            $row['location_level'] ?? null,
            $row['location_room'] ?? null,
            $row['location_rack'] ?? null,
        );

        $payload = $row['payload'];
        $payload['item_type_id'] = $itemType->id;
        if ($locationId !== null) {
            $payload['location_id'] = $locationId;
        }
        $payload['status'] = $payload['status'] ?? AssetStatus::Available->value;

        if ($existing && $replace) {
            $existing = $this->replaceAsset($existing, $payload);
        } elseif ($existing) {
            $existing->update($payload);
        } else {
            $existing = Asset::create($payload);
        }

        $this->syncImportedCustomFields($existing, $itemType, $row['custom_fields'] ?? [], $replace);

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function replaceAsset(Asset $asset, array $payload): Asset
    {
        $blank = [
            'location_id' => null,
            'parent_id' => null,
            'manufacturer' => null,
            'model' => null,
            'serial_number' => null,
            'purchase_date' => null,
            'warranty_expiry' => null,
            'supplier' => null,
            'replacement_cost' => null,
            'fmi_ast' => null,
            'tp_barcode' => null,
            'rig_tag' => null,
            'device_sn' => null,
            'ip_address' => null,
            'mac_address' => null,
            'test_tag_expiry' => null,
            'quantity' => 1,
            'notes' => null,
            'status' => AssetStatus::Available->value,
        ];

        $asset->update(array_merge($blank, $payload));

        return $asset->fresh();
    }

    /**
     * @param  array<string, string>  $customFields  source column => value
     */
    protected function syncImportedCustomFields(Asset $asset, ItemType $itemType, array $customFields, bool $replace): void
    {
        if ($customFields === []) {
            return;
        }

        $set = $this->importedFieldSet();
        if (! $itemType->fieldSets()->where('custom_field_sets.id', $set->id)->exists()) {
            $itemType->fieldSets()->attach($set->id);
        }

        foreach ($customFields as $column => $value) {
            $definition = $this->resolveImportedFieldDefinition($set, (string) $column);
            AssetCustomFieldValue::query()->updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'custom_field_definition_id' => $definition->id,
                ],
                ['value' => $value]
            );
        }

        if ($replace) {
            // Imported-Field values not present in this row are left intact;
            // replace clears core columns only.
        }
    }

    protected function importedFieldSet(): CustomFieldSet
    {
        return CustomFieldSet::query()->firstOrCreate(
            ['slug' => 'imported-fields'],
            [
                'name' => 'Imported Fields',
                'description' => 'Fields created automatically during spreadsheet import.',
            ]
        );
    }

    protected function resolveImportedFieldDefinition(CustomFieldSet $set, string $column): CustomFieldDefinition
    {
        $name = 'Imported-Field:'.$column;
        $slug = Str::slug($name, '_');

        return CustomFieldDefinition::query()->firstOrCreate(
            [
                'custom_field_set_id' => $set->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'type' => 'text',
                'sort_order' => (int) $set->definitions()->count(),
            ]
        );
    }

    public function resolveItemType(string $name): ItemType
    {
        $name = trim($name);
        $slug = Str::slug($name);

        $existing = ItemType::query()
            ->where(function ($query) use ($name, $slug) {
                $query->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($name)])
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        return ItemType::create([
            'name' => $name,
            'slug' => $slug !== '' ? $slug : Str::slug($name.'-'.Str::random(4)),
        ]);
    }

    public function resolveLocation(?string $level, ?string $room, ?string $rack): ?int
    {
        $level = $this->nullableTrim($level);
        $room = $this->nullableTrim($room);
        $rack = $this->nullableTrim($rack);

        if (! $level && ! $room && ! $rack) {
            return null;
        }

        $parentId = null;
        $resolvedId = null;

        if ($level) {
            $location = $this->firstOrCreateLocation($level, LocationType::Level, $parentId);
            $parentId = $location->id;
            $resolvedId = $location->id;
        }

        if ($room) {
            $location = $this->firstOrCreateLocation($room, LocationType::Room, $parentId);
            $parentId = $location->id;
            $resolvedId = $location->id;
        }

        if ($rack) {
            $location = $this->firstOrCreateLocation($rack, LocationType::Rack, $parentId);
            $resolvedId = $location->id;
        }

        return $resolvedId;
    }

    protected function firstOrCreateLocation(string $name, LocationType $type, ?int $parentId): Location
    {
        $query = Location::query()
            ->where('name', $name)
            ->where('type', $type->value);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        $existing = $query->first();
        if ($existing) {
            return $existing;
        }

        return Location::create([
            'name' => $name,
            'type' => $type,
            'parent_id' => $parentId,
            'is_portable' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{asset: ?Asset, matched_on: list<string>}
     */
    protected function findExistingMatch(array $payload): array
    {
        $matchedOn = [];
        $asset = null;

        foreach (self::DUPLICATE_KEYS as $key) {
            $value = $payload[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $found = Asset::query()->where($key, $value)->first();
            if ($found) {
                if ($asset && $asset->id !== $found->id) {
                    // Prefer the first match; still record the key that hit.
                    $matchedOn[] = $key;

                    continue;
                }
                $asset = $found;
                $matchedOn[] = $key;
            }
        }

        return ['asset' => $asset, 'matched_on' => $matchedOn];
    }

    /**
     * @param  Collection<string, mixed>  $row
     */
    protected function cellValue(Collection $row, string $column): mixed
    {
        $key = Str::slug($column, '_');

        return $row->get($key) ?? $row->get($column) ?? $row->get(Str::lower($column));
    }

    protected function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

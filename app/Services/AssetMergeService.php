<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetInterface;
use App\Models\AssetListItem;
use App\Models\AssetMaintenanceLog;
use App\Models\AssetRuPlacement;
use App\Models\AssetStockToken;
use App\Models\AuditLog;
use App\Models\CustomFieldDefinition;
use App\Models\FileLink;
use App\Models\InboundEmailAction;
use App\Models\OutboundEmailThread;
use App\Models\Repair;
use App\Models\SpareGroup;
use App\Models\SparePartMovement;
use App\Models\StockMovement;
use App\Models\UnknownScan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetMergeService
{
    public const MAX_ASSETS = 6;

    public const EMPTY_SOURCE = '__empty__';

    /**
     * Fields that must keep a value (database NOT NULL / required identity).
     *
     * @var list<string>
     */
    public const REQUIRED_FIELDS = [
        'name',
        'status',
        'item_type_id',
        'quantity',
    ];

    /**
     * Mergeable scalar / relation id attributes on assets.
     *
     * @var list<string>
     */
    public const MERGEABLE_FIELDS = [
        'name',
        'description',
        'status',
        'item_type_id',
        'location_id',
        'parent_id',
        'manufacturer_id',
        'manufacturer_model_id',
        'serial_number',
        'fmi_ast',
        'tp_barcode',
        'rig_tag',
        'ip_address',
        'mac_address',
        'purchase_date',
        'warranty_expiry',
        'test_tag_expiry',
        'supplier',
        'replacement_cost',
        'quantity',
        'min_stock_level',
        'max_stock_level',
        'notes',
        'ru_height',
        'ru_rear_enabled',
    ];

    /**
     * @var array<string, string>
     */
    public const FIELD_LABELS = [
        'name' => 'Name',
        'description' => 'Description',
        'status' => 'Status',
        'item_type_id' => 'Item type',
        'location_id' => 'Location',
        'parent_id' => 'Parent asset',
        'manufacturer_id' => 'Manufacturer',
        'manufacturer_model_id' => 'Model',
        'serial_number' => 'Serial number',
        'fmi_ast' => 'FMI / AST',
        'tp_barcode' => 'TP barcode',
        'rig_tag' => 'Rig tag',
        'ip_address' => 'IP address',
        'mac_address' => 'MAC address',
        'purchase_date' => 'Purchase date',
        'warranty_expiry' => 'Warranty expiry',
        'test_tag_expiry' => 'Test & Tag expiry',
        'supplier' => 'Supplier',
        'replacement_cost' => 'Replacement cost',
        'quantity' => 'Quantity',
        'min_stock_level' => 'Min stock',
        'max_stock_level' => 'Max stock',
        'notes' => 'Notes',
        'ru_height' => 'RU height',
        'ru_rear_enabled' => 'RU rear enabled',
    ];

    /**
     * Merge with explicit per-field source asset ids (or EMPTY_SOURCE to clear).
     *
     * @param  list<int>  $assetIds
     * @param  array<string, int|string|null>  $fieldSources  field => asset id | EMPTY_SOURCE
     * @param  array<int, int|string|null>  $customFieldSources  definition id => asset id | EMPTY_SOURCE
     * @return array{survivor: Asset, deleted: int, updated_fields: list<string>}
     */
    public function mergeWithFieldSelection(
        array $assetIds,
        int $survivorId,
        array $fieldSources,
        array $customFieldSources,
        int $userId,
    ): array {
        return DB::transaction(function () use ($assetIds, $survivorId, $fieldSources, $customFieldSources, $userId) {
            $ids = collect($assetIds)->map(fn ($id) => (int) $id)->unique()->values();

            $this->assertMergeSelection($ids, $survivorId);

            $assets = Asset::query()
                ->with(['customFieldValues', 'itemType', 'location', 'manufacturer', 'manufacturerModel', 'parent'])
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            if ($assets->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'One or more selected assets are not available in your department.',
                ]);
            }

            $this->assertSameDepartment($assets);
            $this->assertOpenCheckouts($assets, $survivorId);

            /** @var Asset $survivor */
            $survivor = $assets->get($survivorId);
            $before = $this->snapshotAsset($survivor);

            $duplicateIds = $ids->filter(fn (int $id) => $id !== $survivorId)->values();
            $duplicates = $duplicateIds
                ->map(fn (int $id) => $assets->get($id))
                ->filter()
                ->values();

            $this->resolveSurvivorParent($survivor, $duplicates, $duplicateIds);

            $appliedFields = $this->applyFieldSources($survivor, $assets, $fieldSources);
            $appliedCustom = $this->applyCustomFieldSources($survivor, $assets, $customFieldSources);

            if ($appliedFields !== [] || $survivor->isDirty()) {
                $survivor->save();
            }

            Asset::query()
                ->whereIn('parent_id', $duplicateIds)
                ->update(['parent_id' => $survivorId]);

            foreach ($duplicates as $duplicate) {
                $this->reassignRelatedRecords($survivorId, (int) $duplicate->id);
            }

            $after = $this->snapshotAsset($survivor->fresh(['customFieldValues']));

            foreach ($duplicates as $duplicate) {
                $this->logMergedAsset(
                    $survivor,
                    $duplicate,
                    $userId,
                    $before,
                    $after,
                    $fieldSources,
                    $customFieldSources,
                    $appliedFields,
                    $appliedCustom,
                );
                $duplicate->delete();
            }

            return [
                'survivor' => $survivor->fresh(),
                'deleted' => $duplicates->count(),
                'updated_fields' => array_values(array_unique(array_merge($appliedFields, $appliedCustom))),
            ];
        });
    }

    /**
     * Legacy / bulk behaviour: keep survivor values; fill blanks from other assets; append notes.
     *
     * @param  Collection<int, int>  $assetIds
     * @return array{merged: int, deleted: int, updated: int}
     */
    public function mergeFillingBlanks(Collection $assetIds, int $survivorId, int $userId): array
    {
        return DB::transaction(function () use ($assetIds, $survivorId, $userId) {
            $ids = $assetIds->map(fn ($id) => (int) $id)->unique()->values();

            $this->assertMergeSelection($ids, $survivorId, maxAssets: null);

            $assets = Asset::query()->whereIn('id', $ids)->get()->keyBy('id');

            if ($assets->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'One or more selected assets are not available in your department.',
                ]);
            }

            $this->assertSameDepartment($assets);
            $this->assertOpenCheckouts($assets, $survivorId);

            /** @var Asset $survivor */
            $survivor = $assets->get($survivorId);
            $duplicateIds = $ids->filter(fn (int $id) => $id !== $survivorId)->values();
            $duplicates = $duplicateIds
                ->map(fn (int $id) => $assets->get($id))
                ->filter()
                ->values();

            $this->resolveSurvivorParent($survivor, $duplicates, $duplicateIds);
            $this->fillSurvivorParentFromDuplicates($survivor, $duplicates, $duplicateIds);

            Asset::query()
                ->whereIn('parent_id', $duplicateIds)
                ->update(['parent_id' => $survivorId]);

            foreach ($duplicates as $duplicate) {
                $this->reassignRelatedRecords($survivorId, (int) $duplicate->id);
            }

            $scalarChanges = $this->blankFillScalarFields($survivor, $duplicates);

            if ($scalarChanges !== []) {
                $survivor->update($scalarChanges);
            }

            $deleted = 0;

            foreach ($duplicates as $duplicate) {
                $this->logMergedAssetSimple($survivor, $duplicate, $userId);
                $duplicate->delete();
                $deleted++;
            }

            return [
                'merged' => 1,
                'deleted' => $deleted,
                'updated' => $scalarChanges !== [] ? 1 : 0,
            ];
        });
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return list<array{key: string, label: string, type: string, definition_id?: int}>
     */
    public function mergeableFieldRows(Collection $assets): array
    {
        $rows = [];

        foreach (self::MERGEABLE_FIELDS as $field) {
            $rows[] = [
                'key' => $field,
                'label' => self::FIELD_LABELS[$field] ?? $field,
                'type' => 'attribute',
            ];
        }

        $definitionIds = AssetCustomFieldValue::query()
            ->whereIn('asset_id', $assets->pluck('id'))
            ->pluck('custom_field_definition_id')
            ->unique()
            ->values();

        if ($definitionIds->isEmpty()) {
            return $rows;
        }

        $definitions = CustomFieldDefinition::query()
            ->whereIn('id', $definitionIds)
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->orderBy('name')
            ->get();

        foreach ($definitions as $definition) {
            $rows[] = [
                'key' => 'custom:'.$definition->id,
                'label' => $definition->name.' (custom)',
                'type' => 'custom',
                'definition_id' => (int) $definition->id,
            ];
        }

        return $rows;
    }

    /**
     * Default field sources: prefer survivor value when present, else first non-blank from others.
     *
     * @param  Collection<int, Asset>  $assets
     * @return array{fields: array<string, string>, custom_fields: array<string, string>}
     */
    public function defaultFieldSources(Collection $assets, int $survivorId): array
    {
        $byId = $assets->keyBy(fn (Asset $asset): int => (int) $asset->id);
        $survivor = $byId->get($survivorId);
        $others = $assets->filter(fn (Asset $asset) => (int) $asset->id !== $survivorId)->values();

        $fields = [];
        foreach (self::MERGEABLE_FIELDS as $field) {
            if ($survivor && ! $this->isBlank($survivor->getAttribute($field))) {
                $fields[$field] = (string) $survivorId;

                continue;
            }

            $chosen = null;
            foreach ($others as $other) {
                if (! $this->isBlank($other->getAttribute($field))) {
                    $chosen = (string) $other->id;
                    break;
                }
            }

            $fields[$field] = $chosen
                ?? (in_array($field, self::REQUIRED_FIELDS, true) && $survivor
                    ? (string) $survivorId
                    : self::EMPTY_SOURCE);
        }

        $custom = [];
        $definitionIds = AssetCustomFieldValue::query()
            ->whereIn('asset_id', $assets->pluck('id'))
            ->pluck('custom_field_definition_id')
            ->unique();

        $valuesByAsset = [];
        foreach ($assets as $asset) {
            foreach ($asset->customFieldValues as $value) {
                $valuesByAsset[(int) $asset->id][(int) $value->custom_field_definition_id] = $value->value;
            }
        }

        foreach ($definitionIds as $definitionId) {
            $definitionId = (int) $definitionId;
            $survivorValue = $valuesByAsset[$survivorId][$definitionId] ?? null;
            if (! $this->isBlank($survivorValue)) {
                $custom[(string) $definitionId] = (string) $survivorId;

                continue;
            }

            $chosen = null;
            foreach ($others as $other) {
                $value = $valuesByAsset[(int) $other->id][$definitionId] ?? null;
                if (! $this->isBlank($value)) {
                    $chosen = (string) $other->id;
                    break;
                }
            }

            $custom[(string) $definitionId] = $chosen ?? self::EMPTY_SOURCE;
        }

        return ['fields' => $fields, 'custom_fields' => $custom];
    }

    public function formatAttributeForDisplay(Asset $asset, string $field): string
    {
        $value = $asset->getAttribute($field);

        if ($this->isBlank($value)) {
            return '';
        }

        return match ($field) {
            'status' => is_object($value) && method_exists($value, 'label')
                ? (string) $value->label()
                : (string) (is_object($value) && property_exists($value, 'value') ? $value->value : $value),
            'item_type_id' => (string) ($asset->itemType?->name ?? '#'.$value),
            'location_id' => (string) ($asset->location?->name ?? '#'.$value),
            'parent_id' => (string) ($asset->parent?->name ?? '#'.$value),
            'manufacturer_id' => (string) ($asset->manufacturer?->name ?? '#'.$value),
            'manufacturer_model_id' => (string) ($asset->manufacturerModel?->name ?? '#'.$value),
            'ru_rear_enabled' => $value ? 'Yes' : 'No',
            'purchase_date', 'warranty_expiry', 'test_tag_expiry' => $asset->{$field}?->format('Y-m-d') ?? (string) $value,
            default => is_scalar($value) || $value instanceof \Stringable
                ? (string) $value
                : (string) json_encode($value),
        };
    }

    /**
     * @param  Collection<int, int>  $ids
     */
    protected function assertMergeSelection(Collection $ids, int $survivorId, ?int $maxAssets = self::MAX_ASSETS): void
    {
        if ($ids->count() < 2) {
            throw ValidationException::withMessages([
                'asset_ids' => 'Select at least two assets to merge.',
            ]);
        }

        if ($maxAssets !== null && $ids->count() > $maxAssets) {
            throw ValidationException::withMessages([
                'asset_ids' => 'You can merge at most '.$maxAssets.' assets at a time.',
            ]);
        }

        if (! $ids->contains($survivorId)) {
            throw ValidationException::withMessages([
                'survivor_id' => 'The survivor asset must be one of the selected assets.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Asset>  $assets
     */
    protected function assertSameDepartment(Collection $assets): void
    {
        $departmentIds = $assets
            ->map(fn (Asset $asset) => $asset->department_id !== null ? (int) $asset->department_id : null)
            ->unique()
            ->values();

        if ($departmentIds->count() > 1) {
            throw ValidationException::withMessages([
                'asset_ids' => 'All assets in a merge must belong to the same department.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Asset>  $assets
     */
    protected function assertOpenCheckouts(Collection $assets, int $survivorId): void
    {
        $survivor = $assets->get($survivorId);
        $survivorHasOpen = $survivor?->openCheckout() !== null;

        if (! $survivorHasOpen) {
            return;
        }

        foreach ($assets as $asset) {
            if ((int) $asset->id === $survivorId) {
                continue;
            }

            if ($asset->openCheckout() !== null) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'Cannot merge while both the survivor and another selected asset have an open checkout.',
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Asset>  $duplicates
     * @param  Collection<int, int>  $duplicateIds
     */
    protected function resolveSurvivorParent(Asset $survivor, Collection $duplicates, Collection $duplicateIds): void
    {
        if (! $survivor->parent_id || ! $duplicateIds->contains((int) $survivor->parent_id)) {
            return;
        }

        $removedParent = $duplicates->firstWhere('id', (int) $survivor->parent_id);
        $candidate = $removedParent?->parent_id;

        if ($candidate !== null && ! $duplicateIds->contains((int) $candidate)) {
            $survivor->parent_id = $candidate;
        } else {
            $survivor->parent_id = null;
        }

        $survivor->save();
    }

    /**
     * @param  Collection<int, Asset>  $duplicates
     * @param  Collection<int, int>  $duplicateIds
     */
    protected function fillSurvivorParentFromDuplicates(Asset $survivor, Collection $duplicates, Collection $duplicateIds): void
    {
        if ($survivor->parent_id) {
            return;
        }

        foreach ($duplicates as $duplicate) {
            $parentId = $duplicate->parent_id;

            if (
                $parentId
                && (int) $parentId !== (int) $survivor->id
                && ! $duplicateIds->contains((int) $parentId)
            ) {
                $survivor->parent_id = $parentId;
                $survivor->save();
                break;
            }
        }
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @param  array<string, int|string|null>  $fieldSources
     * @return list<string>
     */
    protected function applyFieldSources(Asset $survivor, Collection $assets, array $fieldSources): array
    {
        $applied = [];

        foreach (self::MERGEABLE_FIELDS as $field) {
            if (! array_key_exists($field, $fieldSources)) {
                continue;
            }

            $source = $fieldSources[$field];

            if ($source === null || $source === '' || $source === self::EMPTY_SOURCE) {
                if (in_array($field, self::REQUIRED_FIELDS, true)) {
                    throw ValidationException::withMessages([
                        "fields.{$field}" => 'This field cannot be cleared during a merge.',
                    ]);
                }

                $clearValue = match ($field) {
                    'ru_rear_enabled' => false,
                    default => null,
                };

                if (! $this->valuesEqual($survivor->getAttribute($field), $clearValue)) {
                    $survivor->setAttribute($field, $clearValue);
                    $applied[] = $field;
                }

                continue;
            }

            $sourceId = (int) $source;
            $sourceAsset = $assets->get($sourceId);

            if ($sourceAsset === null) {
                throw ValidationException::withMessages([
                    "fields.{$field}" => 'Selected source asset is not part of this merge.',
                ]);
            }

            if ($field === 'parent_id') {
                $parentId = $sourceAsset->parent_id;
                if ($parentId !== null && $assets->has((int) $parentId)) {
                    // Parent is being merged away; leave null unless already resolved.
                    $parentId = $survivor->parent_id;
                }
                if ((int) ($parentId ?? 0) === (int) $survivor->id) {
                    $parentId = null;
                }
                $value = $parentId;
            } else {
                $value = $sourceAsset->getAttribute($field);
            }

            $current = $survivor->getAttribute($field);
            if ($this->valuesEqual($current, $value)) {
                continue;
            }

            $survivor->setAttribute($field, $value);
            $applied[] = $field;
        }

        return $applied;
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @param  array<int, int|string|null>  $customFieldSources
     * @return list<string>
     */
    protected function applyCustomFieldSources(Asset $survivor, Collection $assets, array $customFieldSources): array
    {
        $applied = [];
        $survivor->loadMissing('customFieldValues');

        $valuesByAsset = [];
        foreach ($assets as $asset) {
            $asset->loadMissing('customFieldValues');
            foreach ($asset->customFieldValues as $value) {
                $valuesByAsset[(int) $asset->id][(int) $value->custom_field_definition_id] = $value->value;
            }
        }

        foreach ($customFieldSources as $definitionId => $source) {
            $definitionId = (int) $definitionId;
            $key = 'custom:'.$definitionId;

            if ($source === null || $source === '' || $source === self::EMPTY_SOURCE) {
                $deleted = AssetCustomFieldValue::query()
                    ->where('asset_id', $survivor->id)
                    ->where('custom_field_definition_id', $definitionId)
                    ->delete();

                if ($deleted > 0) {
                    $applied[] = $key;
                }

                continue;
            }

            $sourceId = (int) $source;
            if (! $assets->has($sourceId)) {
                throw ValidationException::withMessages([
                    "custom_fields.{$definitionId}" => 'Selected source asset is not part of this merge.',
                ]);
            }

            $value = $valuesByAsset[$sourceId][$definitionId] ?? null;

            if ($this->isBlank($value)) {
                $deleted = AssetCustomFieldValue::query()
                    ->where('asset_id', $survivor->id)
                    ->where('custom_field_definition_id', $definitionId)
                    ->delete();

                if ($deleted > 0) {
                    $applied[] = $key;
                }

                continue;
            }

            $existing = AssetCustomFieldValue::query()
                ->where('asset_id', $survivor->id)
                ->where('custom_field_definition_id', $definitionId)
                ->first();

            if ($existing && (string) $existing->value === (string) $value) {
                continue;
            }

            AssetCustomFieldValue::updateOrCreate(
                [
                    'asset_id' => $survivor->id,
                    'custom_field_definition_id' => $definitionId,
                ],
                ['value' => (string) $value]
            );

            $applied[] = $key;
        }

        return $applied;
    }

    protected function reassignRelatedRecords(int $keepId, int $fromId): void
    {
        $this->reassignListItems($keepId, $fromId);
        $this->reassignSparePartAssets($keepId, $fromId);
        $this->reassignFileLinks($keepId, $fromId);
        $this->reassignSimpleAssetFk(Repair::class, $keepId, $fromId);
        $this->reassignSimpleAssetFk(AssetCheckout::class, $keepId, $fromId);
        $this->reassignSimpleAssetFk(AssetMaintenanceLog::class, $keepId, $fromId);
        $this->reassignSimpleAssetFk(StockMovement::class, $keepId, $fromId);
        $this->reassignSimpleAssetFk(AssetStockToken::class, $keepId, $fromId);
        $this->reassignSimpleAssetFk(AssetInterface::class, $keepId, $fromId);
        $this->reassignCustomFieldValues($keepId, $fromId);
        $this->reassignRuPlacements($keepId, $fromId);
        $this->reassignSimpleAssetFk(SparePartMovement::class, $keepId, $fromId, 'against_asset_id');
        $this->reassignSimpleAssetFk(SpareGroup::class, $keepId, $fromId);
        $this->reassignSimpleAssetFk(OutboundEmailThread::class, $keepId, $fromId);
        $this->reassignSimpleAssetFk(InboundEmailAction::class, $keepId, $fromId);
        UnknownScan::query()
            ->where('resolved_asset_id', $fromId)
            ->update(['resolved_asset_id' => $keepId]);
    }

    protected function reassignListItems(int $keepId, int $fromId): void
    {
        $items = AssetListItem::query()->where('asset_id', $fromId)->get();

        foreach ($items as $item) {
            $exists = AssetListItem::query()
                ->where('asset_list_id', $item->asset_list_id)
                ->where('asset_id', $keepId)
                ->exists();

            if ($exists) {
                $item->delete();
            } else {
                $item->update(['asset_id' => $keepId]);
            }
        }
    }

    protected function reassignSparePartAssets(int $keepId, int $fromId): void
    {
        $rows = DB::table('spare_part_assets')->where('asset_id', $fromId)->get();

        foreach ($rows as $row) {
            $exists = DB::table('spare_part_assets')
                ->where('spare_part_id', $row->spare_part_id)
                ->where('asset_id', $keepId)
                ->exists();

            if ($exists) {
                DB::table('spare_part_assets')
                    ->where('spare_part_id', $row->spare_part_id)
                    ->where('asset_id', $fromId)
                    ->delete();
            } else {
                DB::table('spare_part_assets')
                    ->where('spare_part_id', $row->spare_part_id)
                    ->where('asset_id', $fromId)
                    ->update(['asset_id' => $keepId]);
            }
        }
    }

    protected function reassignFileLinks(int $keepId, int $fromId): void
    {
        $links = FileLink::query()
            ->where('linkable_type', Asset::class)
            ->where('linkable_id', $fromId)
            ->get();

        foreach ($links as $link) {
            $exists = FileLink::query()
                ->where('file_id', $link->file_id)
                ->where('linkable_type', Asset::class)
                ->where('linkable_id', $keepId)
                ->exists();

            if ($exists) {
                $link->delete();
            } else {
                $link->update(['linkable_id' => $keepId]);
            }
        }
    }

    /**
     * @param  class-string  $modelClass
     */
    protected function reassignSimpleAssetFk(string $modelClass, int $keepId, int $fromId, string $column = 'asset_id'): void
    {
        $modelClass::query()
            ->where($column, $fromId)
            ->update([$column => $keepId]);
    }

    protected function reassignCustomFieldValues(int $keepId, int $fromId): void
    {
        $values = AssetCustomFieldValue::query()->where('asset_id', $fromId)->get();

        foreach ($values as $value) {
            $exists = AssetCustomFieldValue::query()
                ->where('asset_id', $keepId)
                ->where('custom_field_definition_id', $value->custom_field_definition_id)
                ->exists();

            if ($exists) {
                $value->delete();
            } else {
                $value->update(['asset_id' => $keepId]);
            }
        }
    }

    protected function reassignRuPlacements(int $keepId, int $fromId): void
    {
        AssetRuPlacement::query()
            ->where('rack_asset_id', $fromId)
            ->update(['rack_asset_id' => $keepId]);

        AssetRuPlacement::query()
            ->where('child_asset_id', $fromId)
            ->update(['child_asset_id' => $keepId]);
    }

    /**
     * @param  Collection<int, Asset>  $duplicates
     * @return array<string, mixed>
     */
    protected function blankFillScalarFields(Asset $keep, Collection $duplicates): array
    {
        $changes = [];

        $fillableScalars = [
            'fmi_ast',
            'tp_barcode',
            'rig_tag',
            'serial_number',
            'mac_address',
            'ip_address',
            'manufacturer_id',
            'manufacturer_model_id',
            'item_type_id',
            'location_id',
            'purchase_date',
            'warranty_expiry',
            'test_tag_expiry',
            'supplier',
            'replacement_cost',
            'quantity',
            'min_stock_level',
            'max_stock_level',
            'ru_height',
            'description',
        ];

        foreach ($fillableScalars as $field) {
            if (! $this->isBlank($keep->getAttribute($field))) {
                continue;
            }

            foreach ($duplicates as $duplicate) {
                $value = $duplicate->getAttribute($field);

                if (! $this->isBlank($value)) {
                    $changes[$field] = $value;
                    break;
                }
            }
        }

        if ($keep->ru_rear_enabled === null) {
            foreach ($duplicates as $duplicate) {
                if ($duplicate->ru_rear_enabled !== null) {
                    $changes['ru_rear_enabled'] = $duplicate->ru_rear_enabled;
                    break;
                }
            }
        }

        $notes = trim((string) ($keep->notes ?? ''));
        $noteSections = $notes !== '' ? [$notes] : [];

        foreach ($duplicates as $duplicate) {
            $dupNotes = trim((string) ($duplicate->notes ?? ''));
            if ($dupNotes === '') {
                continue;
            }

            $noteSections[] = 'Merged from '.$duplicate->name.":\n".$dupNotes;
        }

        if ($noteSections !== [] && ($notes === '' || count($noteSections) > 1)) {
            $changes['notes'] = implode("\n\n---\n", $noteSections);
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, int|string|null>  $fieldSources
     * @param  array<int, int|string|null>  $customFieldSources
     * @param  list<string>  $appliedFields
     * @param  list<string>  $appliedCustom
     */
    protected function logMergedAsset(
        Asset $keep,
        Asset $duplicate,
        int $userId,
        array $before,
        array $after,
        array $fieldSources,
        array $customFieldSources,
        array $appliedFields,
        array $appliedCustom,
    ): void {
        AuditLog::create([
            'user_id' => $userId,
            'department_id' => $keep->department_id !== null ? (int) $keep->department_id : null,
            'auditable_type' => Asset::class,
            'auditable_id' => $keep->id,
            'action' => 'merged',
            'old_values' => [
                'before' => $before,
                'merged_asset' => $this->snapshotAsset($duplicate),
            ],
            'new_values' => [
                'after' => $after,
                'merged_asset_id' => $duplicate->id,
                'merged_asset_name' => $duplicate->name,
                'field_sources' => $fieldSources,
                'custom_field_sources' => $customFieldSources,
                'applied_fields' => $appliedFields,
                'applied_custom_fields' => $appliedCustom,
                'fmi_ast' => $duplicate->fmi_ast,
                'tp_barcode' => $duplicate->tp_barcode,
                'rig_tag' => $duplicate->rig_tag,
                'serial_number' => $duplicate->serial_number,
            ],
            'created_at' => now(),
        ]);
    }

    protected function logMergedAssetSimple(Asset $keep, Asset $duplicate, int $userId): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'department_id' => $keep->department_id !== null ? (int) $keep->department_id : null,
            'auditable_type' => Asset::class,
            'auditable_id' => $keep->id,
            'action' => 'merged',
            'old_values' => null,
            'new_values' => [
                'merged_asset_id' => $duplicate->id,
                'merged_asset_name' => $duplicate->name,
                'fmi_ast' => $duplicate->fmi_ast,
                'tp_barcode' => $duplicate->tp_barcode,
                'rig_tag' => $duplicate->rig_tag,
                'serial_number' => $duplicate->serial_number,
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshotAsset(Asset $asset): array
    {
        $data = $asset->only(array_merge(['id', 'name', 'department_id'], self::MERGEABLE_FIELDS));

        $custom = [];
        foreach ($asset->customFieldValues as $value) {
            $custom[(int) $value->custom_field_definition_id] = $value->value;
        }
        $data['custom_fields'] = $custom;

        return $data;
    }

    protected function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        return false;
    }

    protected function valuesEqual(mixed $a, mixed $b): bool
    {
        if ($this->isBlank($a) && $this->isBlank($b)) {
            return true;
        }

        if (is_bool($a) || is_bool($b)) {
            return (bool) $a === (bool) $b;
        }

        if ($a instanceof \BackedEnum) {
            $a = $a->value;
        }
        if ($b instanceof \BackedEnum) {
            $b = $b->value;
        }

        if ($a instanceof \DateTimeInterface) {
            $a = $a->format('Y-m-d');
        }
        if ($b instanceof \DateTimeInterface) {
            $b = $b->format('Y-m-d');
        }

        return (string) $a === (string) $b;
    }
}

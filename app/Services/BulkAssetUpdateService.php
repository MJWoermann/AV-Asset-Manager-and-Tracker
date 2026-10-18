<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetInterface;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\AssetMaintenanceLog;
use App\Models\AssetRuPlacement;
use App\Models\AssetStockToken;
use App\Models\AuditLog;
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

class BulkAssetUpdateService
{
    /**
     * @param  array{
     *     asset_ids: array<int, int>,
     *     action: string,
     *     fields?: array<string, mixed>,
     *     add_to_list_id?: int|null,
     *     remove_from_list_id?: int|null,
     *     keep_asset_id?: int|null,
     * }  $payload
     * @return array{updated: int, deleted: int, merged: int, added_to_list: int, removed_from_list: int}
     */
    public function handle(array $payload, int $userId): array
    {
        $assetIds = collect($payload['asset_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        return DB::transaction(function () use ($payload, $assetIds, $userId) {
            return match ($payload['action'] ?? '') {
                'delete' => $this->wrapResult($this->deleteAssets($assetIds)),
                'merge' => $this->mergeAssets($assetIds, (int) ($payload['keep_asset_id'] ?? 0), $userId),
                default => $this->applyFieldUpdates($payload, $assetIds, $userId),
            };
        });
    }

    /**
     * @return array{updated: int, deleted: int, merged: int, added_to_list: int, removed_from_list: int}
     */
    protected function wrapResult(array $partial): array
    {
        return array_merge($this->emptyResult(), $partial);
    }

    /**
     * @return array{updated: int, deleted: int, merged: int, added_to_list: int, removed_from_list: int}
     */
    protected function emptyResult(): array
    {
        return [
            'updated' => 0,
            'deleted' => 0,
            'merged' => 0,
            'added_to_list' => 0,
            'removed_from_list' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, int>  $assetIds
     * @return array{updated: int, deleted: int, merged: int, added_to_list: int, removed_from_list: int}
     */
    protected function applyFieldUpdates(array $payload, Collection $assetIds, int $userId): array
    {
        $assets = Asset::query()->whereIn('id', $assetIds)->get();
        $fields = $payload['fields'] ?? [];
        $updated = 0;

        foreach ($assets as $asset) {
            $changes = $this->attributeChangesFor($asset, $fields);

            if ($changes !== []) {
                $asset->update($changes);
                $updated++;
            }
        }

        $addedToList = 0;
        if (! empty($payload['add_to_list_id'])) {
            $addedToList = $this->addToList($assets, (int) $payload['add_to_list_id'], $userId);
        }

        $removedFromList = 0;
        if (! empty($payload['remove_from_list_id'])) {
            $removedFromList = $this->removeFromList($assetIds, (int) $payload['remove_from_list_id']);
        }

        return $this->wrapResult([
            'updated' => $updated,
            'added_to_list' => $addedToList,
            'removed_from_list' => $removedFromList,
        ]);
    }

    /**
     * @param  Collection<int, int>  $assetIds
     * @return array{updated: int, deleted: int, merged: int, added_to_list: int, removed_from_list: int}
     */
    protected function mergeAssets(Collection $assetIds, int $keepId, int $userId): array
    {
        if ($assetIds->count() < 2) {
            throw ValidationException::withMessages([
                'asset_ids' => 'Select at least two assets to merge.',
            ]);
        }

        if (! $assetIds->contains($keepId)) {
            throw ValidationException::withMessages([
                'keep_asset_id' => 'The kept asset must be one of the selected assets.',
            ]);
        }

        $assets = Asset::query()->whereIn('id', $assetIds)->get()->keyBy('id');

        if ($assets->count() !== $assetIds->count()) {
            throw ValidationException::withMessages([
                'asset_ids' => 'One or more selected assets are not available in your department.',
            ]);
        }

        /** @var Asset $keep */
        $keep = $assets->get($keepId);

        $duplicateIds = $assetIds->filter(fn (int $id) => $id !== $keepId)->values();
        $duplicates = $assetIds
            ->filter(fn (int $id) => $id !== $keepId)
            ->map(fn (int $id) => $assets->get($id))
            ->filter()
            ->values();

        $keepHasOpenCheckout = $keep->openCheckout() !== null;

        foreach ($duplicates as $duplicate) {
            if ($keepHasOpenCheckout && $duplicate->openCheckout() !== null) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'Cannot merge while both the kept asset and a duplicate have an open checkout.',
                ]);
            }
        }

        $this->resolveKeepParent($keep, $duplicates, $duplicateIds);
        $this->fillKeepParentFromDuplicates($keep, $duplicates, $duplicateIds);

        Asset::query()
            ->whereIn('parent_id', $duplicateIds)
            ->update(['parent_id' => $keepId]);

        foreach ($duplicates as $duplicate) {
            $this->reassignRelatedRecords($keepId, (int) $duplicate->id);
        }

        $scalarChanges = $this->mergeScalarFields($keep, $duplicates);

        if ($scalarChanges !== []) {
            $keep->update($scalarChanges);
        }

        $deleted = 0;

        foreach ($duplicates as $duplicate) {
            $this->logMergedAsset($keep, $duplicate, $userId);
            $duplicate->delete();
            $deleted++;
        }

        return $this->wrapResult([
            'merged' => 1,
            'deleted' => $deleted,
            'updated' => $scalarChanges !== [] ? 1 : 0,
        ]);
    }

    /**
     * @param  Collection<int, Asset>  $duplicates
     * @param  Collection<int, int>  $duplicateIds
     */
    protected function resolveKeepParent(Asset $keep, Collection $duplicates, Collection $duplicateIds): void
    {
        if (! $keep->parent_id || ! $duplicateIds->contains((int) $keep->parent_id)) {
            return;
        }

        $removedParent = $duplicates->firstWhere('id', (int) $keep->parent_id);
        $candidate = $removedParent?->parent_id;

        if ($candidate !== null && ! $duplicateIds->contains((int) $candidate)) {
            $keep->parent_id = $candidate;
        } else {
            $keep->parent_id = null;
        }

        $keep->save();
    }

    /**
     * @param  Collection<int, Asset>  $duplicates
     * @param  Collection<int, int>  $duplicateIds
     */
    protected function fillKeepParentFromDuplicates(Asset $keep, Collection $duplicates, Collection $duplicateIds): void
    {
        if ($keep->parent_id) {
            return;
        }

        foreach ($duplicates as $duplicate) {
            $parentId = $duplicate->parent_id;

            if (
                $parentId
                && (int) $parentId !== (int) $keep->id
                && ! $duplicateIds->contains((int) $parentId)
            ) {
                $keep->parent_id = $parentId;
                $keep->save();
                break;
            }
        }
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
    protected function mergeScalarFields(Asset $keep, Collection $duplicates): array
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

    protected function logMergedAsset(Asset $keep, Asset $duplicate, int $userId): void
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
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function attributeChangesFor(Asset $asset, array $fields): array
    {
        $changes = [];

        foreach (['status', 'location_id', 'parent_id', 'test_tag_expiry', 'warranty_expiry', 'notes'] as $attribute) {
            if (! array_key_exists($attribute, $fields)) {
                continue;
            }

            $value = $fields[$attribute];

            if ($attribute === 'parent_id' && $value !== null && (int) $value === (int) $asset->id) {
                continue;
            }

            $changes[$attribute] = $value;
        }

        return $changes;
    }

    /** @param  Collection<int, int|string>  $assetIds */
    protected function deleteAssets(Collection $assetIds): array
    {
        $assets = Asset::query()->whereIn('id', $assetIds)->get();
        $count = 0;

        foreach ($assets as $asset) {
            $asset->delete();
            $count++;
        }

        return ['deleted' => $count];
    }

    /** @param  Collection<int, Asset>  $assets */
    protected function addToList(Collection $assets, int $listId, int $userId): int
    {
        AssetList::query()->findOrFail($listId);
        $added = 0;

        foreach ($assets as $asset) {
            $item = AssetListItem::query()->firstOrCreate(
                [
                    'asset_list_id' => $listId,
                    'asset_id' => $asset->id,
                ],
                [
                    'quantity' => max(1, (int) ($asset->quantity ?? 1)),
                    'is_child_expand' => (bool) $asset->parent_id,
                    'added_by' => $userId,
                ]
            );

            if ($item->wasRecentlyCreated) {
                $added++;
            }
        }

        return $added;
    }

    /** @param  Collection<int, int|string>  $assetIds */
    protected function removeFromList(Collection $assetIds, int $listId): int
    {
        $items = AssetListItem::query()
            ->where('asset_list_id', $listId)
            ->whereIn('asset_id', $assetIds)
            ->get();

        $count = 0;

        foreach ($items as $item) {
            $item->delete();
            $count++;
        }

        return $count;
    }
}

<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        $assets = Asset::query()
            ->with('parent:id,department_id')
            ->whereIn('id', $assetIds)
            ->get()
            // Parents first so department moves keep linked children when both are selected.
            ->sortBy(fn (Asset $asset) => $asset->parent_id === null ? 0 : 1)
            ->values();

        $fields = $payload['fields'] ?? [];
        $selectedIds = $assets->pluck('id')->map(fn ($id) => (int) $id)->all();
        $updated = 0;

        foreach ($assets as $asset) {
            $changes = $this->attributeChangesFor($asset, $fields, $selectedIds);

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
        $result = app(AssetMergeService::class)->mergeFillingBlanks($assetIds, $keepId, $userId);

        return $this->wrapResult($result);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  list<int>  $selectedIds
     * @return array<string, mixed>
     */
    protected function attributeChangesFor(Asset $asset, array $fields, array $selectedIds = []): array
    {
        $changes = [];

        foreach (['status', 'location_id', 'parent_id', 'department_id', 'test_tag_expiry', 'warranty_expiry', 'notes'] as $attribute) {
            if (! array_key_exists($attribute, $fields)) {
                continue;
            }

            $value = $fields[$attribute];

            if ($attribute === 'parent_id' && $value !== null && (int) $value === (int) $asset->id) {
                continue;
            }

            $changes[$attribute] = $value;
        }

        if (array_key_exists('department_id', $changes)) {
            $changes = $this->departmentChangesFor($asset, $changes, $selectedIds);
        }

        return $changes;
    }

    /**
     * Detach from a parent that is not moving with this asset when the department changes.
     *
     * @param  array<string, mixed>  $changes
     * @param  list<int>  $selectedIds
     * @return array<string, mixed>
     */
    protected function departmentChangesFor(Asset $asset, array $changes, array $selectedIds): array
    {
        $newDepartmentId = (int) $changes['department_id'];

        if ($asset->parent_id === null) {
            return $changes;
        }

        // Explicit clear/reparent in the same request wins.
        if (array_key_exists('parent_id', $changes)) {
            return $changes;
        }

        $parentId = (int) $asset->parent_id;

        if (in_array($parentId, $selectedIds, true)) {
            return $changes;
        }

        $parentDepartmentId = $asset->parent?->department_id;

        if ($parentDepartmentId !== null && (int) $parentDepartmentId === $newDepartmentId) {
            return $changes;
        }

        $changes['parent_id'] = null;

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

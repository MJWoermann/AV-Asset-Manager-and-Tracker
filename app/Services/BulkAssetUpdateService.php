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
     * }  $payload
     * @return array{updated: int, deleted: int, added_to_list: int, removed_from_list: int}
     */
    public function handle(array $payload, int $userId): array
    {
        $assetIds = collect($payload['asset_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        return DB::transaction(function () use ($payload, $assetIds, $userId) {
            if (($payload['action'] ?? '') === 'delete') {
                $deleted = $this->deleteAssets($assetIds);

                return [
                    'updated' => 0,
                    'deleted' => $deleted,
                    'added_to_list' => 0,
                    'removed_from_list' => 0,
                ];
            }

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

            return [
                'updated' => $updated,
                'deleted' => 0,
                'added_to_list' => $addedToList,
                'removed_from_list' => $removedFromList,
            ];
        });
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
    protected function deleteAssets(Collection $assetIds): int
    {
        $assets = Asset::query()->whereIn('id', $assetIds)->get();
        $count = 0;

        foreach ($assets as $asset) {
            $asset->delete();
            $count++;
        }

        return $count;
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

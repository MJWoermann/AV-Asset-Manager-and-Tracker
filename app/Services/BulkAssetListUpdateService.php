<?php

namespace App\Services;

use App\Models\AssetList;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkAssetListUpdateService
{
    /**
     * @param  array{
     *     asset_list_ids: array<int, int>,
     *     action: string,
     *     fields?: array<string, mixed>,
     * }  $payload
     * @return array{updated: int}
     */
    public function handle(array $payload): array
    {
        $listIds = collect($payload['asset_list_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        return DB::transaction(function () use ($payload, $listIds) {
            return match ($payload['action'] ?? '') {
                default => $this->applyFieldUpdates($payload, $listIds),
            };
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, int>  $listIds
     * @return array{updated: int}
     */
    protected function applyFieldUpdates(array $payload, Collection $listIds): array
    {
        $lists = AssetList::query()->whereIn('id', $listIds)->get();
        $fields = $payload['fields'] ?? [];
        $updated = 0;

        foreach ($lists as $list) {
            $changes = $this->attributeChangesFor($fields);

            if ($changes !== []) {
                $list->update($changes);
                $updated++;
            }
        }

        return ['updated' => $updated];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function attributeChangesFor(array $fields): array
    {
        $changes = [];

        if (array_key_exists('department_id', $fields)) {
            $changes['department_id'] = (int) $fields['department_id'];
        }

        return $changes;
    }
}

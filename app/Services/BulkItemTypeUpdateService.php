<?php

namespace App\Services;

use App\Models\ItemType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkItemTypeUpdateService
{
    /**
     * @param  array{
     *     item_type_ids: array<int, int>,
     *     action: string,
     *     fields?: array<string, mixed>,
     * }  $payload
     * @return array{updated: int}
     */
    public function handle(array $payload): array
    {
        $typeIds = collect($payload['item_type_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        return DB::transaction(function () use ($payload, $typeIds) {
            return match ($payload['action'] ?? '') {
                default => $this->applyFieldUpdates($payload, $typeIds),
            };
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, int>  $typeIds
     * @return array{updated: int}
     */
    protected function applyFieldUpdates(array $payload, Collection $typeIds): array
    {
        $types = ItemType::query()->whereIn('id', $typeIds)->get();
        $fields = $payload['fields'] ?? [];
        $updated = 0;

        foreach ($types as $type) {
            $changes = $this->attributeChangesFor($fields);

            if ($changes !== []) {
                $type->update($changes);
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

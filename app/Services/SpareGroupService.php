<?php

namespace App\Services;

use App\Models\SpareGroup;
use App\Models\SpareGroupItem;
use App\Models\SparePart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpareGroupService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{spare_part_id: int, quantity: int, sort_order?: int, notes?: string|null}>  $items
     */
    public function create(array $data, array $items = []): SpareGroup
    {
        return DB::transaction(function () use ($data, $items): SpareGroup {
            $group = SpareGroup::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'asset_id' => $data['asset_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($group, $items);

            return $group->fresh(['items.sparePart', 'location', 'linkedSystem']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{spare_part_id: int, quantity: int, sort_order?: int, notes?: string|null}>  $items
     */
    public function update(SpareGroup $group, array $data, array $items = []): SpareGroup
    {
        return DB::transaction(function () use ($group, $data, $items): SpareGroup {
            $group->update([
                'name' => $data['name'] ?? $group->name,
                'description' => $data['description'] ?? $group->description,
                'location_id' => $data['location_id'] ?? $group->location_id,
                'asset_id' => $data['asset_id'] ?? $group->asset_id,
                'notes' => $data['notes'] ?? $group->notes,
            ]);

            $this->syncItems($group, $items);

            return $group->fresh(['items.sparePart', 'location', 'linkedSystem']);
        });
    }

    /**
     * @param  list<array{spare_part_id: int, quantity: int, sort_order?: int, notes?: string|null}>  $items
     */
    public function syncItems(SpareGroup $group, array $items): void
    {
        $group->items()->delete();

        foreach ($items as $index => $row) {
            $partId = (int) ($row['spare_part_id'] ?? 0);
            $qty = (int) ($row['quantity'] ?? 0);

            if ($partId < 1 || ! SparePart::query()->whereKey($partId)->exists()) {
                throw ValidationException::withMessages([
                    "items.{$index}.spare_part_id" => 'Select a valid spare part.',
                ]);
            }

            if ($qty < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Quantity must be at least 1.',
                ]);
            }

            SpareGroupItem::query()->create([
                'spare_group_id' => $group->id,
                'spare_part_id' => $partId,
                'quantity' => $qty,
                'sort_order' => (int) ($row['sort_order'] ?? $index),
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }
}

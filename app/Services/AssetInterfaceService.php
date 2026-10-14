<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetInterface;
use App\Models\ItemType;
use App\Models\ItemTypeInterfaceTemplate;
use Illuminate\Support\Facades\DB;

class AssetInterfaceService
{
    /**
     * Copy missing ItemType interface templates onto an asset.
     * Existing interfaces (by template id or name) are left unchanged.
     */
    public function syncMissingFromItemType(Asset $asset): int
    {
        if (! $asset->item_type_id) {
            return 0;
        }

        $templates = ItemTypeInterfaceTemplate::query()
            ->where('item_type_id', $asset->item_type_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($templates->isEmpty()) {
            return 0;
        }

        $existing = AssetInterface::query()
            ->where('asset_id', $asset->id)
            ->get(['id', 'name', 'item_type_interface_template_id']);

        $existingTemplateIds = $existing
            ->pluck('item_type_interface_template_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $existingNames = $existing
            ->pluck('name')
            ->map(fn (string $name): string => mb_strtolower(trim($name)))
            ->all();

        $sortOrder = (int) AssetInterface::query()->where('asset_id', $asset->id)->max('sort_order');
        $created = 0;

        foreach ($templates as $template) {
            if (in_array((int) $template->id, $existingTemplateIds, true)) {
                continue;
            }

            $normalizedName = mb_strtolower(trim($template->name));
            if (in_array($normalizedName, $existingNames, true)) {
                continue;
            }

            $sortOrder += 10;

            AssetInterface::query()->create([
                'asset_id' => $asset->id,
                'name' => $template->name,
                'sort_order' => $sortOrder,
                'item_type_interface_template_id' => $template->id,
                'notes' => $template->notes,
            ]);

            $existingTemplateIds[] = (int) $template->id;
            $existingNames[] = $normalizedName;
            $created++;
        }

        return $created;
    }

    /**
     * Sync missing templates onto every asset of the given item type.
     */
    public function syncMissingForItemType(ItemType $itemType): int
    {
        $created = 0;

        Asset::query()
            ->where('item_type_id', $itemType->id)
            ->orderBy('id')
            ->chunkById(100, function ($assets) use (&$created): void {
                foreach ($assets as $asset) {
                    $created += $this->syncMissingFromItemType($asset);
                }
            });

        return $created;
    }

    /**
     * Replace item-type interface templates from a repeater payload.
     *
     * @param  list<array{id?: int|string|null, name: string, notes?: string|null}>  $rows
     */
    public function syncTemplates(ItemType $itemType, array $rows): void
    {
        DB::transaction(function () use ($itemType, $rows): void {
            $keepIds = [];

            foreach (array_values($rows) as $index => $row) {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $payload = [
                    'name' => $name,
                    'sort_order' => ($index + 1) * 10,
                    'notes' => isset($row['notes']) && $row['notes'] !== ''
                        ? (string) $row['notes']
                        : null,
                ];

                $id = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;

                if ($id) {
                    $template = ItemTypeInterfaceTemplate::query()
                        ->where('item_type_id', $itemType->id)
                        ->whereKey($id)
                        ->first();

                    if ($template) {
                        $template->update($payload);
                        $keepIds[] = $template->id;

                        continue;
                    }
                }

                $template = $itemType->interfaceTemplates()->create($payload);
                $keepIds[] = $template->id;
            }

            ItemTypeInterfaceTemplate::query()
                ->where('item_type_id', $itemType->id)
                ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
                ->when($keepIds === [], fn ($query) => $query)
                ->delete();
        });
    }
}

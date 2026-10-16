<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetInterface;
use App\Models\ItemType;
use App\Models\ItemTypeInterfaceTemplate;
use App\Models\ManufacturerModel;
use App\Models\ManufacturerModelInterfaceTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssetInterfaceService
{
    /**
     * Copy missing item-type and manufacturer-model interface templates onto an asset.
     * Existing interfaces (by template id or name) are left unchanged.
     */
    public function syncMissingForAsset(Asset $asset): int
    {
        $templates = $this->templatesForAsset($asset);

        if ($templates->isEmpty()) {
            return 0;
        }

        $existing = AssetInterface::query()
            ->where('asset_id', $asset->id)
            ->get([
                'id',
                'name',
                'item_type_interface_template_id',
                'manufacturer_model_interface_template_id',
            ]);

        $existingTypeTemplateIds = $existing
            ->pluck('item_type_interface_template_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $existingModelTemplateIds = $existing
            ->pluck('manufacturer_model_interface_template_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $existingNames = $existing
            ->pluck('name')
            ->map(fn (string $name): string => mb_strtolower(trim($name)))
            ->all();

        $sortOrder = (int) AssetInterface::query()->where('asset_id', $asset->id)->max('sort_order');
        $created = 0;

        foreach ($templates as $entry) {
            $template = $entry['template'];
            $source = $entry['source'];

            if ($source === 'type') {
                if (in_array((int) $template->id, $existingTypeTemplateIds, true)) {
                    continue;
                }
            } else {
                if (in_array((int) $template->id, $existingModelTemplateIds, true)) {
                    continue;
                }
            }

            $normalizedName = mb_strtolower(trim($template->name));
            if (in_array($normalizedName, $existingNames, true)) {
                continue;
            }

            $sortOrder += 10;

            $payload = [
                'asset_id' => $asset->id,
                'name' => $template->name,
                'sort_order' => $sortOrder,
                'notes' => $template->notes,
                'connection_type_id' => $template->connection_type_id,
                'item_type_interface_template_id' => null,
                'manufacturer_model_interface_template_id' => null,
            ];

            if ($source === 'type') {
                $payload['item_type_interface_template_id'] = $template->id;
                $existingTypeTemplateIds[] = (int) $template->id;
            } else {
                $payload['manufacturer_model_interface_template_id'] = $template->id;
                $existingModelTemplateIds[] = (int) $template->id;
            }

            AssetInterface::query()->create($payload);

            $existingNames[] = $normalizedName;
            $created++;
        }

        return $created;
    }

    /**
     * @return Collection<int, array{source: 'type'|'model', template: ItemTypeInterfaceTemplate|ManufacturerModelInterfaceTemplate}>
     */
    protected function templatesForAsset(Asset $asset): Collection
    {
        $entries = collect();

        if ($asset->item_type_id) {
            ItemTypeInterfaceTemplate::query()
                ->where('item_type_id', $asset->item_type_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(fn (ItemTypeInterfaceTemplate $template) => $entries->push([
                    'source' => 'type',
                    'template' => $template,
                ]));
        }

        if ($asset->manufacturer_model_id) {
            ManufacturerModelInterfaceTemplate::query()
                ->where('manufacturer_model_id', $asset->manufacturer_model_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(fn (ManufacturerModelInterfaceTemplate $template) => $entries->push([
                    'source' => 'model',
                    'template' => $template,
                ]));
        }

        return $entries;
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
                    $created += $this->syncMissingForAsset($asset);
                }
            });

        return $created;
    }

    /**
     * Sync missing templates onto every asset of the given manufacturer model.
     */
    public function syncMissingForManufacturerModel(ManufacturerModel $manufacturerModel): int
    {
        $created = 0;

        Asset::query()
            ->where('manufacturer_model_id', $manufacturerModel->id)
            ->orderBy('id')
            ->chunkById(100, function ($assets) use (&$created): void {
                foreach ($assets as $asset) {
                    $created += $this->syncMissingForAsset($asset);
                }
            });

        return $created;
    }

    /**
     * Replace item-type interface templates from a repeater payload.
     *
     * @param  list<array{id?: int|string|null, name: string, notes?: string|null, connection_type_id?: int|string|null}>  $rows
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
                    'connection_type_id' => $this->nullableConnectionTypeId($row['connection_type_id'] ?? null),
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

    /**
     * Replace manufacturer-model interface templates from a repeater payload.
     *
     * @param  list<array{id?: int|string|null, name: string, notes?: string|null, connection_type_id?: int|string|null}>  $rows
     */
    public function syncModelTemplates(ManufacturerModel $manufacturerModel, array $rows): void
    {
        DB::transaction(function () use ($manufacturerModel, $rows): void {
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
                    'connection_type_id' => $this->nullableConnectionTypeId($row['connection_type_id'] ?? null),
                ];

                $id = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;

                if ($id) {
                    $template = ManufacturerModelInterfaceTemplate::query()
                        ->where('manufacturer_model_id', $manufacturerModel->id)
                        ->whereKey($id)
                        ->first();

                    if ($template) {
                        $template->update($payload);
                        $keepIds[] = $template->id;

                        continue;
                    }
                }

                $template = $manufacturerModel->interfaceTemplates()->create($payload);
                $keepIds[] = $template->id;
            }

            ManufacturerModelInterfaceTemplate::query()
                ->where('manufacturer_model_id', $manufacturerModel->id)
                ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
                ->when($keepIds === [], fn ($query) => $query)
                ->delete();
        });
    }

    protected function nullableConnectionTypeId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}

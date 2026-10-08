<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetListItem;
use App\Models\ScanSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScanService
{
    public function __construct(
        protected ListComparisonService $comparison
    ) {}

    /**
     * Resolve barcode/search code to asset and add to event list (expanding children for racks).
     *
     * @return array{items: array<int, AssetListItem>, duplicate: bool, inventory_status: string, message: string}
     */
    public function scanCode(ScanSession $session, string $code, int $quantity = 1): array
    {
        if (! $session->isActive()) {
            throw ValidationException::withMessages(['code' => 'Scan session is closed.']);
        }

        $asset = Asset::query()->findByIdentifier($code)->with(['children', 'itemType'])->first();

        if (! $asset) {
            $asset = Asset::query()->search($code)->with(['children', 'itemType'])->first();
        }

        if (! $asset) {
            throw ValidationException::withMessages(['code' => 'No asset found for that code.']);
        }

        return DB::transaction(function () use ($session, $asset, $quantity) {
            $added = [];
            $duplicate = AssetListItem::query()
                ->where('asset_list_id', $session->event_list_id)
                ->where('asset_id', $asset->id)
                ->exists();

            if ($duplicate) {
                return [
                    'items' => [],
                    'duplicate' => true,
                    'inventory_status' => $this->inventoryStatus($session, $asset),
                    'message' => "{$asset->name} is already on this event list.",
                    'asset' => $asset,
                ];
            }

            $added[] = $this->addItem($session, $asset, $quantity, false);

            if ($asset->isContainer() || $asset->children->isNotEmpty()) {
                foreach ($asset->children as $child) {
                    $exists = AssetListItem::query()
                        ->where('asset_list_id', $session->event_list_id)
                        ->where('asset_id', $child->id)
                        ->exists();

                    if (! $exists) {
                        $added[] = $this->addItem($session, $child, 1, true);
                    }
                }
            }

            if ($session->location_id && ! $asset->parent_id) {
                $asset->update(['location_id' => $session->location_id]);
            }

            return [
                'items' => $added,
                'duplicate' => false,
                'inventory_status' => $this->inventoryStatus($session, $asset),
                'message' => count($added) > 1
                    ? "Added {$asset->name} with ".(count($added) - 1).' child asset(s).'
                    : "Added {$asset->name}.",
                'asset' => $asset,
            ];
        });
    }

    protected function addItem(ScanSession $session, Asset $asset, int $quantity, bool $isChild): AssetListItem
    {
        return AssetListItem::create([
            'asset_list_id' => $session->event_list_id,
            'asset_id' => $asset->id,
            'quantity' => $quantity,
            'is_child_expand' => $isChild,
            'added_by' => $session->user_id,
        ]);
    }

    protected function inventoryStatus(ScanSession $session, Asset $asset): string
    {
        if (! $session->inventory_list_id) {
            return 'no_inventory';
        }

        $onInventory = AssetListItem::query()
            ->where('asset_list_id', $session->inventory_list_id)
            ->where('asset_id', $asset->id)
            ->exists();

        return $onInventory ? 'matched' : 'not_on_inventory';
    }
}

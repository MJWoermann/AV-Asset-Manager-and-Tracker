<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetListItem;
use App\Models\ScanSession;
use App\Models\SparePart;
use App\Models\UnknownScan;
use App\Support\SparePartLuhn;
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
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     duplicate: bool,
     *     unknown: bool,
     *     inventory_status: string,
     *     warning: string|null,
     *     message: string,
     *     asset: array<string, mixed>|null,
     *     unknown_scan: array<string, mixed>|null
     * }
     */
    public function scanCode(ScanSession $session, string $code, int $quantity = 1): array
    {
        if (! $session->isActive()) {
            throw ValidationException::withMessages(['code' => 'Scan session is closed.']);
        }

        $code = trim($code);

        $asset = Asset::query()->findByIdentifier($code)->with(['children', 'itemType'])->first();

        if (! $asset) {
            $asset = Asset::query()
                ->search($code)
                ->orderBySearchRelevance($code)
                ->with(['children', 'itemType'])
                ->first();
        }

        if (! $asset) {
            $spare = $this->resolveSpareByCode($code);
            if ($spare) {
                return [
                    'items' => [],
                    'duplicate' => false,
                    'unknown' => false,
                    'inventory_status' => 'spare_part',
                    'warning' => null,
                    'message' => "Spare {$spare->code} ({$spare->component}).",
                    'asset' => null,
                    'unknown_scan' => null,
                    'spare_redirect' => route('spares.show', $spare),
                    'spare' => [
                        'id' => $spare->id,
                        'code' => $spare->code,
                        'component' => $spare->component,
                    ],
                ];
            }

            return $this->logUnknownScan($session, $code, $quantity);
        }

        return DB::transaction(function () use ($session, $asset, $quantity) {
            $inventoryStatus = $this->inventoryStatus($session, $asset);
            $warning = $this->inventoryWarning($inventoryStatus, $asset, (bool) $session->event_list_id);

            if (! $session->event_list_id) {
                $this->applySessionAssetUpdates($session, $asset);

                $added = [
                    $this->syntheticItemSummary($asset, $quantity, false),
                ];

                if ($asset->isContainer() || $asset->children->isNotEmpty()) {
                    foreach ($asset->children as $child) {
                        $child->loadMissing('itemType');
                        $added[] = $this->syntheticItemSummary($child, 1, true);
                    }
                }

                $message = count($added) > 1
                    ? "Scanned {$asset->name} with ".(count($added) - 1).' child asset(s).'
                    : "Scanned {$asset->name}.";

                if ($session->asset_status instanceof AssetStatus) {
                    $message .= ' Status updated to '.$session->asset_status->label().'.';
                }

                return [
                    'items' => $added,
                    'duplicate' => false,
                    'unknown' => false,
                    'inventory_status' => $inventoryStatus,
                    'warning' => $warning,
                    'message' => $message,
                    'asset' => $this->assetSummary($asset->fresh(['itemType'])),
                    'unknown_scan' => null,
                ];
            }

            $added = [];
            $duplicate = AssetListItem::query()
                ->where('asset_list_id', $session->event_list_id)
                ->where('asset_id', $asset->id)
                ->exists();

            if (! $duplicate) {
                $added[] = $this->itemSummary(
                    $this->addItem($session, $asset, $quantity, false),
                    $asset
                );

                if ($asset->isContainer() || $asset->children->isNotEmpty()) {
                    foreach ($asset->children as $child) {
                        $exists = AssetListItem::query()
                            ->where('asset_list_id', $session->event_list_id)
                            ->where('asset_id', $child->id)
                            ->exists();

                        if (! $exists) {
                            $child->loadMissing('itemType');
                            $added[] = $this->itemSummary(
                                $this->addItem($session, $child, 1, true),
                                $child
                            );
                        }
                    }
                }
            }

            $this->applySessionAssetUpdates($session, $asset);

            if ($duplicate) {
                $message = "{$asset->name} is already on this event list.";
                if ($session->asset_status instanceof AssetStatus) {
                    $message .= ' Status updated to '.$session->asset_status->label().'.';
                }

                return [
                    'items' => [],
                    'duplicate' => true,
                    'unknown' => false,
                    'inventory_status' => $inventoryStatus,
                    'warning' => $warning,
                    'message' => $message,
                    'asset' => $this->assetSummary($asset->fresh(['itemType'])),
                    'unknown_scan' => null,
                ];
            }

            return [
                'items' => $added,
                'duplicate' => false,
                'unknown' => false,
                'inventory_status' => $inventoryStatus,
                'warning' => $warning,
                'message' => count($added) > 1
                    ? "Added {$asset->name} with ".(count($added) - 1).' child asset(s).'
                    : "Added {$asset->name}.",
                'asset' => $this->assetSummary($asset->fresh(['itemType'])),
                'unknown_scan' => null,
            ];
        });
    }

    /**
     * Persist an unknown barcode for later identification / asset creation.
     *
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     duplicate: bool,
     *     unknown: bool,
     *     inventory_status: string,
     *     warning: string|null,
     *     message: string,
     *     asset: null,
     *     unknown_scan: array<string, mixed>
     * }
     */
    protected function resolveSpareByCode(string $code): ?SparePart
    {
        $part = SparePart::query()->findByCode($code)->first();
        if ($part) {
            return $part;
        }

        $digits = preg_replace('/\D/', '', $code) ?? '';
        if ($digits !== '' && SparePartLuhn::isValid($digits)) {
            return SparePart::query()->findByCode($digits)->first();
        }

        return null;
    }

    protected function logUnknownScan(ScanSession $session, string $code, int $quantity): array
    {
        $session->loadMissing('location');

        $unknown = UnknownScan::create([
            'department_id' => $session->department_id,
            'scanned_code' => $code,
            'user_id' => $session->user_id,
            'scan_session_id' => $session->id,
            'event_list_id' => $session->event_list_id,
            'inventory_list_id' => $session->inventory_list_id,
            'location_id' => $session->location_id,
            'quantity' => $quantity,
            'asset_status' => $session->asset_status,
        ]);

        return [
            'items' => [],
            'duplicate' => false,
            'unknown' => true,
            'inventory_status' => 'unknown_asset',
            'warning' => null,
            'message' => "No asset found for \"{$code}\". Logged for follow-up.",
            'asset' => null,
            'unknown_scan' => $this->unknownScanSummary($unknown),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function unknownScanSummary(UnknownScan $unknown): array
    {
        $unknown->loadMissing('location');

        return [
            'id' => $unknown->id,
            'scanned_code' => $unknown->scanned_code,
            'quantity' => $unknown->quantity,
            'note' => $unknown->note,
            'location' => $unknown->location?->name,
            'resolved' => $unknown->isResolved(),
            'create_url' => route('assets.create', [
                'tp_barcode' => $unknown->scanned_code,
                'location_id' => $unknown->location_id,
                'unknown_scan_id' => $unknown->id,
                'status' => $unknown->asset_status?->value,
            ]),
        ];
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

    /**
     * Apply scan-session location/status to the scanned asset (and child statuses when set).
     */
    protected function applySessionAssetUpdates(ScanSession $session, Asset $asset): void
    {
        $attributes = [];

        if ($session->asset_status instanceof AssetStatus) {
            $attributes['status'] = $session->asset_status;
        }

        if ($session->location_id && ! $asset->parent_id) {
            $attributes['location_id'] = $session->location_id;
        }

        if ($attributes !== []) {
            $asset->update($attributes);
        }

        if (! ($session->asset_status instanceof AssetStatus)) {
            return;
        }

        if (! $asset->relationLoaded('children')) {
            $asset->load('children');
        }

        foreach ($asset->children as $child) {
            $child->update(['status' => $session->asset_status]);
        }
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

    protected function inventoryWarning(string $inventoryStatus, Asset $asset, bool $addedToEventList = true): ?string
    {
        if ($inventoryStatus !== 'not_on_inventory') {
            return null;
        }

        $message = "{$asset->name} is in the asset register but not on the selected inventory list.";

        if ($addedToEventList) {
            $message .= ' It was still added to the event list.';
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    protected function assetSummary(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'tp_barcode' => $asset->tp_barcode,
            'fmi_ast' => $asset->fmi_ast,
            'rig_tag' => $asset->rig_tag,
            'serial_number' => $asset->serial_number,
            'item_type' => $asset->itemType?->name,
            'url' => route('assets.show', $asset),
        ];
    }

    /**
     * @return array{
     *     id: null,
     *     quantity: int,
     *     is_child_expand: bool,
     *     asset: array<string, mixed>
     * }
     */
    protected function syntheticItemSummary(Asset $asset, int $quantity, bool $isChild): array
    {
        return [
            'id' => null,
            'quantity' => $quantity,
            'is_child_expand' => $isChild,
            'asset' => $this->assetSummary($asset),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function itemSummary(AssetListItem $item, Asset $asset): array
    {
        return [
            'id' => $item->id,
            'quantity' => $item->quantity,
            'is_child_expand' => $item->is_child_expand,
            'asset' => $this->assetSummary($asset),
        ];
    }
}

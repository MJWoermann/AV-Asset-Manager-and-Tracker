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
     *     unknown_scan: array<string, mixed>|null,
     *     parent_assignment: 'assigned'|'already'|'needs_confirm'|'rejected'|null,
     *     parent_message: string|null,
     *     current_parent: array{id: int, name: string}|null,
     *     session_parent: array{id: int, name: string}|null
     * }
     */
    public function scanCode(ScanSession $session, string $code, int $quantity = 1, bool $confirmParentChange = false): array
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

        return DB::transaction(function () use ($session, $asset, $quantity, $confirmParentChange) {
            $inventoryStatus = $this->inventoryStatus($session, $asset);
            $warning = $this->inventoryWarning($inventoryStatus, $asset, (bool) $session->event_list_id);
            $assignment = $this->applySessionAssetUpdates($session, $asset, $confirmParentChange);

            if (! $session->event_list_id) {
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

                return $this->withParentAssignment([
                    'items' => $added,
                    'duplicate' => false,
                    'unknown' => false,
                    'inventory_status' => $inventoryStatus,
                    'warning' => $warning,
                    'message' => $message,
                    'asset' => $this->assetSummary($asset->fresh(['itemType'])),
                    'unknown_scan' => null,
                ], $assignment);
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

            if ($duplicate) {
                $message = "{$asset->name} is already on this event list.";
                if ($session->asset_status instanceof AssetStatus) {
                    $message .= ' Status updated to '.$session->asset_status->label().'.';
                }

                return $this->withParentAssignment([
                    'items' => [],
                    'duplicate' => true,
                    'unknown' => false,
                    'inventory_status' => $inventoryStatus,
                    'warning' => $warning,
                    'message' => $message,
                    'asset' => $this->assetSummary($asset->fresh(['itemType'])),
                    'unknown_scan' => null,
                ], $assignment);
            }

            return $this->withParentAssignment([
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
            ], $assignment);
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
        $unknown->loadMissing(['location', 'scanSession']);

        return [
            'id' => $unknown->id,
            'scanned_code' => $unknown->scanned_code,
            'quantity' => $unknown->quantity,
            'note' => $unknown->note,
            'location' => $unknown->location?->name,
            'has_event_list' => (bool) $unknown->event_list_id,
            'has_location' => (bool) $unknown->location_id,
            'resolved' => $unknown->isResolved(),
            'create_url' => route('assets.create', array_filter([
                'tp_barcode' => $unknown->scanned_code,
                'location_id' => $unknown->location_id,
                'unknown_scan_id' => $unknown->id,
                'status' => $unknown->asset_status?->value,
                'parent_id' => $unknown->scanSession?->parent_asset_id,
                'return_to' => route('scan.index', absolute: false),
            ], fn ($value) => $value !== null && $value !== '')),
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
     * Apply scan-session location/status/parent to the scanned asset (and child statuses when set).
     *
     * @return array{
     *     status: 'assigned'|'already'|'needs_confirm'|'rejected'|null,
     *     message: string|null,
     *     current_parent: array{id: int, name: string}|null,
     *     session_parent: array{id: int, name: string}|null
     * }
     */
    protected function applySessionAssetUpdates(ScanSession $session, Asset $asset, bool $confirmParentChange): array
    {
        $assignment = $this->parentAssignment($session, $asset, $confirmParentChange);
        $attributes = [];

        if ($session->asset_status instanceof AssetStatus) {
            $attributes['status'] = $session->asset_status;
        }

        if ($assignment['assign']) {
            $asset->unsetRelation('parent');
            $attributes['parent_id'] = $session->parent_asset_id;
        } elseif ($session->location_id && ! $asset->parent_id) {
            $attributes['location_id'] = $session->location_id;
        }

        if ($attributes !== []) {
            $asset->update($attributes);
        }

        if (! ($session->asset_status instanceof AssetStatus)) {
            return $this->parentAssignmentResult($assignment);
        }

        if (! $asset->relationLoaded('children')) {
            $asset->load('children');
        }

        foreach ($asset->children as $child) {
            $child->update(['status' => $session->asset_status]);
        }

        return $this->parentAssignmentResult($assignment);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array{
     *     status: 'assigned'|'already'|'needs_confirm'|'rejected'|null,
     *     message: string|null,
     *     current_parent: array{id: int, name: string}|null,
     *     session_parent: array{id: int, name: string}|null
     * }  $assignment
     * @return array<string, mixed>
     */
    protected function withParentAssignment(array $result, array $assignment): array
    {
        if ($assignment['message']) {
            $result['message'] = rtrim((string) $result['message']).' '.$assignment['message'];
        }

        $result['parent_assignment'] = $assignment['status'];
        $result['parent_message'] = $assignment['message'];
        $result['current_parent'] = $assignment['current_parent'];
        $result['session_parent'] = $assignment['session_parent'];

        return $result;
    }

    /**
     * @param  array{
     *     status: 'assigned'|'already'|'needs_confirm'|'rejected'|null,
     *     message: string|null,
     *     current_parent: array{id: int, name: string}|null,
     *     session_parent: array{id: int, name: string}|null,
     *     assign: bool
     * }  $assignment
     * @return array{
     *     status: 'assigned'|'already'|'needs_confirm'|'rejected'|null,
     *     message: string|null,
     *     current_parent: array{id: int, name: string}|null,
     *     session_parent: array{id: int, name: string}|null
     * }
     */
    protected function parentAssignmentResult(array $assignment): array
    {
        unset($assignment['assign']);

        return $assignment;
    }

    /**
     * @return array{
     *     status: 'assigned'|'already'|'needs_confirm'|'rejected'|null,
     *     message: string|null,
     *     current_parent: array{id: int, name: string}|null,
     *     session_parent: array{id: int, name: string}|null,
     *     assign: bool
     * }
     */
    protected function parentAssignment(ScanSession $session, Asset $asset, bool $confirmParentChange): array
    {
        $empty = [
            'status' => null,
            'message' => null,
            'current_parent' => null,
            'session_parent' => null,
            'assign' => false,
        ];

        if (! $session->parent_asset_id) {
            return $empty;
        }

        $parent = Asset::query()->find($session->parent_asset_id);

        if ($parent === null) {
            return $empty;
        }

        $asset->loadMissing('parent');
        $sessionParent = [
            'id' => $parent->id,
            'name' => $parent->name,
        ];
        $currentParent = $asset->parent === null
            ? null
            : [
                'id' => $asset->parent->id,
                'name' => $asset->parent->name,
            ];
        $base = [
            'current_parent' => $currentParent,
            'session_parent' => $sessionParent,
            'assign' => false,
        ];

        if ((int) $asset->id === (int) $parent->id) {
            return $base + [
                'status' => 'rejected',
                'message' => "Cannot assign {$asset->name} as a child of itself.",
            ];
        }

        if ($this->childIsAncestorOfParent($parent, $asset)) {
            return $base + [
                'status' => 'rejected',
                'message' => "Cannot assign {$asset->name} under {$parent->name} — that would create a cycle.",
            ];
        }

        if (
            $asset->department_id !== null
            && $parent->department_id !== null
            && (int) $asset->department_id !== (int) $parent->department_id
        ) {
            return $base + [
                'status' => 'rejected',
                'message' => "{$asset->name} belongs to another department and was not assigned as a child of {$parent->name}.",
            ];
        }

        if ((int) ($asset->parent_id ?? 0) === (int) $parent->id) {
            return $base + [
                'status' => 'already',
                'message' => "Already a child of {$parent->name}.",
            ];
        }

        if (
            $asset->parent_id
            && (int) $asset->parent_id !== (int) $parent->id
            && ! $session->replace_existing_parent
            && ! $confirmParentChange
        ) {
            $currentName = $currentParent['name'] ?? 'another asset';

            return $base + [
                'status' => 'needs_confirm',
                'message' => "{$asset->name} already belongs to {$currentName}. Confirm to assign it under {$parent->name}.",
            ];
        }

        $message = $asset->parent_id
            ? "Moved {$asset->name} from ".($currentParent['name'] ?? 'its previous parent')." to {$parent->name}."
            : "Assigned {$asset->name} as a child of {$parent->name}.";

        return [
            ...$base,
            'status' => 'assigned',
            'message' => $message,
            'assign' => true,
        ];
    }

    protected function childIsAncestorOfParent(Asset $parent, Asset $child): bool
    {
        $ancestorId = $parent->parent_id;
        $guard = 0;

        while ($ancestorId !== null && $guard < 1000) {
            if ((int) $ancestorId === (int) $child->id) {
                return true;
            }

            $ancestorId = Asset::withoutDepartmentScope()->whereKey($ancestorId)->value('parent_id');
            $guard++;
        }

        return false;
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

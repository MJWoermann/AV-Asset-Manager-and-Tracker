<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceLogType;
use App\Enums\NotificationEventKey;
use App\Http\Requests\BulkUpdateAssetsRequest;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetList;
use App\Models\AssetRuPlacement;
use App\Models\ConnectionType;
use App\Models\CustomFieldDefinition;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\UnknownScan;
use App\Services\AssetConnectionService;
use App\Services\AssetInterfaceService;
use App\Services\BulkAssetUpdateService;
use App\Services\FileAttachmentService;
use App\Services\KanbanTagService;
use App\Services\NotificationDispatchService;
use App\Services\NotificationFieldCatalog;
use App\Services\RuVisualiserService;
use App\Services\StockChartService;
use App\Services\StockMovementService;
use App\Support\AssetBrowseFilters;
use App\Support\AssetTableColumns;
use App\Support\EmailIntegrationSettings;
use App\Support\StockTracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $term = AssetBrowseFilters::term($request);
        $selectedColumns = $request->user()->assetTableColumns();

        $assets = Asset::query()
            ->with(['itemType', 'location', 'parent', 'customFieldValues'])
            ->whereNull('parent_id');

        AssetBrowseFilters::applyToAssetQuery($assets, $request);

        $assets = $assets
            ->orderBySearchRelevance($term)
            ->paginate(50)
            ->withQueryString();

        return view('assets.index', [
            'assets' => $assets,
            'statuses' => AssetStatus::options(),
            'q' => $term ?? '',
            'availableColumns' => AssetTableColumns::definitions(),
            'selectedColumns' => $selectedColumns,
            'preservedFilters' => AssetBrowseFilters::hiddenPreservedParams($request, $selectedColumns),
            'locations' => Location::with('parent.parent.parent')->orderBy('name')->get(),
            'itemTypes' => ItemType::orderBy('name')->get(),
            'parents' => Asset::whereNull('parent_id')->orderBy('name')->get(),
            'lists' => AssetList::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function bulkUpdate(BulkUpdateAssetsRequest $request, BulkAssetUpdateService $bulkUpdate): RedirectResponse
    {
        $result = $bulkUpdate->handle($request->bulkPayload(), (int) $request->user()->id);

        return back()->with('status', $this->bulkStatusMessage($result));
    }

    /**
     * @param  array{updated: int, deleted: int, added_to_list: int, removed_from_list: int}  $result
     */
    protected function bulkStatusMessage(array $result): string
    {
        $parts = [];

        if ($result['deleted'] > 0) {
            $parts[] = $result['deleted'].' asset'.($result['deleted'] === 1 ? '' : 's').' deleted';
        }

        if ($result['updated'] > 0) {
            $parts[] = $result['updated'].' asset'.($result['updated'] === 1 ? '' : 's').' updated';
        }

        if ($result['added_to_list'] > 0) {
            $parts[] = $result['added_to_list'].' added to list';
        }

        if ($result['removed_from_list'] > 0) {
            $parts[] = $result['removed_from_list'].' removed from list';
        }

        return $parts === [] ? 'No changes applied.' : implode('. ', $parts).'.';
    }

    public function create(Request $request): View
    {
        $statusValue = $request->string('status')->toString();
        $status = array_key_exists($statusValue, AssetStatus::options())
            ? AssetStatus::from($statusValue)
            : AssetStatus::Available;

        $asset = new Asset([
            'status' => $status,
            'tp_barcode' => $request->string('tp_barcode')->toString() ?: null,
            'fmi_ast' => $request->string('fmi_ast')->toString() ?: null,
            'rig_tag' => $request->string('rig_tag')->toString() ?: null,
            'serial_number' => $request->string('serial_number')->toString() ?: null,
            'location_id' => $request->integer('location_id') ?: null,
        ]);

        return view('assets.form', $this->assetFormData($asset, [
            'unknownScanId' => $request->integer('unknown_scan_id') ?: null,
        ]));
    }

    public function store(
        Request $request,
        AssetInterfaceService $interfaces,
        RuVisualiserService $ruVisualiser,
        NotificationDispatchService $notifications,
    ): RedirectResponse {
        $data = $this->validated($request);
        $extra = $request->validate([
            'unknown_scan_id' => ['nullable', 'integer', 'exists:unknown_scans,id'],
            'notify_mac_onboarding' => ['sometimes', 'boolean'],
        ]);
        $unknownScanId = $extra['unknown_scan_id'] ?? null;
        $notifyMac = $request->boolean('notify_mac_onboarding');

        $asset = Asset::create($data);
        $this->syncCustomFields($asset, $request);
        $interfaces->syncMissingFromItemType($asset);
        $ruVisualiser->seedRuHeightFromCustomField($asset->fresh());

        if ($unknownScanId) {
            $unknownScan = UnknownScan::query()->find($unknownScanId);
            if ($unknownScan && ! $unknownScan->isResolved()) {
                $unknownScan->markResolved($asset);
            }
        }

        $status = 'Asset created.';
        $warning = null;

        if (
            $notifyMac
            && $request->user()?->hasFeature('email_integration')
            && EmailIntegrationSettings::isEnabled()
        ) {
            $result = $notifications->dispatch(
                NotificationEventKey::AssetMacOnboarding,
                $asset->fresh(['itemType', 'location', 'customFieldValues.definition']),
                $request->user(),
            );

            if ($result['sent']) {
                $status = 'Asset created. MAC onboarding email sent.';
            } elseif (filled($result['warning'])) {
                $warning = $result['warning'];
            }
        }

        $redirect = redirect()->route('assets.show', $asset)->with('status', $status);

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }

    public function show(
        Request $request,
        Asset $asset,
        FileAttachmentService $attachments,
        AssetConnectionService $connections,
        RuVisualiserService $ruVisualiser,
        StockMovementService $stockMovements,
        StockChartService $stockCharts,
        KanbanTagService $kanbanTags,
    ): View {
        $asset->load([
            'itemType.fieldSets.definitions',
            'location.parent.parent',
            'children.itemType',
            'customFieldValues.definition',
            'parent',
            'ruPlacements.childAsset',
        ]);

        $ruVisualiser->seedRuHeightFromCustomField($asset);
        $asset->refresh();

        $requestedTab = $request->string('tab')->toString();
        $connectionsEnabled = $request->user()?->hasFeature('asset_connections') ?? false;
        $ruVisualiserEnabled = ($request->user()?->hasDeveloperFeature('ru_visualiser') ?? false)
            && (bool) $asset->itemType?->supports_ru_visualiser;
        $stockEnabled = StockTracking::availableForAsset($request->user(), $asset);

        $tab = match (true) {
            $requestedTab === 'history' => 'history',
            $requestedTab === 'files' => 'files',
            $requestedTab === 'maintenance' => 'maintenance',
            $requestedTab === 'connections' && $connectionsEnabled => 'connections',
            $requestedTab === 'rack' && $ruVisualiserEnabled => 'rack',
            $requestedTab === 'stock' && $stockEnabled => 'stock',
            default => 'details',
        };

        $auditLogs = $tab === 'history'
            ? $asset->auditLogs()->with('user')->paginate(25)->withQueryString()
            : null;

        $fileRows = $tab === 'files'
            ? $attachments->filesForAsset($asset)
            : [];

        $connectionPeers = collect();
        $connectionTypes = collect();
        $peerAssetOptions = [];

        if ($tab === 'connections') {
            $asset->load([
                'interfaces.connectionsAsA.connectionType',
                'interfaces.connectionsAsA.interfaceB.asset.itemType',
                'interfaces.connectionsAsA.interfaceB.asset.location',
                'interfaces.connectionsAsB.connectionType',
                'interfaces.connectionsAsB.interfaceA.asset.itemType',
                'interfaces.connectionsAsB.interfaceA.asset.location',
            ]);
            $connectionPeers = $connections->peersForAsset($asset);
            $connectionTypes = ConnectionType::query()->orderBy('sort_order')->orderBy('name')->get();
            $peerAssetOptions = Asset::query()
                ->whereKeyNot($asset->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Asset $peer): array => [
                    'value' => (string) $peer->id,
                    'label' => $peer->name,
                ])
                ->all();
        }

        $ruChildAssetOptions = [];
        $ruUnplacedChildren = collect();
        $ruPlacementsPayload = [];

        if ($tab === 'rack') {
            $placedChildIds = $asset->ruPlacements
                ->pluck('child_asset_id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $ruChildAssetOptions = Asset::query()
                ->whereKeyNot($asset->id)
                ->when($placedChildIds !== [], fn ($q) => $q->whereNotIn('id', $placedChildIds))
                ->with(['parent:id,name'])
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id', 'ru_height', 'tp_barcode'])
                ->map(fn (Asset $candidate): array => [
                    'value' => (string) $candidate->id,
                    'label' => $candidate->name.($candidate->tp_barcode ? ' ('.$candidate->tp_barcode.')' : ''),
                    'parent_id' => $candidate->parent_id ? (string) $candidate->parent_id : null,
                    'parent_name' => $candidate->parent?->name,
                    'ru_height' => max(1, (int) ($candidate->ru_height ?? 1)),
                ])
                ->all();

            $ruUnplacedChildren = $asset->children
                ->filter(fn (Asset $child): bool => ! in_array((int) $child->id, $placedChildIds, true))
                ->values();

            $ruPlacementsPayload = $asset->ruPlacements
                ->sortBy([
                    ['face', 'asc'],
                    ['start_ru', 'asc'],
                ])
                ->values()
                ->map(fn (AssetRuPlacement $placement): array => [
                    'id' => $placement->id,
                    'face' => $placement->face->value,
                    'start_ru' => (int) $placement->start_ru,
                    'ru_height' => (int) $placement->ru_height,
                    'kind' => $placement->kind->value,
                    'child_asset_id' => $placement->child_asset_id ? (string) $placement->child_asset_id : null,
                    'child_name' => $placement->childAsset?->name,
                    'child_url' => $placement->childAsset
                        ? route('assets.show', $placement->childAsset)
                        : null,
                    'note' => $placement->note,
                    'update_url' => route('assets.ru-placements.update', [$asset, $placement]),
                    'destroy_url' => route('assets.ru-placements.destroy', [$asset, $placement]),
                ])
                ->all();
        }

        $stockChartPayload = null;
        $stockTokens = collect();
        $stockReorderMeta = [];

        if ($tab === 'stock') {
            $stockMovements->ensureBaseline($asset, $request->user());
            $asset->refresh();
            $stockChartPayload = $stockCharts->build($asset);
            $stockTokens = $asset->stockTokens()->orderBy('label')->get();
            $stockReorderMeta = [
                'sku' => $kanbanTags->restockingField($asset, 'sku'),
                'supplier' => $kanbanTags->restockingField($asset, 'supplier') ?: $asset->supplier,
                'url' => $kanbanTags->restockingField($asset, 'url'),
            ];
        }

        $maintenanceLogs = collect();

        if ($tab === 'maintenance') {
            $maintenanceLogs = $asset->maintenanceLogs()
                ->with(['user', 'files'])
                ->get();
        }

        return view('assets.show', [
            'asset' => $asset,
            'tab' => $tab,
            'auditLogs' => $auditLogs,
            'fileRows' => $fileRows,
            'connectionPeers' => $connectionPeers,
            'connectionTypes' => $connectionTypes,
            'peerAssetOptions' => $peerAssetOptions,
            'connectionsEnabled' => $connectionsEnabled,
            'ruVisualiserEnabled' => $ruVisualiserEnabled,
            'ruChildAssetOptions' => $ruChildAssetOptions,
            'ruUnplacedChildren' => $ruUnplacedChildren,
            'ruPlacementsPayload' => $ruPlacementsPayload,
            'stockEnabled' => $stockEnabled,
            'stockChartPayload' => $stockChartPayload,
            'stockTokens' => $stockTokens,
            'stockReorderMeta' => $stockReorderMeta,
            'stockAction' => $request->string('action')->toString() ?: null,
            'maintenanceLogs' => $maintenanceLogs,
            'maintenanceLogTypes' => MaintenanceLogType::options(),
            'maintenanceStatuses' => AssetStatus::options(),
            'canDeleteMaintenanceLogs' => $request->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false,
        ]);
    }

    public function edit(Asset $asset, RuVisualiserService $ruVisualiser): View
    {
        $asset->load(['itemType.fieldSets.definitions', 'customFieldValues.definition']);
        $ruVisualiser->seedRuHeightFromCustomField($asset);
        $asset->refresh();

        return view('assets.form', $this->assetFormData($asset));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function assetFormData(Asset $asset, array $extra = []): array
    {
        $itemTypes = ItemType::orderBy('name')->get();

        $ruHeightPrefill = $asset->ru_height;
        if ($ruHeightPrefill === null && $asset->exists) {
            $ruHeightPrefill = app(RuVisualiserService::class)->customRuValue($asset);
        }

        $emailIntegrationEnabled = (auth()->user()?->hasFeature('email_integration') ?? false)
            && EmailIntegrationSettings::isEnabled();

        $macCustomFields = [];
        if ($emailIntegrationEnabled && ! $asset->exists) {
            $macCustomFields = app(NotificationFieldCatalog::class)
                ->macCustomFieldMetaForTypes()
                ->all();
        }

        return array_merge([
            'asset' => $asset,
            'itemTypes' => $itemTypes,
            'locations' => Location::orderBy('name')->get(),
            'parents' => Asset::query()
                ->whereNull('parent_id')
                ->when($asset->exists, fn ($q) => $q->where('id', '!=', $asset->id))
                ->orderBy('name')
                ->get(),
            'statuses' => AssetStatus::options(),
            'ruVisualiserEnabled' => auth()->user()?->hasDeveloperFeature('ru_visualiser') ?? false,
            'itemTypesSupportRu' => $itemTypes
                ->mapWithKeys(fn (ItemType $type): array => [(string) $type->id => (bool) $type->supports_ru_visualiser])
                ->all(),
            'ruHeightPrefill' => $ruHeightPrefill,
            'stockTrackingEnabled' => StockTracking::enabledFor(auth()->user()),
            'itemTypesSupportStock' => $itemTypes
                ->mapWithKeys(fn (ItemType $type): array => [(string) $type->id => (bool) $type->supports_stock_tracking])
                ->all(),
            'emailIntegrationEnabled' => $emailIntegrationEnabled,
            'macCustomFields' => $macCustomFields,
        ], $extra);
    }

    public function update(
        Request $request,
        Asset $asset,
        AssetInterfaceService $interfaces,
        RuVisualiserService $ruVisualiser,
    ): RedirectResponse {
        $data = $this->validated($request);

        if (array_key_exists('ru_height', $data) || array_key_exists('ru_rear_enabled', $data)) {
            $ruVisualiser->assertRackSettingsChange(
                $asset,
                array_key_exists('ru_height', $data) ? $data['ru_height'] : $asset->ru_height,
                array_key_exists('ru_rear_enabled', $data)
                    ? (bool) $data['ru_rear_enabled']
                    : (bool) $asset->ru_rear_enabled,
            );
        }

        $previousTypeId = $asset->item_type_id;
        $asset->update($data);
        $this->syncCustomFields($asset, $request);

        if ((int) $previousTypeId !== (int) $asset->item_type_id) {
            $interfaces->syncMissingFromItemType($asset->fresh());
        }

        $ruVisualiser->seedRuHeightFromCustomField($asset->fresh());

        return redirect()->route('assets.show', $asset)->with('status', 'Asset updated.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'inventory_manager']), 403);
        $asset->delete();

        return redirect()->route('assets.index')->with('status', 'Asset deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request): array
    {
        if ($request->has('ru_rear_enabled')) {
            $request->merge([
                'ru_rear_enabled' => $request->boolean('ru_rear_enabled'),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'item_type_id' => ['required', 'exists:item_types,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'parent_id' => ['nullable', 'exists:assets,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(AssetStatus::options()))],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expiry' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'replacement_cost' => ['nullable', 'numeric', 'min:0'],
            'fmi_ast' => ['nullable', 'string', 'max:255'],
            'tp_barcode' => ['nullable', 'string', 'max:255'],
            'rig_tag' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:32'],
            'test_tag_expiry' => ['nullable', 'date'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'max_stock_level' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'ru_height' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ru_rear_enabled' => ['sometimes', 'boolean'],
        ]);

        if (! array_key_exists('ru_rear_enabled', $data) && $request->has('ru_rear_enabled')) {
            $data['ru_rear_enabled'] = $request->boolean('ru_rear_enabled');
        }

        if (! StockTracking::enabledFor($request->user())) {
            unset($data['min_stock_level'], $data['max_stock_level']);
        }

        $itemTypeId = (int) ($data['item_type_id'] ?? 0);
        $supportsStock = $itemTypeId > 0
            && (bool) ItemType::query()->whereKey($itemTypeId)->value('supports_stock_tracking');

        if ($supportsStock) {
            // Quantity for stock-tracked assets is owned by the movement ledger.
            unset($data['quantity']);
        } else {
            unset($data['min_stock_level'], $data['max_stock_level']);
        }

        if (
            isset($data['min_stock_level'], $data['max_stock_level'])
            && $data['max_stock_level'] < $data['min_stock_level']
        ) {
            throw ValidationException::withMessages([
                'max_stock_level' => 'Max stock level must be greater than or equal to min.',
            ]);
        }

        return $data;
    }

    protected function syncCustomFields(Asset $asset, Request $request): void
    {
        $values = $request->input('custom_fields', []);
        if (! is_array($values) || $values === []) {
            return;
        }

        $layoutIds = CustomFieldDefinition::query()
            ->whereIn('id', array_keys($values))
            ->whereIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->pluck('id')
            ->all();

        foreach ($values as $definitionId => $value) {
            if (in_array((int) $definitionId, $layoutIds, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                AssetCustomFieldValue::where('asset_id', $asset->id)
                    ->where('custom_field_definition_id', $definitionId)
                    ->delete();

                continue;
            }

            AssetCustomFieldValue::updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'custom_field_definition_id' => $definitionId,
                ],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }
    }
}

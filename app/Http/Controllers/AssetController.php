<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\CheckoutPurpose;
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
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\UnknownScan;
use App\Models\User;
use App\Services\AssetConnectionService;
use App\Services\AssetInterfaceService;
use App\Services\BulkAssetUpdateService;
use App\Services\ConnectionBusService;
use App\Services\FileAttachmentService;
use App\Services\KanbanTagService;
use App\Services\ManufacturerCatalogueService;
use App\Services\NotificationDispatchService;
use App\Services\NotificationFieldCatalog;
use App\Services\RuVisualiserService;
use App\Services\StockChartService;
use App\Services\StockMovementService;
use App\Support\AssetBrowseFilters;
use App\Support\AssetHistoryFilters;
use App\Support\AssetMaintenanceFilters;
use App\Support\AssetTableColumns;
use App\Support\EmailIntegrationSettings;
use App\Support\StockTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $selectedColumns = $request->user()->assetTableColumns();

        if (AssetBrowseFilters::hasFilledHiddenColumnFilters($request, $selectedColumns)) {
            return redirect()->to(
                AssetBrowseFilters::urlWithoutHiddenColumnFilters($request->fullUrl(), $selectedColumns)
            );
        }

        $term = AssetBrowseFilters::term($request);

        $assets = Asset::query()
            ->with(['itemType', 'location', 'parent', 'manufacturer', 'manufacturerModel', 'customFieldValues'])
            ->whereNull('parent_id');

        AssetBrowseFilters::applyToAssetQuery($assets, $request, selectedColumns: $selectedColumns);

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
            'locations' => Location::with('parent.parent.parent')->orderBy('name')->get(),
            'itemTypes' => ItemType::orderBy('name')->get(),
            'manufacturers' => Manufacturer::query()->orderBy('name')->get(['id', 'name']),
            'manufacturerModels' => ManufacturerModel::query()
                ->with('manufacturer:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'manufacturer_id']),
            'parents' => Asset::whereNull('parent_id')->orderBy('name')->get(),
            'lists' => AssetList::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        $searchTerm = $term !== '' ? $term : null;

        $assets = Asset::query()
            ->with(['itemType:id,name'])
            ->search($searchTerm)
            ->orderBySearchRelevance($searchTerm)
            ->limit(20)
            ->get(['id', 'name', 'tp_barcode', 'fmi_ast', 'serial_number', 'item_type_id']);

        return response()->json([
            'data' => $assets->map(static function (Asset $asset): array {
                $parts = array_filter([
                    $asset->tp_barcode,
                    $asset->fmi_ast,
                    $asset->serial_number,
                ]);

                return [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'tp_barcode' => $asset->tp_barcode,
                    'fmi_ast' => $asset->fmi_ast,
                    'serial_number' => $asset->serial_number,
                    'item_type' => $asset->itemType?->name,
                    'label' => $parts === []
                        ? $asset->name
                        : $asset->name.' ('.implode(' · ', $parts).')',
                ];
            })->values(),
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
        $interfaces->syncMissingForAsset($asset);
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
        ConnectionBusService $buses,
        RuVisualiserService $ruVisualiser,
        StockMovementService $stockMovements,
        StockChartService $stockCharts,
        KanbanTagService $kanbanTags,
    ): View {
        $user = $request->user();
        $isExternalRepairer = $user?->isExternalRepairer() ?? false;

        if ($isExternalRepairer && ! $user->canAccessAssetViaAssignedRepair($asset)) {
            abort(403, 'You can only view assets on repairs assigned to you.');
        }

        $asset->load([
            'itemType.fieldSets.definitions',
            'location.parent.parent',
            'children.itemType',
            'customFieldValues.definition',
            'parent',
            'manufacturer',
            'manufacturerModel',
            'ruPlacements.childAsset',
        ]);

        $ruVisualiser->seedRuHeightFromCustomField($asset);
        $asset->refresh();

        $requestedTab = $request->string('tab')->toString();
        $connectionsEnabled = ! $isExternalRepairer
            && ($user?->hasFeature('asset_connections') ?? false);
        $ruVisualiserEnabled = ! $isExternalRepairer
            && ($user?->hasDeveloperFeature('ru_visualiser') ?? false)
            && (bool) $asset->itemType?->supports_ru_visualiser;
        $stockEnabled = ! $isExternalRepairer
            && StockTracking::availableForAsset($user, $asset);

        $tab = match (true) {
            $isExternalRepairer && ! in_array($requestedTab, ['', 'details', 'maintenance'], true) => 'details',
            $requestedTab === 'history' && ! $isExternalRepairer => 'history',
            $requestedTab === 'files' && ! $isExternalRepairer => 'files',
            $requestedTab === 'maintenance' => 'maintenance',
            $requestedTab === 'connections' && $connectionsEnabled => 'connections',
            $requestedTab === 'rack' && $ruVisualiserEnabled => 'rack',
            $requestedTab === 'stock' && $stockEnabled => 'stock',
            $requestedTab === 'spares' && ! $isExternalRepairer => 'spares',
            default => 'details',
        };

        $auditLogs = null;
        $historyActionOptions = [];
        $historyUserOptions = collect();

        if ($tab === 'history') {
            $historyQuery = $asset->auditLogs()->with('user');
            AssetHistoryFilters::apply($historyQuery, $request);
            $auditLogs = $historyQuery->paginate(25)->withQueryString();
            $historyActionOptions = AssetHistoryFilters::actionOptions($asset);
            $historyUserOptions = AssetHistoryFilters::userOptions($asset);
        }

        $fileRows = $tab === 'files'
            ? $attachments->filesForAsset($asset)
            : [];

        $connectionPeers = collect();
        $busMemberships = collect();
        $connectionBusesByType = [];
        $connectionTypes = collect();
        $peerAssetOptions = [];
        $canApplyInterfaceTemplates = false;

        if ($tab === 'connections') {
            $asset->load([
                'itemType.interfaceTemplates',
                'manufacturerModel.interfaceTemplates',
                'interfaces.connectionType',
                'interfaces.buses.connectionType',
                'interfaces.buses.interfaces.asset.itemType',
                'interfaces.buses.interfaces.asset.location',
                'interfaces.connectionsAsA.connectionType',
                'interfaces.connectionsAsA.interfaceB.asset.itemType',
                'interfaces.connectionsAsA.interfaceB.asset.location',
                'interfaces.connectionsAsB.connectionType',
                'interfaces.connectionsAsB.interfaceA.asset.itemType',
                'interfaces.connectionsAsB.interfaceA.asset.location',
            ]);
            $connectionPeers = $connections->peersForAsset($asset);
            $busMemberships = $buses->membershipsForAsset($asset);
            $connectionTypes = ConnectionType::query()->orderBy('sort_order')->orderBy('name')->get();
            $connectionBusesByType = $connectionTypes
                ->filter(fn (ConnectionType $type): bool => (bool) $type->allows_bus)
                ->mapWithKeys(function (ConnectionType $type) use ($buses): array {
                    return [
                        (string) $type->id => $buses->busesForType($type)->map(fn ($bus): array => [
                            'id' => (string) $bus->id,
                            'name' => $bus->name,
                            'member_count' => (int) $bus->interfaces_count,
                        ])->values()->all(),
                    ];
                })
                ->all();
            $peerAssetOptions = Asset::query()
                ->whereKeyNot($asset->id)
                ->with(['interfaces:id,asset_id,connection_type_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Asset $peer): array => [
                    'value' => (string) $peer->id,
                    'label' => $peer->name,
                    'connection_type_ids' => $peer->interfaces
                        ->pluck('connection_type_id')
                        ->filter()
                        ->map(fn ($id): string => (string) $id)
                        ->unique()
                        ->values()
                        ->all(),
                ])
                ->all();

            $canApplyInterfaceTemplates = ($asset->itemType?->interfaceTemplates->isNotEmpty() ?? false)
                || ($asset->manufacturerModel?->interfaceTemplates->isNotEmpty() ?? false);
        }

        $ruChildAssetOptions = [];
        $ruUnplacedChildren = collect();
        $ruPlacementsPayload = [];
        $ruDetailsPreview = null;

        $needsRuPayload = $tab === 'rack'
            || ($tab === 'details' && $ruVisualiserEnabled && $asset->ruPlacements->isNotEmpty());

        if ($needsRuPayload) {
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
                    'full_depth' => (bool) $placement->full_depth,
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

        if ($tab === 'details' && $ruVisualiserEnabled && $ruPlacementsPayload !== [] && (int) ($asset->ru_height ?? 0) >= 1) {
            $ruDetailsPreview = [
                'capacity' => (int) $asset->ru_height,
                'placements' => $ruPlacementsPayload,
            ];
        }

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
        $maintenanceUserOptions = collect();
        $openRepairs = collect();
        $openRepairsByFaultId = collect();

        $repairAssigneeOptions = collect();

        if ($tab === 'maintenance') {
            $maintenanceQuery = $asset->maintenanceLogs()->with(['user', 'files']);
            AssetMaintenanceFilters::apply($maintenanceQuery, $request);
            $maintenanceLogs = $maintenanceQuery->get();
            $maintenanceUserOptions = AssetMaintenanceFilters::userOptions($asset);
            $openRepairs = $asset->repairs()->active()->with('assignee')->get();
            $openRepairsByFaultId = $openRepairs->keyBy('maintenance_log_id');
            if (! $isExternalRepairer) {
                $repairAssigneeOptions = User::role([
                    'admin',
                    'inventory_manager',
                    'scanner',
                    'external_repairer',
                ])->orderBy('name')->get(['id', 'name', 'email']);
            }
        }

        $spareMovements = collect();
        if ($tab === 'spares') {
            $spareMovements = $asset->sparePartMovements()
                ->with(['sparePart', 'user'])
                ->limit(100)
                ->get();
        }

        $openCheckout = $asset->checkouts()
            ->open()
            ->with(['checkedOutTo', 'checkedOutBy', 'repair'])
            ->first();

        $checkoutAssigneeOptions = collect();
        $checkoutOpenRepairs = collect();
        $canManageCheckouts = ! $isExternalRepairer
            && ($user?->hasAnyRole(['admin', 'inventory_manager', 'scanner']) ?? false);

        if ($canManageCheckouts && $tab === 'details') {
            $checkoutAssigneeOptions = User::role([
                'admin',
                'inventory_manager',
                'scanner',
                'external_repairer',
            ])->orderBy('name')->get(['id', 'name', 'email']);

            $checkoutOpenRepairs = $asset->repairs()->active()->orderByDesc('started_at')->get(['id', 'summary']);
        }

        return view('assets.show', [
            'asset' => $asset,
            'tab' => $tab,
            'auditLogs' => $auditLogs,
            'historyActionOptions' => $historyActionOptions,
            'historyUserOptions' => $historyUserOptions,
            'fileRows' => $fileRows,
            'connectionPeers' => $connectionPeers,
            'busMemberships' => $busMemberships,
            'connectionBusesByType' => $connectionBusesByType,
            'connectionTypes' => $connectionTypes,
            'peerAssetOptions' => $peerAssetOptions,
            'canApplyInterfaceTemplates' => $canApplyInterfaceTemplates,
            'connectionsEnabled' => $connectionsEnabled,
            'ruVisualiserEnabled' => $ruVisualiserEnabled,
            'ruChildAssetOptions' => $ruChildAssetOptions,
            'ruUnplacedChildren' => $ruUnplacedChildren,
            'ruPlacementsPayload' => $ruPlacementsPayload,
            'ruDetailsPreview' => $ruDetailsPreview,
            'stockEnabled' => $stockEnabled,
            'stockChartPayload' => $stockChartPayload,
            'stockTokens' => $stockTokens,
            'stockReorderMeta' => $stockReorderMeta,
            'stockAction' => $tab === 'stock' ? ($request->string('action')->toString() ?: null) : null,
            'maintenanceLogs' => $maintenanceLogs,
            'maintenanceUserOptions' => $maintenanceUserOptions,
            'maintenanceLogTypes' => MaintenanceLogType::options(),
            'maintenanceStatuses' => AssetStatus::options(),
            'canDeleteMaintenanceLogs' => $user?->hasAnyRole(['admin', 'inventory_manager']) ?? false,
            'openRepairs' => $openRepairs,
            'openRepairsByFaultId' => $openRepairsByFaultId,
            'repairAssigneeOptions' => $repairAssigneeOptions,
            'spareMovements' => $spareMovements,
            'isExternalRepairer' => $isExternalRepairer,
            'openCheckout' => $openCheckout,
            'canManageCheckouts' => $canManageCheckouts,
            'checkoutPurposes' => CheckoutPurpose::options(),
            'checkoutAssigneeOptions' => $checkoutAssigneeOptions,
            'checkoutOpenRepairs' => $checkoutOpenRepairs,
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

        $manufacturers = Manufacturer::query()->with(['models' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();

        $modelsByManufacturer = $manufacturers
            ->mapWithKeys(fn (Manufacturer $manufacturer): array => [
                (string) $manufacturer->id => $manufacturer->models
                    ->map(fn (ManufacturerModel $model): array => [
                        'value' => (string) $model->id,
                        'label' => $model->name,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        // #region agent log
        $initialManufacturerId = old('manufacturer_id', $asset->manufacturer_id);
        $mfrKey = $initialManufacturerId !== null && $initialManufacturerId !== ''
            ? (string) $initialManufacturerId
            : '';
        file_put_contents(base_path('debug-114b8c.log'), json_encode([
            'sessionId' => '114b8c',
            'runId' => 'pre-fix',
            'hypothesisId' => 'H3',
            'location' => 'AssetController.php:assetFormData',
            'message' => 'asset form modelsByManufacturer payload',
            'data' => [
                'assetId' => $asset->id,
                'assetExists' => $asset->exists,
                'initialManufacturerId' => $mfrKey,
                'initialModelId' => old('manufacturer_model_id', $asset->manufacturer_model_id),
                'mapKeyCount' => count($modelsByManufacturer),
                'mapKeysSample' => array_slice(array_keys($modelsByManufacturer), 0, 8),
                'optionsForInitialMfr' => $mfrKey !== '' && isset($modelsByManufacturer[$mfrKey])
                    ? count($modelsByManufacturer[$mfrKey])
                    : null,
                'lookupMiss' => $mfrKey !== '' && ! isset($modelsByManufacturer[$mfrKey]),
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND | LOCK_EX);
        // #endregion

        return array_merge([
            'asset' => $asset,
            'itemTypes' => $itemTypes,
            'locations' => Location::orderBy('name')->get(),
            'manufacturers' => $manufacturers,
            'modelsByManufacturer' => $modelsByManufacturer,
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
        $previousModelId = $asset->manufacturer_model_id;
        $asset->update($data);
        $this->syncCustomFields($asset, $request);

        if (
            (int) $previousTypeId !== (int) $asset->item_type_id
            || (int) $previousModelId !== (int) $asset->manufacturer_model_id
        ) {
            $interfaces->syncMissingForAsset($asset->fresh());
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

        $creatingManufacturer = $request->input('manufacturer_id') === '__new__';
        $creatingModel = $request->input('manufacturer_model_id') === '__new__';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'item_type_id' => ['required', 'exists:item_types,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'parent_id' => ['nullable', 'exists:assets,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(AssetStatus::options()))],
            'manufacturer_id' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) use ($creatingManufacturer) {
                if ($value === null || $value === '' || $creatingManufacturer) {
                    return;
                }
                if (! Manufacturer::query()->whereKey($value)->exists()) {
                    $fail('The selected manufacturer is invalid.');
                }
            }],
            'new_manufacturer' => [$creatingManufacturer ? 'required' : 'nullable', 'string', 'max:255'],
            'manufacturer_model_id' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) use ($creatingModel) {
                if ($value === null || $value === '' || $creatingModel) {
                    return;
                }
                if (! ManufacturerModel::query()->whereKey($value)->exists()) {
                    $fail('The selected model is invalid.');
                }
            }],
            'new_manufacturer_model' => [$creatingModel ? 'required' : 'nullable', 'string', 'max:255'],
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

        unset($data['new_manufacturer'], $data['new_manufacturer_model']);

        $catalogue = app(ManufacturerCatalogueService::class);
        $manufacturer = null;

        if ($creatingManufacturer) {
            $manufacturer = $catalogue->resolveManufacturer($request->string('new_manufacturer')->toString());
            $data['manufacturer_id'] = $manufacturer?->id;
        } elseif (! empty($data['manufacturer_id'])) {
            $manufacturer = Manufacturer::query()->find((int) $data['manufacturer_id']);
            $data['manufacturer_id'] = $manufacturer?->id;
        } else {
            $data['manufacturer_id'] = null;
        }

        if ($creatingModel) {
            if ($manufacturer === null) {
                throw ValidationException::withMessages([
                    'manufacturer_id' => 'Choose or create a manufacturer before adding a model.',
                ]);
            }
            $model = $catalogue->resolveModel($manufacturer, $request->string('new_manufacturer_model')->toString());
            $data['manufacturer_model_id'] = $model?->id;
            $data['manufacturer_id'] = $manufacturer->id;
        } elseif (! empty($data['manufacturer_model_id'])) {
            $model = ManufacturerModel::query()->find((int) $data['manufacturer_model_id']);
            if ($model === null) {
                throw ValidationException::withMessages([
                    'manufacturer_model_id' => 'The selected model is invalid.',
                ]);
            }
            if ($manufacturer !== null && (int) $model->manufacturer_id !== (int) $manufacturer->id) {
                throw ValidationException::withMessages([
                    'manufacturer_model_id' => 'The selected model does not belong to the chosen manufacturer.',
                ]);
            }
            $data['manufacturer_model_id'] = $model->id;
            $data['manufacturer_id'] = $model->manufacturer_id;
        } else {
            $data['manufacturer_model_id'] = null;
        }

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

        $asset->loadMissing('itemType');
        $itemType = $asset->itemType;
        if ($itemType === null) {
            return;
        }

        $allowedIds = CustomFieldDefinition::query()
            ->whereIn('custom_field_set_id', $itemType->fieldSets()->pluck('custom_field_sets.id'))
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->whereIn('id', array_keys($values))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $allowedLookup = array_fill_keys($allowedIds, true);

        foreach ($values as $definitionId => $value) {
            $definitionId = (int) $definitionId;
            if (! isset($allowedLookup[$definitionId])) {
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

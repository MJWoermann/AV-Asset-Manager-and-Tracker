<?php

use App\Http\Controllers\ActiveDepartmentController;
use App\Http\Controllers\Admin\AdminHubController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmailSettingsController;
use App\Http\Controllers\Admin\ExportColumnController;
use App\Http\Controllers\Admin\InboundEmailActionController;
use App\Http\Controllers\Admin\InboundEmailRuleController;
use App\Http\Controllers\Admin\NotificationRecipientController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\OutboundEmailThreadController;
use App\Http\Controllers\Admin\SsoSettingsController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssetCheckoutController;
use App\Http\Controllers\AssetColumnPreferenceController;
use App\Http\Controllers\AssetConnectionBusController;
use App\Http\Controllers\AssetConnectionController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetInterfaceController;
use App\Http\Controllers\AssetListController;
use App\Http\Controllers\AssetMaintenanceLogController;
use App\Http\Controllers\AssetRuPlacementController;
use App\Http\Controllers\AssetShelfTagPrintController;
use App\Http\Controllers\AssetStockController;
use App\Http\Controllers\AssetStockTokenController;
use App\Http\Controllers\ConnectionMapController;
use App\Http\Controllers\ConnectionTypeController;
use App\Http\Controllers\CustomFieldSetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardModulePreferenceController;
use App\Http\Controllers\DataHubController;
use App\Http\Controllers\DepartmentVisibilityPreferenceController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GuestStockController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceHubController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\ManufacturerModelController;
use App\Http\Controllers\ProfileApiTokenController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\RepairProcedureController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RigEventController;
use App\Http\Controllers\RiggingCalculationLogController;
use App\Http\Controllers\RiggingCalculatorController;
use App\Http\Controllers\RiggingController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ShelfTagLayoutController;
use App\Http\Controllers\ShelfTagPrintController;
use App\Http\Controllers\SpareGroupController;
use App\Http\Controllers\SparePartChartController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\SparePartMovementController;
use App\Http\Controllers\SparePartShelfTagPrintController;
use App\Http\Controllers\TrustedExternalDomainPreferenceController;
use App\Http\Controllers\UnknownScanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route(auth()->user()->homeRouteName());
});

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/stock/t/{token}', [GuestStockController::class, 'show'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('stock.guest.show');
    Route::post('/stock/t/{token}', [GuestStockController::class, 'store'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('stock.guest.store');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'role:admin,inventory_manager,scanner'])
    ->name('dashboard');

/*
 * Shared routes for staff and external_repairer.
 * Controllers enforce assignment-only access for external_repairer.
 * Do not add external_repairer to the broad staff group below.
 */
Route::middleware(['auth', 'role:admin,inventory_manager,scanner,external_repairer'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/maintenance', [MaintenanceHubController::class, 'index'])->name('maintenance.index');

    Route::get('/repairs', [RepairController::class, 'index'])->name('repairs.index');
    Route::get('/repairs/{repair}', [RepairController::class, 'show'])->name('repairs.show');
    Route::post('/repairs/{repair}/steps', [RepairController::class, 'storeStep'])
        ->name('repairs.steps.store');
    Route::patch('/repairs/{repair}/steps/{step}', [RepairController::class, 'updateStep'])
        ->name('repairs.steps.update');
    Route::post('/repairs/{repair}/parts', [RepairController::class, 'storePart'])
        ->name('repairs.parts.store');
    Route::post('/repairs/{repair}/complete', [RepairController::class, 'complete'])
        ->name('repairs.complete');

    Route::get('/assets/{asset}', [AssetController::class, 'show'])->whereNumber('asset')->name('assets.show');
});

// Broad staff group — external_repairer is intentionally excluded.
Route::middleware(['auth', 'role:admin,inventory_manager,scanner'])->group(function () {
    // #region agent log
    Route::post('/__debug/agent-log', function (Request $request) {
        $payload = $request->all();
        $payload['sessionId'] = $payload['sessionId'] ?? '114b8c';
        $payload['timestamp'] = $payload['timestamp'] ?? (int) round(microtime(true) * 1000);
        $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $payload['sessionId']) ?: '114b8c';
        file_put_contents(
            base_path("debug-{$sessionId}.log"),
            json_encode($payload, JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND | LOCK_EX
        );

        return response()->noContent();
    })->name('debug.agent-log');
    // #endregion

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/api-tokens', [ProfileApiTokenController::class, 'store'])
        ->name('profile.api-tokens.store');
    Route::delete('/profile/api-tokens/{token}', [ProfileApiTokenController::class, 'destroy'])
        ->name('profile.api-tokens.destroy');
    Route::patch('/preferences/asset-columns', [AssetColumnPreferenceController::class, 'update'])
        ->name('preferences.asset-columns');
    Route::patch('/preferences/dashboard-modules', [DashboardModulePreferenceController::class, 'update'])
        ->name('preferences.dashboard-modules');
    Route::patch('/preferences/active-department', [ActiveDepartmentController::class, 'update'])
        ->name('preferences.active-department');
    Route::patch('/preferences/department-visibility', [DepartmentVisibilityPreferenceController::class, 'update'])
        ->middleware('role:admin')
        ->name('preferences.department-visibility');
    Route::delete('/preferences/trusted-external-domains', [TrustedExternalDomainPreferenceController::class, 'destroy'])
        ->name('preferences.trusted-external-domains.destroy');

    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/create', [AssetController::class, 'create'])->name('assets.create');
    Route::get('/assets/search', [AssetController::class, 'search'])->name('assets.search');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::post('/assets/bulk-update', [AssetController::class, 'bulkUpdate'])->name('assets.bulk-update');
    Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])
        ->middleware('role:admin,inventory_manager')
        ->name('assets.destroy');

    Route::post('/assets/{asset}/checkouts', [AssetCheckoutController::class, 'store'])
        ->name('assets.checkouts.store');
    Route::post('/assets/{asset}/checkouts/{checkout}/return', [AssetCheckoutController::class, 'returnCheckout'])
        ->name('assets.checkouts.return');

    Route::post('/assets/{asset}/maintenance-logs', [AssetMaintenanceLogController::class, 'store'])
        ->name('assets.maintenance-logs.store');
    Route::delete('/assets/{asset}/maintenance-logs/{maintenanceLog}', [AssetMaintenanceLogController::class, 'destroy'])
        ->middleware('role:admin,inventory_manager')
        ->name('assets.maintenance-logs.destroy');

    Route::post('/assets/{asset}/repairs', [RepairController::class, 'storeFromFault'])
        ->name('assets.repairs.store');
    Route::post('/assets/{asset}/repairs/with-fault', [RepairController::class, 'storeWithNewFault'])
        ->name('assets.repairs.store-with-fault');
    Route::post('/spares/{spare}/repairs', [RepairController::class, 'storeFromSpare'])
        ->name('spares.repairs.store');
    Route::post('/repairs/{repair}/cancel', [RepairController::class, 'cancel'])
        ->name('repairs.cancel');

    Route::middleware('role:admin,inventory_manager')->group(function () {
        Route::get('/repair-procedures', [RepairProcedureController::class, 'index'])->name('repair-procedures.index');
        Route::get('/repair-procedures/create', [RepairProcedureController::class, 'create'])->name('repair-procedures.create');
        Route::post('/repair-procedures', [RepairProcedureController::class, 'store'])->name('repair-procedures.store');
        Route::get('/repair-procedures/{repair_procedure}', [RepairProcedureController::class, 'show'])->name('repair-procedures.show');
        Route::get('/repair-procedures/{repair_procedure}/edit', [RepairProcedureController::class, 'edit'])->name('repair-procedures.edit');
        Route::put('/repair-procedures/{repair_procedure}', [RepairProcedureController::class, 'update'])->name('repair-procedures.update');
        Route::delete('/repair-procedures/{repair_procedure}', [RepairProcedureController::class, 'destroy'])->name('repair-procedures.destroy');
    });

    Route::get('/files/search', [FileController::class, 'search'])->name('files.search');
    Route::post('/files/upload', [FileController::class, 'storeUpload'])->name('files.upload');
    Route::post('/files/bulk-upload', [FileController::class, 'storeBulkUpload'])->name('files.bulk-upload');
    Route::post('/files/url', [FileController::class, 'storeUrl'])->name('files.url');
    Route::post('/files/{file}/link', [FileController::class, 'link'])->name('files.link');
    Route::delete('/files/links/{fileLink}', [FileController::class, 'unlink'])->name('files.unlink');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('/files/{file}/preview', [FileController::class, 'preview'])->name('files.preview');
    Route::get('/files/{file}/thumbnail', [FileController::class, 'thumbnail'])->name('files.thumbnail');
    Route::post('/files/{file}/external/continue', [FileController::class, 'continueExternal'])
        ->name('files.external.continue');
    Route::middleware('role:admin,inventory_manager')->group(function () {
        Route::get('/files', [FileController::class, 'index'])->name('files.index');
        Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');
        Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
    });

    Route::get('/data', [DataHubController::class, 'index'])->name('data.index');

    Route::get('/spares', [SparePartController::class, 'index'])->name('spares.index');
    Route::get('/spares/lookup', [SparePartController::class, 'lookup'])->name('spares.lookup');
    Route::get('/spares/c/{code}', [SparePartController::class, 'showByCode'])->name('spares.show-by-code');
    Route::get('/spares/groups', [SpareGroupController::class, 'index'])->name('spares.groups.index');
    Route::middleware('role:admin,inventory_manager')->group(function () {
        Route::get('/spares/create', [SparePartController::class, 'create'])->name('spares.create');
        Route::post('/spares', [SparePartController::class, 'store'])->name('spares.store');
        Route::post('/spares/labels/print', [SparePartShelfTagPrintController::class, 'batch'])->name('spares.labels.print');
        Route::get('/spares/groups/create', [SpareGroupController::class, 'create'])->name('spares.groups.create');
        Route::post('/spares/groups', [SpareGroupController::class, 'store'])->name('spares.groups.store');
        Route::get('/spares/groups/{group}/edit', [SpareGroupController::class, 'edit'])->name('spares.groups.edit');
        Route::put('/spares/groups/{group}', [SpareGroupController::class, 'update'])->name('spares.groups.update');
        Route::delete('/spares/groups/{group}', [SpareGroupController::class, 'destroy'])->name('spares.groups.destroy');
        Route::get('/spares/{spare}/edit', [SparePartController::class, 'edit'])->name('spares.edit');
        Route::put('/spares/{spare}', [SparePartController::class, 'update'])->name('spares.update');
        Route::delete('/spares/{spare}', [SparePartController::class, 'destroy'])->name('spares.destroy');
        Route::post('/spares/{spare}/supersede', [SparePartController::class, 'supersede'])->name('spares.supersede');
        Route::get('/spares/{spare}/label', [SparePartShelfTagPrintController::class, 'label'])->name('spares.label');
    });
    Route::get('/spares/groups/{group}', [SpareGroupController::class, 'show'])->name('spares.groups.show');
    Route::get('/spares/{spare}', [SparePartController::class, 'show'])->name('spares.show');
    Route::get('/spares/{spare}/chart', [SparePartChartController::class, 'show'])->name('spares.chart');
    Route::post('/spares/{spare}/movements', [SparePartMovementController::class, 'store'])
        ->name('spares.movements.store');

    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('/locations/create', [LocationController::class, 'create'])->name('locations.create');
    Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
    Route::get('/manufacturers', [ManufacturerController::class, 'index'])->name('manufacturers.index');
    Route::get('/manufacturers/create', [ManufacturerController::class, 'create'])->name('manufacturers.create');
    Route::post('/manufacturers', [ManufacturerController::class, 'store'])->name('manufacturers.store');
    Route::get('/manufacturers/{manufacturer}/models/create', [ManufacturerModelController::class, 'create'])->name('manufacturers.models.create');
    Route::post('/manufacturers/{manufacturer}/models', [ManufacturerModelController::class, 'store'])->name('manufacturers.models.store');
    Route::middleware('role:admin,inventory_manager')->group(function () {
        Route::post('/locations/bulk-update', [LocationController::class, 'bulkUpdate'])->name('locations.bulk-update');
        Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

        Route::get('/manufacturers/{manufacturer}/edit', [ManufacturerController::class, 'edit'])->name('manufacturers.edit');
        Route::put('/manufacturers/{manufacturer}', [ManufacturerController::class, 'update'])->name('manufacturers.update');
        Route::delete('/manufacturers/{manufacturer}', [ManufacturerController::class, 'destroy'])->name('manufacturers.destroy');
        Route::get('/manufacturers/{manufacturer}/models/{manufacturer_model}/edit', [ManufacturerModelController::class, 'edit'])->name('manufacturers.models.edit');
        Route::put('/manufacturers/{manufacturer}/models/{manufacturer_model}', [ManufacturerModelController::class, 'update'])->name('manufacturers.models.update');
        Route::delete('/manufacturers/{manufacturer}/models/{manufacturer_model}', [ManufacturerModelController::class, 'destroy'])->name('manufacturers.models.destroy');

        Route::resource('item-types', ItemTypeController::class)->except(['show']);
        Route::resource('custom-field-sets', CustomFieldSetController::class)->except(['show']);

        Route::get('/lists/create', [AssetListController::class, 'create'])->name('lists.create');
        Route::post('/lists', [AssetListController::class, 'store'])->name('lists.store');
        Route::get('/lists/{list}/edit', [AssetListController::class, 'edit'])->name('lists.edit');
        Route::put('/lists/{list}', [AssetListController::class, 'update'])->name('lists.update');
        Route::delete('/lists/{list}', [AssetListController::class, 'destroy'])->name('lists.destroy');
    });

    Route::get('/lists', [AssetListController::class, 'index'])->name('lists.index');
    Route::get('/lists/{list}', [AssetListController::class, 'show'])->name('lists.show');

    Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('/scan/start', [ScanController::class, 'start'])->name('scan.start');
    Route::patch('/scan/{scan}/lists', [ScanController::class, 'updateLists'])->name('scan.lists');
    Route::patch('/scan/{scan}/location', [ScanController::class, 'updateLocation'])->name('scan.location');
    Route::post('/scan/{scan}', [ScanController::class, 'scan'])->name('scan.scan');
    Route::post('/scan/{scan}/close', [ScanController::class, 'close'])->name('scan.close');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/compare', [ReportController::class, 'compareForm'])->name('reports.compare');
    Route::post('/reports/compare', [ReportController::class, 'compare'])->name('reports.compare.run');
    Route::get('/reports/unknown-scans', [UnknownScanController::class, 'index'])->name('reports.unknown-scans');
    Route::get('/reports/unknown-scans/export', [UnknownScanController::class, 'export'])->name('reports.unknown-scans.export');
    Route::patch('/unknown-scans/{unknownScan}', [UnknownScanController::class, 'update'])->name('unknown-scans.update');
    Route::post('/unknown-scans/{unknownScan}/link', [UnknownScanController::class, 'link'])->name('unknown-scans.link');
    Route::middleware('role:admin')->group(function () {
        Route::delete('/reports/unknown-scans', [UnknownScanController::class, 'destroyAll'])
            ->name('reports.unknown-scans.destroy-all');
        Route::post('/reports/unknown-scans/deduplicate', [UnknownScanController::class, 'deduplicate'])
            ->name('reports.unknown-scans.deduplicate');
    });

    Route::middleware('role:admin,inventory_manager')->group(function () {
        Route::get('/import', [ImportController::class, 'create'])->name('import.create');
        Route::post('/import/upload', [ImportController::class, 'upload'])->name('import.upload');
        Route::post('/import/prepare', [ImportController::class, 'prepare'])->name('import.prepare');
        Route::post('/import/process', [ImportController::class, 'process'])->name('import.process');
        Route::get('/export/assets', [ExportController::class, 'assets'])->name('export.assets');
        Route::get('/export/lists/{list}', [ExportController::class, 'list'])->name('export.list');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminHubController::class, 'index'])->name('index');
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::get('/export-columns', [ExportColumnController::class, 'edit'])->name('export-columns.edit');
        Route::put('/export-columns', [ExportColumnController::class, 'update'])->name('export-columns.update');
        Route::get('/sso', [SsoSettingsController::class, 'edit'])->name('sso.edit');
        Route::put('/sso', [SsoSettingsController::class, 'update'])->name('sso.update');
        Route::get('/storage', [StorageController::class, 'edit'])->name('storage.edit');
        Route::post('/storage/migrate', [StorageController::class, 'migrate'])->name('storage.migrate');
        Route::post('/storage/backup', [StorageController::class, 'backup'])->name('storage.backup');
    });

    Route::middleware(['feature:email_integration', 'role:admin'])
        ->prefix('admin/email')
        ->name('admin.email.')
        ->group(function () {
            Route::get('/', [EmailSettingsController::class, 'edit'])->name('edit');
            Route::put('/', [EmailSettingsController::class, 'update'])->name('update');
            Route::post('/test', [EmailSettingsController::class, 'test'])
                ->middleware('throttle:6,1')
                ->name('test');

            Route::resource('recipients', NotificationRecipientController::class)->except(['show']);
            Route::resource('templates', NotificationTemplateController::class)->except(['show']);
            Route::get('/templates/{template}/preview', [NotificationTemplateController::class, 'preview'])
                ->name('templates.preview');
            Route::resource('rules', InboundEmailRuleController::class)->except(['show']);
        });

    Route::middleware(['feature:email_integration', 'role:admin,inventory_manager'])
        ->prefix('admin/email')
        ->name('admin.email.')
        ->group(function () {
            Route::get('/inbound', [InboundEmailActionController::class, 'index'])->name('inbound.index');
            Route::post('/inbound/{inboundEmailAction}/apply', [InboundEmailActionController::class, 'apply'])->name('inbound.apply');
            Route::post('/inbound/{inboundEmailAction}/reject', [InboundEmailActionController::class, 'reject'])->name('inbound.reject');
            Route::get('/outbound', [OutboundEmailThreadController::class, 'index'])->name('outbound.index');
        });

    Route::middleware('feature:asset_connections')->group(function () {
        Route::get('/reports/connection-map', [ConnectionMapController::class, 'index'])
            ->name('reports.connection-map');
        Route::get('/assets/{asset}/peer-interfaces/{peer}', [AssetConnectionController::class, 'peerInterfaces'])
            ->name('assets.peer-interfaces');

        Route::middleware('role:admin,inventory_manager')->group(function () {
            Route::post('/assets/{asset}/interfaces', [AssetInterfaceController::class, 'store'])
                ->name('assets.interfaces.store');
            Route::put('/assets/{asset}/interfaces/{interface}', [AssetInterfaceController::class, 'update'])
                ->name('assets.interfaces.update');
            Route::delete('/assets/{asset}/interfaces/{interface}', [AssetInterfaceController::class, 'destroy'])
                ->name('assets.interfaces.destroy');

            Route::post('/assets/{asset}/connections', [AssetConnectionController::class, 'store'])
                ->name('assets.connections.store');
            Route::delete('/assets/{asset}/connections/{connection}', [AssetConnectionController::class, 'destroy'])
                ->name('assets.connections.destroy');

            Route::post('/assets/{asset}/buses', [AssetConnectionBusController::class, 'store'])
                ->name('assets.buses.store');
            Route::delete('/assets/{asset}/interfaces/{interface}/bus', [AssetConnectionBusController::class, 'destroy'])
                ->name('assets.interfaces.bus.destroy');

            Route::resource('connection-types', ConnectionTypeController::class)->except(['show']);
            Route::post('/item-types/{item_type}/sync-interfaces', [ItemTypeController::class, 'syncMissingInterfaces'])
                ->name('item-types.sync-interfaces');

            Route::post('/assets/{asset}/interfaces/apply-templates', [AssetInterfaceController::class, 'applyTemplates'])
                ->name('assets.interfaces.apply-templates');

            Route::post('/manufacturers/{manufacturer}/models/{manufacturer_model}/sync-interfaces', [ManufacturerModelController::class, 'syncMissingInterfaces'])
                ->name('manufacturers.models.sync-interfaces');
        });
    });

    Route::middleware(['developer_feature:ru_visualiser', 'role:admin,inventory_manager'])->group(function () {
        Route::post('/assets/{asset}/ru-placements', [AssetRuPlacementController::class, 'store'])
            ->name('assets.ru-placements.store');
        Route::put('/assets/{asset}/ru-placements/{ruPlacement}', [AssetRuPlacementController::class, 'update'])
            ->name('assets.ru-placements.update');
        Route::patch('/assets/{asset}/ru-placements/{ruPlacement}', [AssetRuPlacementController::class, 'update']);
        Route::delete('/assets/{asset}/ru-placements/{ruPlacement}', [AssetRuPlacementController::class, 'destroy'])
            ->name('assets.ru-placements.destroy');
    });

    Route::middleware(['feature:stock_consumables', 'role:admin,inventory_manager,scanner'])->group(function () {
        Route::post('/assets/{asset}/stock-movements', [AssetStockController::class, 'store'])
            ->name('assets.stock-movements.store');
        Route::patch('/assets/{asset}/stock-levels', [AssetStockController::class, 'updateLevels'])
            ->name('assets.stock-levels.update');
    });

    Route::middleware(['feature:stock_consumables', 'role:admin,inventory_manager'])->group(function () {
        Route::post('/assets/{asset}/stock-tokens', [AssetStockTokenController::class, 'rotate'])
            ->name('assets.stock-tokens.rotate');
        Route::delete('/assets/{asset}/stock-tokens/{stockToken}', [AssetStockTokenController::class, 'revoke'])
            ->name('assets.stock-tokens.revoke');
        Route::get('/assets/{asset}/kanban-tags', [AssetStockController::class, 'printTags'])
            ->name('assets.kanban-tags.print');
    });

    Route::middleware(['feature:shelf_tag_generator', 'role:admin,inventory_manager'])->group(function () {
        Route::get('/assets/{asset}/label', [AssetShelfTagPrintController::class, 'show'])
            ->name('assets.label');
        Route::get('/assets/{asset}/label/pdf', [AssetShelfTagPrintController::class, 'pdf'])
            ->name('assets.label.pdf');
    });

    Route::middleware(['feature:shelf_tag_generator', 'role:admin,inventory_manager'])
        ->prefix('labels')
        ->name('labels.')
        ->group(function () {
            Route::get('/', [ShelfTagPrintController::class, 'create'])->name('index');

            Route::get('/layouts', [ShelfTagLayoutController::class, 'index'])->name('layouts.index');
            Route::get('/layouts/import', [ShelfTagLayoutController::class, 'importForm'])->name('layouts.import');
            Route::post('/layouts/import', [ShelfTagLayoutController::class, 'import'])->name('layouts.import.store');
            Route::get('/layouts/create', [ShelfTagLayoutController::class, 'create'])->name('layouts.create');
            Route::post('/layouts', [ShelfTagLayoutController::class, 'store'])->name('layouts.store');
            Route::get('/layouts/{layout}/edit', [ShelfTagLayoutController::class, 'edit'])->name('layouts.edit');
            Route::put('/layouts/{layout}', [ShelfTagLayoutController::class, 'update'])->name('layouts.update');
            Route::delete('/layouts/{layout}', [ShelfTagLayoutController::class, 'destroy'])->name('layouts.destroy');

            Route::get('/print', [ShelfTagPrintController::class, 'create'])->name('print.create');
            Route::post('/print/preview', [ShelfTagPrintController::class, 'preview'])->name('print.preview');
            Route::get('/print/preview', [ShelfTagPrintController::class, 'show'])->name('print.show');
            Route::get('/print/pdf', [ShelfTagPrintController::class, 'pdf'])->name('print.pdf');
        });

    Route::middleware('feature:rigging')->prefix('rigging')->name('rigging.')->group(function () {
        Route::get('/', [RiggingController::class, 'index'])->name('index');

        Route::resource('events', RigEventController::class);
        Route::post('events/{event}/sign-off', [RigEventController::class, 'signOff'])->name('events.sign-off');
        Route::put('events/{event}/checklist', [RigEventController::class, 'updateChecklist'])->name('events.checklist');
        Route::put('events/{event}/motor-patches', [RigEventController::class, 'syncMotorPatches'])->name('events.motor-patches.sync');
        Route::post('events/{event}/calculations', [RiggingCalculationLogController::class, 'attach'])->name('events.calculations.attach');
        Route::delete('events/{event}/calculations/{log}', [RiggingCalculationLogController::class, 'detach'])->name('events.calculations.detach');

        Route::prefix('calculators')->name('calculators.')->group(function () {
            Route::get('/', [RiggingCalculatorController::class, 'index'])->name('index');
            Route::get('/{calculator}', [RiggingCalculatorController::class, 'show'])->name('show');
            Route::post('/{calculator}/compute', [RiggingCalculatorController::class, 'compute'])->name('compute');
            Route::post('/{calculator}/logs', [RiggingCalculationLogController::class, 'store'])->name('logs.store');
        });
    });
});

require __DIR__.'/auth.php';

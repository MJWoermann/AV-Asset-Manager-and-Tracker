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
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SsoSettingsController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssetCheckoutController;
use App\Http\Controllers\AssetColumnPreferenceController;
use App\Http\Controllers\AssetConnectionBusController;
use App\Http\Controllers\AssetConnectionController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDuplicateController;
use App\Http\Controllers\AssetInterfaceController;
use App\Http\Controllers\AssetListController;
use App\Http\Controllers\AssetMaintenanceLogController;
use App\Http\Controllers\AssetRuPlacementController;
use App\Http\Controllers\AssetShelfTagPrintController;
use App\Http\Controllers\AssetStockController;
use App\Http\Controllers\AssetStockTokenController;
use App\Http\Controllers\CheckoutReportController;
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
    ->middleware(['auth', 'staff', 'permission:reports:read'])
    ->name('dashboard');

/*
 * Shared routes for staff and external_repairer.
 * Controllers enforce assignment-only access for external_repairer.
 * Do not add external_repairer to the broad staff group below.
 */
Route::middleware(['auth', 'operator'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/maintenance', [MaintenanceHubController::class, 'index'])
        ->middleware('permission:repairs:read')
        ->name('maintenance.index');

    Route::get('/repairs', [RepairController::class, 'index'])
        ->middleware('permission:repairs:read')
        ->name('repairs.index');
    Route::get('/repairs/{repair}', [RepairController::class, 'show'])
        ->middleware('permission:repairs:read')
        ->name('repairs.show');
    Route::post('/repairs/{repair}/steps', [RepairController::class, 'storeStep'])
        ->middleware('permission:repairs:write')
        ->name('repairs.steps.store');
    Route::patch('/repairs/{repair}/steps/{step}', [RepairController::class, 'updateStep'])
        ->middleware('permission:repairs:write')
        ->name('repairs.steps.update');
    Route::post('/repairs/{repair}/parts', [RepairController::class, 'storePart'])
        ->middleware('permission:repairs:write')
        ->name('repairs.parts.store');
    Route::post('/repairs/{repair}/complete', [RepairController::class, 'complete'])
        ->middleware('permission:repairs:write')
        ->name('repairs.complete');

    Route::get('/assets/{asset}', [AssetController::class, 'show'])
        ->whereNumber('asset')
        ->middleware('permission:assets:read')
        ->name('assets.show');
});

// Broad staff group — external_repairer is intentionally excluded.
Route::middleware(['auth', 'staff'])->group(function () {
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

    Route::get('/assets', [AssetController::class, 'index'])->middleware('permission:assets:read')->name('assets.index');
    Route::get('/assets/create', [AssetController::class, 'create'])->middleware('permission:assets:write')->name('assets.create');
    Route::get('/assets/search', [AssetController::class, 'search'])->middleware('permission:assets:read')->name('assets.search');
    Route::post('/assets', [AssetController::class, 'store'])->middleware('permission:assets:write')->name('assets.store');
    Route::post('/assets/bulk-update', [AssetController::class, 'bulkUpdate'])->middleware('permission:assets:write')->name('assets.bulk-update');

    Route::middleware('permission:assets:delete')->group(function () {
        Route::get('/assets/duplicates', [AssetDuplicateController::class, 'index'])
            ->name('assets.duplicates.index');
        Route::get('/assets/duplicates/merge', [AssetDuplicateController::class, 'mergeForm'])
            ->name('assets.duplicates.merge');
        Route::post('/assets/duplicates/merge', [AssetDuplicateController::class, 'merge'])
            ->name('assets.duplicates.merge.store');
        Route::post('/assets/duplicates/clear', [AssetDuplicateController::class, 'clearFields'])
            ->name('assets.duplicates.clear');
        Route::post('/assets/duplicates/parent-child', [AssetDuplicateController::class, 'linkParentChildren'])
            ->name('assets.duplicates.parent-child');
        Route::post('/assets/duplicates/start', [AssetDuplicateController::class, 'startFromSelection'])
            ->name('assets.duplicates.start');
    });

    Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->middleware('permission:assets:write')->name('assets.edit');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->middleware('permission:assets:write')->name('assets.update');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])
        ->middleware('permission:assets:delete')
        ->name('assets.destroy');

    Route::post('/assets/{asset}/checkouts', [AssetCheckoutController::class, 'store'])
        ->middleware('permission:assets:write')
        ->name('assets.checkouts.store');
    Route::post('/assets/{asset}/checkouts/{checkout}/return', [AssetCheckoutController::class, 'returnCheckout'])
        ->middleware('permission:assets:write')
        ->name('assets.checkouts.return');

    Route::post('/assets/{asset}/spares', [SparePartController::class, 'storeFromAsset'])
        ->middleware('permission:spares:manage')
        ->name('assets.spares.store');

    Route::post('/assets/{asset}/maintenance-logs', [AssetMaintenanceLogController::class, 'store'])
        ->middleware('permission:assets:write')
        ->name('assets.maintenance-logs.store');
    Route::delete('/assets/{asset}/maintenance-logs/{maintenanceLog}', [AssetMaintenanceLogController::class, 'destroy'])
        ->middleware('permission:assets:delete')
        ->name('assets.maintenance-logs.destroy');

    Route::post('/assets/{asset}/repairs', [RepairController::class, 'storeFromFault'])
        ->middleware('permission:repairs:write')
        ->name('assets.repairs.store');
    Route::post('/assets/{asset}/repairs/with-fault', [RepairController::class, 'storeWithNewFault'])
        ->middleware('permission:repairs:write')
        ->name('assets.repairs.store-with-fault');
    Route::post('/spares/{spare}/repairs', [RepairController::class, 'storeFromSpare'])
        ->middleware('permission:repairs:write')
        ->name('spares.repairs.store');
    Route::post('/repairs/{repair}/cancel', [RepairController::class, 'cancel'])
        ->middleware('permission:repairs:write')
        ->name('repairs.cancel');

    Route::middleware('permission:repairs:procedures')->group(function () {
        Route::get('/repair-procedures', [RepairProcedureController::class, 'index'])->name('repair-procedures.index');
        Route::get('/repair-procedures/create', [RepairProcedureController::class, 'create'])->name('repair-procedures.create');
        Route::post('/repair-procedures', [RepairProcedureController::class, 'store'])->name('repair-procedures.store');
        Route::get('/repair-procedures/{repair_procedure}', [RepairProcedureController::class, 'show'])->name('repair-procedures.show');
        Route::get('/repair-procedures/{repair_procedure}/edit', [RepairProcedureController::class, 'edit'])->name('repair-procedures.edit');
        Route::put('/repair-procedures/{repair_procedure}', [RepairProcedureController::class, 'update'])->name('repair-procedures.update');
        Route::delete('/repair-procedures/{repair_procedure}', [RepairProcedureController::class, 'destroy'])->name('repair-procedures.destroy');
    });

    Route::get('/files/search', [FileController::class, 'search'])->middleware('permission:files:read')->name('files.search');
    Route::post('/files/upload', [FileController::class, 'storeUpload'])->middleware('permission:files:write')->name('files.upload');
    Route::post('/files/bulk-upload', [FileController::class, 'storeBulkUpload'])->middleware('permission:files:write')->name('files.bulk-upload');
    Route::post('/files/url', [FileController::class, 'storeUrl'])->middleware('permission:files:write')->name('files.url');
    Route::post('/files/{file}/link', [FileController::class, 'link'])->middleware('permission:files:write')->name('files.link');
    Route::delete('/files/links/{fileLink}', [FileController::class, 'unlink'])->middleware('permission:files:write')->name('files.unlink');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->middleware('permission:files:read')->name('files.download');
    Route::get('/files/{file}/preview', [FileController::class, 'preview'])->middleware('permission:files:read')->name('files.preview');
    Route::get('/files/{file}/thumbnail', [FileController::class, 'thumbnail'])->middleware('permission:files:read')->name('files.thumbnail');
    Route::post('/files/{file}/external/continue', [FileController::class, 'continueExternal'])
        ->middleware('permission:files:read')
        ->name('files.external.continue');
    Route::middleware('permission:files:manage')->group(function () {
        Route::get('/files', [FileController::class, 'index'])->name('files.index');
        Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');
        Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
    });

    Route::get('/data', [DataHubController::class, 'index'])->middleware('permission:locations:read,catalog:read')->name('data.index');

    Route::get('/spares', [SparePartController::class, 'index'])->middleware('permission:spares:read')->name('spares.index');
    Route::get('/spares/lookup', [SparePartController::class, 'lookup'])->middleware('permission:spares:read')->name('spares.lookup');
    Route::get('/spares/c/{code}', [SparePartController::class, 'showByCode'])->middleware('permission:spares:read')->name('spares.show-by-code');
    Route::get('/spares/groups', [SpareGroupController::class, 'index'])->middleware('permission:spares:read')->name('spares.groups.index');
    Route::middleware('permission:spares:manage')->group(function () {
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
        Route::get('/spares/{spare}/label', [SparePartShelfTagPrintController::class, 'label'])
            ->middleware('permission:labels:read')
            ->name('spares.label');
    });
    Route::get('/spares/groups/{group}', [SpareGroupController::class, 'show'])->middleware('permission:spares:read')->name('spares.groups.show');
    Route::get('/spares/{spare}', [SparePartController::class, 'show'])->middleware('permission:spares:read')->name('spares.show');
    Route::get('/spares/{spare}/chart', [SparePartChartController::class, 'show'])->middleware('permission:spares:read')->name('spares.chart');
    Route::post('/spares/{spare}/movements', [SparePartMovementController::class, 'store'])
        ->middleware('permission:spares:write')
        ->name('spares.movements.store');

    Route::get('/locations', [LocationController::class, 'index'])->middleware('permission:locations:read')->name('locations.index');
    Route::get('/locations/create', [LocationController::class, 'create'])->middleware('permission:locations:write')->name('locations.create');
    Route::post('/locations', [LocationController::class, 'store'])->middleware('permission:locations:write')->name('locations.store');
    Route::get('/manufacturers', [ManufacturerController::class, 'index'])->middleware('permission:catalog:read')->name('manufacturers.index');
    Route::get('/manufacturers/create', [ManufacturerController::class, 'create'])->middleware('permission:catalog:write')->name('manufacturers.create');
    Route::post('/manufacturers', [ManufacturerController::class, 'store'])->middleware('permission:catalog:write')->name('manufacturers.store');
    Route::get('/manufacturers/{manufacturer}/models', [ManufacturerModelController::class, 'index'])->middleware('permission:catalog:read')->name('manufacturers.models.index');
    Route::get('/manufacturers/{manufacturer}/models/create', [ManufacturerModelController::class, 'create'])->middleware('permission:catalog:write')->name('manufacturers.models.create');
    Route::post('/manufacturers/{manufacturer}/models', [ManufacturerModelController::class, 'store'])->middleware('permission:catalog:write')->name('manufacturers.models.store');
    Route::middleware('permission:locations:update')->group(function () {
        Route::post('/locations/bulk-update', [LocationController::class, 'bulkUpdate'])->name('locations.bulk-update');
        Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
    });

    Route::middleware('permission:catalog:update')->group(function () {
        Route::get('/manufacturers/replacement-models', [ManufacturerModelController::class, 'replacementOptions'])
            ->name('manufacturers.replacement-models');
        Route::get('/manufacturers/{manufacturer}/edit', [ManufacturerController::class, 'edit'])->name('manufacturers.edit');
        Route::put('/manufacturers/{manufacturer}', [ManufacturerController::class, 'update'])->name('manufacturers.update');
        Route::delete('/manufacturers/{manufacturer}', [ManufacturerController::class, 'destroy'])->name('manufacturers.destroy');
        Route::get('/manufacturers/{manufacturer}/models/{manufacturer_model}/edit', [ManufacturerModelController::class, 'edit'])->name('manufacturers.models.edit');
        Route::put('/manufacturers/{manufacturer}/models/{manufacturer_model}', [ManufacturerModelController::class, 'update'])->name('manufacturers.models.update');
        Route::delete('/manufacturers/{manufacturer}/models/{manufacturer_model}', [ManufacturerModelController::class, 'destroy'])->name('manufacturers.models.destroy');

        Route::post('/item-types/bulk-update', [ItemTypeController::class, 'bulkUpdate'])
            ->middleware('permission:catalog:bulk')
            ->name('item-types.bulk-update');
        Route::resource('item-types', ItemTypeController::class)->except(['show']);
        Route::resource('custom-field-sets', CustomFieldSetController::class)->except(['show']);
    });

    Route::middleware('permission:lists:write')->group(function () {
        Route::get('/lists/create', [AssetListController::class, 'create'])->name('lists.create');
        Route::post('/lists', [AssetListController::class, 'store'])->name('lists.store');
        Route::get('/lists/{list}/edit', [AssetListController::class, 'edit'])->name('lists.edit');
        Route::put('/lists/{list}', [AssetListController::class, 'update'])->name('lists.update');
        Route::delete('/lists/{list}', [AssetListController::class, 'destroy'])->name('lists.destroy');
    });

    Route::get('/lists', [AssetListController::class, 'index'])->middleware('permission:lists:read')->name('lists.index');
    Route::post('/lists/bulk-update', [AssetListController::class, 'bulkUpdate'])
        ->middleware('permission:lists:bulk')
        ->name('lists.bulk-update');
    Route::get('/lists/{list}', [AssetListController::class, 'show'])->middleware('permission:lists:read')->name('lists.show');

    Route::get('/scan', [ScanController::class, 'index'])->middleware('permission:scan,scan-sessions')->name('scan.index');
    Route::post('/scan/start', [ScanController::class, 'start'])->middleware('permission:scan-sessions')->name('scan.start');
    Route::post('/scan/parent/{asset}', [ScanController::class, 'prepareParent'])->middleware('permission:scan-sessions')->name('scan.prepare-parent');
    Route::patch('/scan/{scan}/lists', [ScanController::class, 'updateLists'])->middleware('permission:scan-sessions')->name('scan.lists');
    Route::patch('/scan/{scan}/location', [ScanController::class, 'updateLocation'])->middleware('permission:scan-sessions')->name('scan.location');
    Route::post('/scan/{scan}', [ScanController::class, 'scan'])->middleware('permission:scan')->name('scan.scan');
    Route::post('/scan/{scan}/close', [ScanController::class, 'close'])->middleware('permission:scan-sessions')->name('scan.close');

    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports:read')->name('reports.index');
    Route::get('/reports/compare', [ReportController::class, 'compareForm'])->middleware('permission:lists:compare')->name('reports.compare');
    Route::post('/reports/compare', [ReportController::class, 'compare'])
        ->middleware(['permission:lists:compare', 'throttle:compare'])
        ->name('reports.compare.run');
    Route::middleware('permission:reports:export')->group(function () {
        Route::get('/reports/checkouts', [CheckoutReportController::class, 'index'])->name('reports.checkouts');
        Route::get('/reports/checkouts/export', [CheckoutReportController::class, 'export'])
            ->middleware('throttle:export')
            ->name('reports.checkouts.export');
        Route::post('/reports/checkouts/purposes', [CheckoutReportController::class, 'storePurpose'])
            ->name('reports.checkouts.purposes.store');
        Route::patch('/reports/checkouts/purposes/{checkoutPurpose}', [CheckoutReportController::class, 'updatePurpose'])
            ->name('reports.checkouts.purposes.update');
        Route::delete('/reports/checkouts/purposes/{checkoutPurpose}', [CheckoutReportController::class, 'destroyPurpose'])
            ->name('reports.checkouts.purposes.destroy');
    });
    Route::get('/reports/unknown-scans', [UnknownScanController::class, 'index'])->middleware('permission:reports:read')->name('reports.unknown-scans');
    Route::get('/reports/unknown-scans/export', [UnknownScanController::class, 'export'])
        ->middleware(['permission:reports:read', 'throttle:export'])
        ->name('reports.unknown-scans.export');
    Route::patch('/unknown-scans/{unknownScan}', [UnknownScanController::class, 'update'])->middleware('permission:reports:write')->name('unknown-scans.update');
    Route::post('/unknown-scans/{unknownScan}/link', [UnknownScanController::class, 'link'])->middleware('permission:reports:write')->name('unknown-scans.link');
    Route::middleware('permission:reports:purge')->group(function () {
        Route::delete('/reports/unknown-scans', [UnknownScanController::class, 'destroyAll'])
            ->name('reports.unknown-scans.destroy-all');
        Route::post('/reports/unknown-scans/deduplicate', [UnknownScanController::class, 'deduplicate'])
            ->name('reports.unknown-scans.deduplicate');
    });

    Route::middleware('permission:import:run')->group(function () {
        Route::get('/import', [ImportController::class, 'create'])->name('import.create');
        Route::post('/import/upload', [ImportController::class, 'upload'])
            ->middleware('throttle:import')
            ->name('import.upload');
        Route::post('/import/prepare', [ImportController::class, 'prepare'])
            ->middleware('throttle:import')
            ->name('import.prepare');
        Route::post('/import/process', [ImportController::class, 'process'])
            ->middleware('throttle:import')
            ->name('import.process');
    });
    Route::match(['get', 'post'], '/export/assets', [ExportController::class, 'assets'])
        ->middleware(['permission:reports:export', 'throttle:export'])
        ->name('export.assets');
    Route::get('/export/lists/{list}', [ExportController::class, 'list'])
        ->middleware(['permission:reports:export', 'throttle:export'])
        ->name('export.list');

    Route::middleware('permission:admin:read,admin:write,admin:settings')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminHubController::class, 'index'])->name('index');
        Route::middleware('permission:admin:write')->group(function () {
            Route::resource('roles', RoleController::class)->except(['show']);
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
            Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
            Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
            Route::patch('/departments/row-colours', [DepartmentController::class, 'updateRowColours'])
                ->name('departments.row-colours');
        });
        Route::middleware('permission:admin:read')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        });
        Route::middleware('permission:admin:settings')->group(function () {
            Route::get('/export-columns', [ExportColumnController::class, 'edit'])->name('export-columns.edit');
            Route::put('/export-columns', [ExportColumnController::class, 'update'])->name('export-columns.update');
            Route::get('/sso', [SsoSettingsController::class, 'edit'])->name('sso.edit');
            Route::put('/sso', [SsoSettingsController::class, 'update'])->name('sso.update');
            Route::get('/storage', [StorageController::class, 'edit'])->name('storage.edit');
            Route::post('/storage/migrate', [StorageController::class, 'migrate'])
                ->middleware('throttle:backup')
                ->name('storage.migrate');
            Route::post('/storage/backup', [StorageController::class, 'backup'])
                ->middleware('throttle:backup')
                ->name('storage.backup');
        });
    });

    Route::middleware(['feature:email_integration', 'permission:admin:settings'])
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

    Route::middleware(['feature:email_integration', 'permission:email:ops'])
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
            ->middleware('permission:connections:read')
            ->name('reports.connection-map');
        Route::get('/assets/{asset}/peer-interfaces/{peer}', [AssetConnectionController::class, 'peerInterfaces'])
            ->middleware('permission:connections:read')
            ->name('assets.peer-interfaces');

        Route::middleware('permission:connections:write')->group(function () {
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

    Route::middleware(['developer_feature:ru_visualiser', 'permission:connections:write'])->group(function () {
        Route::post('/assets/{asset}/ru-placements', [AssetRuPlacementController::class, 'store'])
            ->name('assets.ru-placements.store');
        Route::put('/assets/{asset}/ru-placements/{ruPlacement}', [AssetRuPlacementController::class, 'update'])
            ->name('assets.ru-placements.update');
        Route::patch('/assets/{asset}/ru-placements/{ruPlacement}', [AssetRuPlacementController::class, 'update']);
        Route::delete('/assets/{asset}/ru-placements/{ruPlacement}', [AssetRuPlacementController::class, 'destroy'])
            ->name('assets.ru-placements.destroy');
    });

    Route::middleware(['feature:stock_consumables', 'permission:stock:write'])->group(function () {
        Route::post('/assets/{asset}/stock-movements', [AssetStockController::class, 'store'])
            ->name('assets.stock-movements.store');
        Route::patch('/assets/{asset}/stock-levels', [AssetStockController::class, 'updateLevels'])
            ->name('assets.stock-levels.update');
    });

    Route::middleware(['feature:stock_consumables', 'permission:stock:adjust'])->group(function () {
        Route::patch('/assets/{asset}/stock-movements/{stockMovement}', [AssetStockController::class, 'update'])
            ->name('assets.stock-movements.update')
            ->scopeBindings();
        Route::delete('/assets/{asset}/stock-movements/{stockMovement}', [AssetStockController::class, 'destroy'])
            ->name('assets.stock-movements.destroy')
            ->scopeBindings();
    });

    Route::middleware(['feature:stock_consumables', 'permission:stock:manage'])->group(function () {
        Route::post('/assets/{asset}/stock-tokens', [AssetStockTokenController::class, 'rotate'])
            ->name('assets.stock-tokens.rotate');
        Route::delete('/assets/{asset}/stock-tokens/{stockToken}', [AssetStockTokenController::class, 'revoke'])
            ->name('assets.stock-tokens.revoke');
        Route::get('/assets/{asset}/kanban-tags', [AssetStockController::class, 'printTags'])
            ->name('assets.kanban-tags.print');
    });

    Route::middleware(['feature:shelf_tag_generator', 'permission:labels:read'])->group(function () {
        Route::get('/assets/{asset}/label', [AssetShelfTagPrintController::class, 'show'])
            ->name('assets.label');
        Route::get('/assets/{asset}/label/pdf', [AssetShelfTagPrintController::class, 'pdf'])
            ->name('assets.label.pdf');
    });

    Route::middleware(['feature:shelf_tag_generator', 'permission:labels:read'])
        ->prefix('labels')
        ->name('labels.')
        ->group(function () {
            Route::get('/', [ShelfTagPrintController::class, 'create'])->name('index');

            Route::get('/layouts', [ShelfTagLayoutController::class, 'index'])->name('layouts.index');
            Route::get('/layouts/import', [ShelfTagLayoutController::class, 'importForm'])->middleware('permission:labels:write')->name('layouts.import');
            Route::post('/layouts/import', [ShelfTagLayoutController::class, 'import'])->middleware('permission:labels:write')->name('layouts.import.store');
            Route::get('/layouts/logo-library', [ShelfTagLayoutController::class, 'logoLibrary'])->middleware('permission:labels:write')->name('layouts.logo-library');
            Route::get('/layouts/logo-files/{file}', [ShelfTagLayoutController::class, 'showLogoFile'])->name('layouts.logo-file');
            Route::get('/layouts/create', [ShelfTagLayoutController::class, 'create'])->middleware('permission:labels:write')->name('layouts.create');
            Route::post('/layouts', [ShelfTagLayoutController::class, 'store'])->middleware('permission:labels:write')->name('layouts.store');
            Route::get('/layouts/{layout}/edit', [ShelfTagLayoutController::class, 'edit'])->middleware('permission:labels:write')->name('layouts.edit');
            Route::put('/layouts/{layout}', [ShelfTagLayoutController::class, 'update'])->middleware('permission:labels:write')->name('layouts.update');
            Route::delete('/layouts/{layout}', [ShelfTagLayoutController::class, 'destroy'])->middleware('permission:labels:write')->name('layouts.destroy');

            Route::get('/print', [ShelfTagPrintController::class, 'create'])->name('print.create');
            Route::post('/print/preview', [ShelfTagPrintController::class, 'preview'])->name('print.preview');
            Route::get('/print/preview', [ShelfTagPrintController::class, 'show'])->name('print.show');
            Route::get('/print/pdf', [ShelfTagPrintController::class, 'pdf'])->name('print.pdf');
        });

    Route::middleware(['feature:rigging', 'permission:rigging:read'])->prefix('rigging')->name('rigging.')->group(function () {
        Route::get('/', [RiggingController::class, 'index'])->name('index');

        Route::resource('events', RigEventController::class)->middleware('permission:rigging:write')->except(['index', 'show']);
        Route::get('events', [RigEventController::class, 'index'])->name('events.index');
        Route::get('events/{event}', [RigEventController::class, 'show'])->name('events.show');
        Route::post('events/{event}/sign-off', [RigEventController::class, 'signOff'])->middleware('permission:rigging:write')->name('events.sign-off');
        Route::put('events/{event}/checklist', [RigEventController::class, 'updateChecklist'])->middleware('permission:rigging:write')->name('events.checklist');
        Route::put('events/{event}/motor-patches', [RigEventController::class, 'syncMotorPatches'])->middleware('permission:rigging:write')->name('events.motor-patches.sync');
        Route::post('events/{event}/calculations', [RiggingCalculationLogController::class, 'attach'])->middleware('permission:rigging:write')->name('events.calculations.attach');
        Route::delete('events/{event}/calculations/{log}', [RiggingCalculationLogController::class, 'detach'])->middleware('permission:rigging:write')->name('events.calculations.detach');

        Route::prefix('calculators')->name('calculators.')->group(function () {
            Route::get('/', [RiggingCalculatorController::class, 'index'])->name('index');
            Route::get('/{calculator}', [RiggingCalculatorController::class, 'show'])->name('show');
            Route::post('/{calculator}/compute', [RiggingCalculatorController::class, 'compute'])->name('compute');
            Route::post('/{calculator}/logs', [RiggingCalculationLogController::class, 'store'])->middleware('permission:rigging:write')->name('logs.store');
        });
    });
});

require __DIR__.'/auth.php';

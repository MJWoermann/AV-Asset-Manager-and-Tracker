<?php

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
use App\Http\Controllers\AssetColumnPreferenceController;
use App\Http\Controllers\AssetConnectionController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetInterfaceController;
use App\Http\Controllers\AssetListController;
use App\Http\Controllers\AssetMaintenanceLogController;
use App\Http\Controllers\AssetRuPlacementController;
use App\Http\Controllers\AssetStockController;
use App\Http\Controllers\AssetStockTokenController;
use App\Http\Controllers\ConnectionMapController;
use App\Http\Controllers\ConnectionTypeController;
use App\Http\Controllers\CustomFieldSetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GuestStockController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ShelfTagLayoutController;
use App\Http\Controllers\ShelfTagPrintController;
use App\Http\Controllers\UnknownScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
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

Route::middleware(['auth', 'role:admin,inventory_manager,scanner'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/preferences/asset-columns', [AssetColumnPreferenceController::class, 'update'])
        ->name('preferences.asset-columns');

    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/create', [AssetController::class, 'create'])->name('assets.create');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::post('/assets/bulk-update', [AssetController::class, 'bulkUpdate'])->name('assets.bulk-update');
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
    Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])
        ->middleware('role:admin,inventory_manager')
        ->name('assets.destroy');

    Route::post('/assets/{asset}/maintenance-logs', [AssetMaintenanceLogController::class, 'store'])
        ->name('assets.maintenance-logs.store');
    Route::delete('/assets/{asset}/maintenance-logs/{maintenanceLog}', [AssetMaintenanceLogController::class, 'destroy'])
        ->middleware('role:admin,inventory_manager')
        ->name('assets.maintenance-logs.destroy');

    Route::get('/files/search', [FileController::class, 'search'])->name('files.search');
    Route::post('/files/upload', [FileController::class, 'storeUpload'])->name('files.upload');
    Route::post('/files/url', [FileController::class, 'storeUrl'])->name('files.url');
    Route::post('/files/{file}/link', [FileController::class, 'link'])->name('files.link');
    Route::delete('/files/links/{fileLink}', [FileController::class, 'unlink'])->name('files.unlink');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('/files/{file}/preview', [FileController::class, 'preview'])->name('files.preview');
    Route::get('/files/{file}/thumbnail', [FileController::class, 'thumbnail'])->name('files.thumbnail');
    Route::middleware('role:admin,inventory_manager')->group(function () {
        Route::get('/files', [FileController::class, 'index'])->name('files.index');
        Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');
        Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
    });

    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::middleware('role:admin,inventory_manager')->group(function () {
        Route::get('/locations/create', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::post('/locations/bulk-update', [LocationController::class, 'bulkUpdate'])->name('locations.bulk-update');
        Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

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

    Route::get('/reports/compare', [ReportController::class, 'compareForm'])->name('reports.compare');
    Route::post('/reports/compare', [ReportController::class, 'compare'])->name('reports.compare.run');
    Route::get('/reports/unknown-scans', [UnknownScanController::class, 'index'])->name('reports.unknown-scans');
    Route::get('/reports/unknown-scans/export', [UnknownScanController::class, 'export'])->name('reports.unknown-scans.export');
    Route::patch('/unknown-scans/{unknownScan}', [UnknownScanController::class, 'update'])->name('unknown-scans.update');
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
        Route::resource('users', UserController::class)->except(['show']);
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

            Route::resource('connection-types', ConnectionTypeController::class)->except(['show']);
            Route::post('/item-types/{item_type}/sync-interfaces', [ItemTypeController::class, 'syncMissingInterfaces'])
                ->name('item-types.sync-interfaces');
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

    Route::middleware(['feature:shelf_tag_generator', 'role:admin,inventory_manager'])
        ->prefix('shelf-tags')
        ->name('shelf-tags.')
        ->group(function () {
            Route::get('/layouts', [ShelfTagLayoutController::class, 'index'])->name('layouts.index');
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
});

require __DIR__.'/auth.php';

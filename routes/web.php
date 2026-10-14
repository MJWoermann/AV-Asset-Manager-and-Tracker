<?php

use App\Http\Controllers\Admin\ExportColumnController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssetColumnPreferenceController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetListController;
use App\Http\Controllers\CustomFieldSetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\UnknownScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
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

    Route::get('/files/search', [FileController::class, 'search'])->name('files.search');
    Route::post('/files/upload', [FileController::class, 'storeUpload'])->name('files.upload');
    Route::post('/files/url', [FileController::class, 'storeUrl'])->name('files.url');
    Route::post('/files/{file}/link', [FileController::class, 'link'])->name('files.link');
    Route::delete('/files/links/{fileLink}', [FileController::class, 'unlink'])->name('files.unlink');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('/files/{file}/thumbnail', [FileController::class, 'thumbnail'])->name('files.thumbnail');
    Route::middleware('role:admin,inventory_manager')->group(function () {
        Route::get('/files', [FileController::class, 'index'])->name('files.index');
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
    });
});

require __DIR__.'/auth.php';

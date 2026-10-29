<?php

use App\Http\Controllers\Api\Admin\DepartmentApiController;
use App\Http\Controllers\Api\Admin\InboundEmailActionApiController;
use App\Http\Controllers\Api\Admin\UserApiController;
use App\Http\Controllers\Api\AssetApiController;
use App\Http\Controllers\Api\AssetCheckoutApiController;
use App\Http\Controllers\Api\AssetConnectionApiController;
use App\Http\Controllers\Api\AssetConnectionBusApiController;
use App\Http\Controllers\Api\AssetInterfaceApiController;
use App\Http\Controllers\Api\AssetListApiController;
use App\Http\Controllers\Api\AssetStockApiController;
use App\Http\Controllers\Api\ConnectionMapApiController;
use App\Http\Controllers\Api\ConnectionTypeApiController;
use App\Http\Controllers\Api\CustomFieldSetApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ExportApiController;
use App\Http\Controllers\Api\FileApiController;
use App\Http\Controllers\Api\ImportApiController;
use App\Http\Controllers\Api\ItemTypeApiController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Api\ManufacturerApiController;
use App\Http\Controllers\Api\ManufacturerModelApiController;
use App\Http\Controllers\Api\MeApiController;
use App\Http\Controllers\Api\RepairApiController;
use App\Http\Controllers\Api\RepairProcedureApiController;
use App\Http\Controllers\Api\RiggingApiController;
use App\Http\Controllers\Api\ScanSessionApiController;
use App\Http\Controllers\Api\ShelfTagLayoutApiController;
use App\Http\Controllers\Api\SpareGroupApiController;
use App\Http\Controllers\Api\SparePartApiController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UnknownScanApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [TokenController::class, 'store'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'staff'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    })->middleware(['permission:user:read', 'abilities:user:read']);

    Route::delete('/auth/token', [TokenController::class, 'destroy']);

    Route::patch('/me/active-department', [MeApiController::class, 'updateActiveDepartment'])
        ->middleware(['permission:user:read', 'abilities:user:read']);

    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])
        ->middleware(['permission:reports:read', 'abilities:reports:read']);

    // Assets
    Route::get('/assets', [AssetApiController::class, 'index'])->middleware(['permission:assets:read', 'abilities:assets:read']);
    Route::get('/assets/{asset}', [AssetApiController::class, 'show'])->middleware(['permission:assets:read', 'abilities:assets:read']);
    Route::get('/assets/{asset}/history', [AssetApiController::class, 'history'])->middleware(['permission:audit:read', 'abilities:audit:read']);
    Route::post('/assets', [AssetApiController::class, 'store'])->middleware(['permission:assets:write', 'abilities:assets:write']);
    Route::put('/assets/{asset}', [AssetApiController::class, 'update'])->middleware(['permission:assets:write', 'abilities:assets:write']);
    Route::delete('/assets/{asset}', [AssetApiController::class, 'destroy'])->middleware(['permission:assets:delete', 'abilities:assets:write']);
    Route::post('/assets/bulk-update', [AssetApiController::class, 'bulkUpdate'])->middleware(['permission:assets:write', 'abilities:assets:write']);
    Route::post('/assets/{asset}/maintenance-logs', [AssetApiController::class, 'storeMaintenanceLog'])
        ->middleware(['permission:assets:write', 'abilities:assets:write']);
    Route::delete('/assets/{asset}/maintenance-logs/{maintenanceLog}', [AssetApiController::class, 'destroyMaintenanceLog'])
        ->middleware(['permission:assets:delete', 'abilities:assets:write']);

    Route::post('/assets/{asset}/checkouts', [AssetCheckoutApiController::class, 'store'])
        ->middleware(['permission:assets:write', 'abilities:assets:write']);
    Route::post('/assets/{asset}/checkouts/{checkout}/return', [AssetCheckoutApiController::class, 'returnCheckout'])
        ->middleware(['permission:assets:write', 'abilities:assets:write']);

    // Lists & scan
    Route::get('/lists', [AssetListApiController::class, 'index'])->middleware(['permission:lists:read', 'abilities:lists:read']);
    Route::get('/lists/{list}', [AssetListApiController::class, 'show'])->middleware(['permission:lists:read', 'abilities:lists:read']);
    Route::post('/lists', [AssetListApiController::class, 'store'])->middleware(['permission:lists:write', 'abilities:lists:write']);
    Route::put('/lists/{list}', [AssetListApiController::class, 'update'])->middleware(['permission:lists:write', 'abilities:lists:write']);
    Route::delete('/lists/{list}', [AssetListApiController::class, 'destroy'])->middleware(['permission:lists:write', 'abilities:lists:write']);
    Route::post('/lists/compare', [AssetListApiController::class, 'compare'])
        ->middleware(['permission:lists:compare', 'abilities:lists:compare', 'throttle:compare']);
    Route::post('/scan', [AssetListApiController::class, 'scan'])->middleware(['permission:scan', 'abilities:scan', 'throttle:60,1']);

    Route::get('/scan-sessions/active', [ScanSessionApiController::class, 'active'])->middleware(['permission:scan-sessions', 'abilities:scan-sessions']);
    Route::post('/scan-sessions', [ScanSessionApiController::class, 'store'])->middleware(['permission:scan-sessions', 'abilities:scan-sessions']);
    Route::get('/scan-sessions/{scanSession}', [ScanSessionApiController::class, 'show'])->middleware(['permission:scan-sessions', 'abilities:scan-sessions']);
    Route::patch('/scan-sessions/{scanSession}/lists', [ScanSessionApiController::class, 'updateLists'])->middleware(['permission:scan-sessions', 'abilities:scan-sessions']);
    Route::patch('/scan-sessions/{scanSession}/location', [ScanSessionApiController::class, 'updateLocation'])->middleware(['permission:scan-sessions', 'abilities:scan-sessions']);
    Route::post('/scan-sessions/{scanSession}/close', [ScanSessionApiController::class, 'close'])->middleware(['permission:scan-sessions', 'abilities:scan-sessions']);

    // Locations
    Route::get('/locations', [LocationApiController::class, 'index'])->middleware(['permission:locations:read', 'abilities:locations:read']);
    Route::get('/locations/{location}', [LocationApiController::class, 'show'])->middleware(['permission:locations:read', 'abilities:locations:read']);
    Route::post('/locations', [LocationApiController::class, 'store'])->middleware(['permission:locations:write', 'abilities:locations:write']);
    Route::put('/locations/{location}', [LocationApiController::class, 'update'])->middleware(['permission:locations:update', 'abilities:locations:write']);
    Route::delete('/locations/{location}', [LocationApiController::class, 'destroy'])->middleware(['permission:locations:update', 'abilities:locations:write']);
    Route::post('/locations/bulk-update', [LocationApiController::class, 'bulkUpdate'])->middleware(['permission:locations:update', 'abilities:locations:write']);

    // Catalogue
    Route::get('/manufacturers', [ManufacturerApiController::class, 'index'])->middleware(['permission:catalog:read', 'abilities:catalog:read']);
    Route::get('/manufacturers/{manufacturer}', [ManufacturerApiController::class, 'show'])->middleware(['permission:catalog:read', 'abilities:catalog:read']);
    Route::post('/manufacturers', [ManufacturerApiController::class, 'store'])->middleware(['permission:catalog:write', 'abilities:catalog:write']);
    Route::put('/manufacturers/{manufacturer}', [ManufacturerApiController::class, 'update'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::delete('/manufacturers/{manufacturer}', [ManufacturerApiController::class, 'destroy'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::post('/manufacturers/{manufacturer}/models', [ManufacturerModelApiController::class, 'store'])->middleware(['permission:catalog:write', 'abilities:catalog:write']);
    Route::put('/manufacturers/{manufacturer}/models/{manufacturerModel}', [ManufacturerModelApiController::class, 'update'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::delete('/manufacturers/{manufacturer}/models/{manufacturerModel}', [ManufacturerModelApiController::class, 'destroy'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::post('/manufacturers/{manufacturer}/models/{manufacturerModel}/sync-interfaces', [ManufacturerModelApiController::class, 'syncInterfaces'])
        ->middleware(['permission:connections:write', 'abilities:catalog:write', 'feature:asset_connections']);

    Route::get('/item-types', [ItemTypeApiController::class, 'index'])->middleware(['permission:catalog:read', 'abilities:catalog:read']);
    Route::get('/item-types/{itemType}', [ItemTypeApiController::class, 'show'])->middleware(['permission:catalog:read', 'abilities:catalog:read']);
    Route::post('/item-types', [ItemTypeApiController::class, 'store'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::put('/item-types/{itemType}', [ItemTypeApiController::class, 'update'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::delete('/item-types/{itemType}', [ItemTypeApiController::class, 'destroy'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::post('/item-types/{itemType}/sync-interfaces', [ItemTypeApiController::class, 'syncInterfaces'])
        ->middleware(['permission:connections:write', 'abilities:catalog:write', 'feature:asset_connections']);

    Route::get('/custom-field-sets', [CustomFieldSetApiController::class, 'index'])->middleware(['permission:catalog:read', 'abilities:catalog:read']);
    Route::get('/custom-field-sets/{customFieldSet}', [CustomFieldSetApiController::class, 'show'])->middleware(['permission:catalog:read', 'abilities:catalog:read']);
    Route::post('/custom-field-sets', [CustomFieldSetApiController::class, 'store'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::put('/custom-field-sets/{customFieldSet}', [CustomFieldSetApiController::class, 'update'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);
    Route::delete('/custom-field-sets/{customFieldSet}', [CustomFieldSetApiController::class, 'destroy'])->middleware(['permission:catalog:update', 'abilities:catalog:write']);

    // Spares (static paths before /spares/{spare})
    Route::get('/spares', [SparePartApiController::class, 'index'])->middleware(['permission:spares:read', 'abilities:spares:read']);
    Route::get('/spares/lookup', [SparePartApiController::class, 'lookup'])->middleware(['permission:spares:read', 'abilities:spares:read']);
    Route::get('/spares/c/{code}', [SparePartApiController::class, 'showByCode'])->middleware(['permission:spares:read', 'abilities:spares:read']);
    Route::get('/spares/groups', [SpareGroupApiController::class, 'index'])->middleware(['permission:spares:read', 'abilities:spares:read']);
    Route::post('/spares/groups', [SpareGroupApiController::class, 'store'])->middleware(['permission:spares:manage', 'abilities:spares:write']);
    Route::get('/spares/groups/{group}', [SpareGroupApiController::class, 'show'])->middleware(['permission:spares:read', 'abilities:spares:read']);
    Route::put('/spares/groups/{group}', [SpareGroupApiController::class, 'update'])->middleware(['permission:spares:manage', 'abilities:spares:write']);
    Route::delete('/spares/groups/{group}', [SpareGroupApiController::class, 'destroy'])->middleware(['permission:spares:manage', 'abilities:spares:write']);
    Route::get('/spares/{spare}', [SparePartApiController::class, 'show'])->middleware(['permission:spares:read', 'abilities:spares:read']);
    Route::get('/spares/{spare}/chart', [SparePartApiController::class, 'chart'])->middleware(['permission:spares:read', 'abilities:spares:read']);
    Route::post('/spares', [SparePartApiController::class, 'store'])->middleware(['permission:spares:manage', 'abilities:spares:write']);
    Route::put('/spares/{spare}', [SparePartApiController::class, 'update'])->middleware(['permission:spares:manage', 'abilities:spares:write']);
    Route::delete('/spares/{spare}', [SparePartApiController::class, 'destroy'])->middleware(['permission:spares:manage', 'abilities:spares:write']);
    Route::post('/spares/{spare}/supersede', [SparePartApiController::class, 'supersede'])->middleware(['permission:spares:manage', 'abilities:spares:write']);
    Route::post('/spares/{spare}/movements', [SparePartApiController::class, 'storeMovement'])->middleware(['permission:spares:write', 'abilities:spares:write']);

    // Repairs
    Route::get('/repairs', [RepairApiController::class, 'index'])->middleware(['permission:repairs:read', 'abilities:repairs:read']);
    Route::get('/repairs/{repair}', [RepairApiController::class, 'show'])->middleware(['permission:repairs:read', 'abilities:repairs:read']);
    Route::post('/assets/{asset}/repairs', [RepairApiController::class, 'storeFromFault'])->middleware(['permission:repairs:write', 'abilities:repairs:write']);
    Route::post('/assets/{asset}/repairs/with-fault', [RepairApiController::class, 'storeWithNewFault'])->middleware(['permission:repairs:write', 'abilities:repairs:write']);
    Route::post('/spares/{spare}/repairs', [RepairApiController::class, 'storeFromSpare'])->middleware(['permission:repairs:write', 'abilities:repairs:write']);
    Route::post('/repairs/{repair}/steps', [RepairApiController::class, 'storeStep'])->middleware(['permission:repairs:write', 'abilities:repairs:write']);
    Route::patch('/repairs/{repair}/steps/{step}', [RepairApiController::class, 'updateStep'])->middleware(['permission:repairs:write', 'abilities:repairs:write']);
    Route::post('/repairs/{repair}/parts', [RepairApiController::class, 'storePart'])->middleware(['permission:repairs:write', 'abilities:repairs:write']);
    Route::post('/repairs/{repair}/complete', [RepairApiController::class, 'complete'])->middleware(['permission:repairs:write', 'abilities:repairs:write']);
    Route::post('/repairs/{repair}/cancel', [RepairApiController::class, 'cancel'])->middleware(['permission:repairs:write', 'abilities:repairs:write']);

    Route::get('/repair-procedures', [RepairProcedureApiController::class, 'index'])->middleware(['permission:repairs:procedures', 'abilities:repairs:read']);
    Route::get('/repair-procedures/{repairProcedure}', [RepairProcedureApiController::class, 'show'])->middleware(['permission:repairs:procedures', 'abilities:repairs:read']);
    Route::post('/repair-procedures', [RepairProcedureApiController::class, 'store'])->middleware(['permission:repairs:procedures', 'abilities:repairs:write']);
    Route::put('/repair-procedures/{repairProcedure}', [RepairProcedureApiController::class, 'update'])->middleware(['permission:repairs:procedures', 'abilities:repairs:write']);
    Route::delete('/repair-procedures/{repairProcedure}', [RepairProcedureApiController::class, 'destroy'])->middleware(['permission:repairs:procedures', 'abilities:repairs:write']);

    // Reports / exports
    Route::get('/unknown-scans', [UnknownScanApiController::class, 'index'])->middleware(['permission:reports:read', 'abilities:reports:read']);
    Route::get('/unknown-scans/export', [UnknownScanApiController::class, 'export'])
        ->middleware(['permission:reports:read', 'abilities:reports:read', 'throttle:export']);
    Route::patch('/unknown-scans/{unknownScan}', [UnknownScanApiController::class, 'update'])->middleware(['permission:reports:write', 'abilities:reports:write']);
    Route::post('/unknown-scans/{unknownScan}/link', [UnknownScanApiController::class, 'link'])->middleware(['permission:reports:write', 'abilities:reports:write']);
    Route::delete('/unknown-scans', [UnknownScanApiController::class, 'destroyAll'])->middleware(['permission:reports:purge', 'abilities:reports:write']);
    Route::post('/unknown-scans/deduplicate', [UnknownScanApiController::class, 'deduplicate'])->middleware(['permission:reports:purge', 'abilities:reports:write']);

    Route::get('/export/assets', [ExportApiController::class, 'assets'])
        ->middleware(['permission:reports:export', 'abilities:reports:read', 'throttle:export']);
    Route::get('/export/lists/{list}', [ExportApiController::class, 'list'])
        ->middleware(['permission:reports:export', 'abilities:reports:read', 'throttle:export']);

    // Files
    Route::get('/files/search', [FileApiController::class, 'search'])->middleware(['permission:files:read', 'abilities:files:read']);
    Route::post('/files/upload', [FileApiController::class, 'upload'])->middleware(['permission:files:write', 'abilities:files:write']);
    Route::post('/files/bulk-upload', [FileApiController::class, 'bulkUpload'])->middleware(['permission:files:write', 'abilities:files:write']);
    Route::post('/files/url', [FileApiController::class, 'storeUrl'])->middleware(['permission:files:write', 'abilities:files:write']);
    Route::post('/files/{file}/link', [FileApiController::class, 'link'])->middleware(['permission:files:write', 'abilities:files:write']);
    Route::delete('/files/links/{fileLink}', [FileApiController::class, 'unlink'])->middleware(['permission:files:write', 'abilities:files:write']);
    Route::get('/files/{file}/download', [FileApiController::class, 'download'])->middleware(['permission:files:read', 'abilities:files:read']);
    Route::get('/files', [FileApiController::class, 'index'])->middleware(['permission:files:manage', 'abilities:files:read']);
    Route::get('/files/{file}', [FileApiController::class, 'show'])->middleware(['permission:files:manage', 'abilities:files:read']);
    Route::delete('/files/{file}', [FileApiController::class, 'destroy'])->middleware(['permission:files:manage', 'abilities:files:write']);

    // Import jobs
    Route::middleware('permission:import:run')->prefix('import')->group(function () {
        Route::get('/status', [ImportApiController::class, 'status'])->middleware(['permission:import:run', 'abilities:assets:write']);
        Route::post('/upload', [ImportApiController::class, 'upload'])
            ->middleware(['permission:import:run', 'abilities:assets:write', 'throttle:import']);
        Route::post('/prepare', [ImportApiController::class, 'prepare'])
            ->middleware(['permission:import:run', 'abilities:assets:write', 'throttle:import']);
        Route::post('/process', [ImportApiController::class, 'process'])
            ->middleware(['permission:import:run', 'abilities:assets:write', 'throttle:import']);
    });

    // Admin
    Route::middleware('permission:admin:read,admin:write')->prefix('admin')->group(function () {
        Route::get('/users', [UserApiController::class, 'index'])->middleware(['permission:admin:read', 'abilities:admin:read']);
        Route::get('/users/{user}', [UserApiController::class, 'show'])->middleware(['permission:admin:read', 'abilities:admin:read']);
        Route::post('/users', [UserApiController::class, 'store'])->middleware(['permission:admin:write', 'abilities:admin:write']);
        Route::put('/users/{user}', [UserApiController::class, 'update'])->middleware(['permission:admin:write', 'abilities:admin:write']);
        Route::delete('/users/{user}', [UserApiController::class, 'destroy'])->middleware(['permission:admin:write', 'abilities:admin:write']);

        Route::get('/departments', [DepartmentApiController::class, 'index'])->middleware(['permission:admin:read', 'abilities:admin:read']);
        Route::get('/departments/{department}', [DepartmentApiController::class, 'show'])->middleware(['permission:admin:read', 'abilities:admin:read']);
        Route::post('/departments', [DepartmentApiController::class, 'store'])->middleware(['permission:admin:write', 'abilities:admin:write']);
        Route::put('/departments/{department}', [DepartmentApiController::class, 'update'])->middleware(['permission:admin:write', 'abilities:admin:write']);
        Route::delete('/departments/{department}', [DepartmentApiController::class, 'destroy'])->middleware(['permission:admin:write', 'abilities:admin:write']);
    });

    Route::middleware(['feature:email_integration', 'permission:email:ops'])->group(function () {
        Route::get('/email/inbound', [InboundEmailActionApiController::class, 'index'])->middleware(['permission:email:ops', 'abilities:email:ops']);
        Route::post('/email/inbound/{inboundEmailAction}/apply', [InboundEmailActionApiController::class, 'apply'])->middleware(['permission:email:ops', 'abilities:email:ops']);
        Route::post('/email/inbound/{inboundEmailAction}/reject', [InboundEmailActionApiController::class, 'reject'])->middleware(['permission:email:ops', 'abilities:email:ops']);
    });

    // Feature: asset connections
    Route::middleware('feature:asset_connections')->group(function () {
        Route::get('/reports/connection-map', [ConnectionMapApiController::class, 'index'])->middleware(['permission:connections:read', 'abilities:connections:read']);
        Route::get('/assets/{asset}/peer-interfaces/{peer}', [AssetConnectionApiController::class, 'peerInterfaces'])->middleware(['permission:connections:read', 'abilities:connections:read']);

        Route::middleware('permission:connections:write')->group(function () {
            Route::post('/assets/{asset}/interfaces', [AssetInterfaceApiController::class, 'store'])->middleware(['permission:connections:write', 'abilities:connections:write']);
            Route::put('/assets/{asset}/interfaces/{interface}', [AssetInterfaceApiController::class, 'update'])->middleware(['permission:connections:write', 'abilities:connections:write']);
            Route::delete('/assets/{asset}/interfaces/{interface}', [AssetInterfaceApiController::class, 'destroy'])->middleware(['permission:connections:write', 'abilities:connections:write']);
            Route::post('/assets/{asset}/interfaces/apply-templates', [AssetInterfaceApiController::class, 'applyTemplates'])->middleware(['permission:connections:write', 'abilities:connections:write']);

            Route::post('/assets/{asset}/connections', [AssetConnectionApiController::class, 'store'])->middleware(['permission:connections:write', 'abilities:connections:write']);
            Route::delete('/assets/{asset}/connections/{connection}', [AssetConnectionApiController::class, 'destroy'])->middleware(['permission:connections:write', 'abilities:connections:write']);

            Route::post('/assets/{asset}/buses', [AssetConnectionBusApiController::class, 'store'])->middleware(['permission:connections:write', 'abilities:connections:write']);
            Route::delete('/assets/{asset}/interfaces/{interface}/bus', [AssetConnectionBusApiController::class, 'destroy'])->middleware(['permission:connections:write', 'abilities:connections:write']);

            Route::get('/connection-types', [ConnectionTypeApiController::class, 'index'])->middleware(['permission:connections:read', 'abilities:connections:read']);
            Route::post('/connection-types', [ConnectionTypeApiController::class, 'store'])->middleware(['permission:connections:write', 'abilities:connections:write']);
            Route::put('/connection-types/{connectionType}', [ConnectionTypeApiController::class, 'update'])->middleware(['permission:connections:write', 'abilities:connections:write']);
            Route::delete('/connection-types/{connectionType}', [ConnectionTypeApiController::class, 'destroy'])->middleware(['permission:connections:write', 'abilities:connections:write']);
        });
    });

    // Feature: stock consumables
    Route::middleware(['feature:stock_consumables', 'permission:stock:write'])->group(function () {
        Route::post('/assets/{asset}/stock-movements', [AssetStockApiController::class, 'storeMovement'])->middleware(['permission:stock:write', 'abilities:stock:write']);
        Route::patch('/assets/{asset}/stock-levels', [AssetStockApiController::class, 'updateLevels'])->middleware(['permission:stock:write', 'abilities:stock:write']);
    });

    Route::middleware(['feature:stock_consumables', 'permission:stock:manage'])->group(function () {
        Route::post('/assets/{asset}/stock-tokens', [AssetStockApiController::class, 'rotateToken'])->middleware(['permission:stock:write', 'abilities:stock:write']);
        Route::delete('/assets/{asset}/stock-tokens/{stockToken}', [AssetStockApiController::class, 'revokeToken'])->middleware(['permission:stock:write', 'abilities:stock:write']);
    });

    // Feature: shelf tags
    Route::middleware(['feature:shelf_tag_generator', 'permission:labels:read'])->group(function () {
        Route::get('/assets/{asset}/label/pdf', [ShelfTagLayoutApiController::class, 'assetLabelPdf'])->middleware(['permission:labels:read', 'abilities:labels:read']);
        Route::get('/labels/layouts', [ShelfTagLayoutApiController::class, 'index'])->middleware(['permission:labels:read', 'abilities:labels:read']);
        Route::get('/labels/layouts/{layout}', [ShelfTagLayoutApiController::class, 'show'])->middleware(['permission:labels:read', 'abilities:labels:read']);
        Route::post('/labels/layouts', [ShelfTagLayoutApiController::class, 'store'])->middleware(['permission:labels:write', 'abilities:labels:write']);
        Route::put('/labels/layouts/{layout}', [ShelfTagLayoutApiController::class, 'update'])->middleware(['permission:labels:write', 'abilities:labels:write']);
        Route::delete('/labels/layouts/{layout}', [ShelfTagLayoutApiController::class, 'destroy'])->middleware(['permission:labels:write', 'abilities:labels:write']);
    });

    // Feature: rigging
    Route::middleware('feature:rigging')->prefix('rigging')->group(function () {
        Route::get('/events', [RiggingApiController::class, 'eventsIndex'])->middleware(['permission:rigging:read', 'abilities:rigging:read']);
        Route::get('/events/{event}', [RiggingApiController::class, 'eventsShow'])->middleware(['permission:rigging:read', 'abilities:rigging:read']);
        Route::post('/events', [RiggingApiController::class, 'eventsStore'])->middleware(['permission:rigging:write', 'abilities:rigging:write']);
        Route::get('/calculators', [RiggingApiController::class, 'calculatorsIndex'])->middleware(['permission:rigging:read', 'abilities:rigging:read']);
        Route::post('/calculators/{calculator}/compute', [RiggingApiController::class, 'calculatorsCompute'])->middleware(['permission:rigging:read', 'abilities:rigging:read']);
        Route::post('/calculators/{calculator}/logs', [RiggingApiController::class, 'storeCalculationLog'])->middleware(['permission:rigging:write', 'abilities:rigging:write']);
    });
});

<?php

use App\Http\Controllers\Api\AssetApiController;
use App\Http\Controllers\Api\AssetListApiController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [TokenController::class, 'store'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    });
    Route::delete('/auth/token', [TokenController::class, 'destroy']);

    Route::get('/assets', [AssetApiController::class, 'index']);
    Route::get('/assets/{asset}', [AssetApiController::class, 'show']);
    Route::post('/assets', [AssetApiController::class, 'store']);
    Route::put('/assets/{asset}', [AssetApiController::class, 'update']);

    Route::get('/lists', [AssetListApiController::class, 'index']);
    Route::get('/lists/{list}', [AssetListApiController::class, 'show']);
    Route::post('/lists/compare', [AssetListApiController::class, 'compare']);
    Route::post('/scan', [AssetListApiController::class, 'scan'])->middleware('throttle:60,1');
});

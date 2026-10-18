<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReturnAssetCheckoutRequest;
use App\Http\Requests\StoreAssetCheckoutRequest;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Services\AssetCheckoutService;
use Illuminate\Http\JsonResponse;

class AssetCheckoutApiController extends Controller
{
    public function store(
        StoreAssetCheckoutRequest $request,
        Asset $asset,
        AssetCheckoutService $service,
    ): JsonResponse {
        $checkout = $service->checkout($asset, $request->validated(), $request->user());

        return response()->json($checkout, 201);
    }

    public function returnCheckout(
        ReturnAssetCheckoutRequest $request,
        Asset $asset,
        AssetCheckout $checkout,
        AssetCheckoutService $service,
    ): JsonResponse {
        abort_unless((int) $checkout->asset_id === (int) $asset->id, 404);

        $checkout = $service->returnCheckout($checkout, $request->user(), $request->validated());

        return response()->json($checkout);
    }
}

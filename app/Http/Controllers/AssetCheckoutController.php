<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnAssetCheckoutRequest;
use App\Http\Requests\StoreAssetCheckoutRequest;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Services\AssetCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AssetCheckoutController extends Controller
{
    public function store(
        StoreAssetCheckoutRequest $request,
        Asset $asset,
        AssetCheckoutService $service,
    ): RedirectResponse {
        $checkout = $service->checkout($asset, $request->validated(), $request->user());

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'checkout'])
            ->with('status', 'Asset checked out ('.$checkout->purposeLabel().').');
    }

    public function returnCheckout(
        ReturnAssetCheckoutRequest $request,
        Asset $asset,
        AssetCheckout $checkout,
        AssetCheckoutService $service,
    ): RedirectResponse {
        abort_unless((int) $checkout->asset_id === (int) $asset->id, 404);

        try {
            $service->returnCheckout($checkout, $request->user(), $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'checkout'])
            ->with('status', 'Asset returned from checkout.');
    }
}

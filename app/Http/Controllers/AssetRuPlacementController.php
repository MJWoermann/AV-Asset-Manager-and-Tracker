<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRuPlacementRequest;
use App\Http\Requests\UpdateAssetRuPlacementRequest;
use App\Models\Asset;
use App\Models\AssetRuPlacement;
use App\Services\RuVisualiserService;
use Illuminate\Http\RedirectResponse;

class AssetRuPlacementController extends Controller
{
    public function store(
        StoreAssetRuPlacementRequest $request,
        Asset $asset,
        RuVisualiserService $service,
    ): RedirectResponse {
        $service->place($asset, $request->validated());

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'rack'])
            ->with('status', 'RU placement created.');
    }

    public function update(
        UpdateAssetRuPlacementRequest $request,
        Asset $asset,
        AssetRuPlacement $ruPlacement,
        RuVisualiserService $service,
    ): RedirectResponse {
        $this->ensurePlacementBelongsToRack($asset, $ruPlacement);

        $service->update($ruPlacement, $request->validated());

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'rack'])
            ->with('status', 'RU placement updated.');
    }

    public function destroy(
        Asset $asset,
        AssetRuPlacement $ruPlacement,
        RuVisualiserService $service,
    ): RedirectResponse {
        $this->ensurePlacementBelongsToRack($asset, $ruPlacement);

        $service->remove($ruPlacement);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'rack'])
            ->with('status', 'RU placement removed.');
    }

    protected function ensurePlacementBelongsToRack(Asset $asset, AssetRuPlacement $ruPlacement): void
    {
        abort_unless((int) $ruPlacement->rack_asset_id === (int) $asset->id, 404);
    }
}

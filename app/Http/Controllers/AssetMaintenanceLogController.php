<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetMaintenanceLogRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Services\AssetMaintenanceLogService;
use Illuminate\Http\RedirectResponse;

class AssetMaintenanceLogController extends Controller
{
    public function store(
        StoreAssetMaintenanceLogRequest $request,
        Asset $asset,
        AssetMaintenanceLogService $maintenanceLogs,
    ): RedirectResponse {
        $maintenanceLogs->create(
            $asset,
            $request->safe()->only(['type', 'summary', 'body', 'occurred_at', 'status']),
            $request->user(),
            $request->file('files'),
        );

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'maintenance'])
            ->with('status', 'Maintenance entry logged.');
    }

    public function destroy(Asset $asset, AssetMaintenanceLog $maintenanceLog): RedirectResponse
    {
        abort_unless((int) $maintenanceLog->asset_id === (int) $asset->id, 404);

        $maintenanceLog->delete();

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'maintenance'])
            ->with('status', 'Maintenance entry removed.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetConnectionBusRequest;
use App\Models\Asset;
use App\Models\AssetInterface;
use App\Services\ConnectionBusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AssetConnectionBusController extends Controller
{
    public function store(
        StoreAssetConnectionBusRequest $request,
        Asset $asset,
        ConnectionBusService $buses,
    ): RedirectResponse {
        $local = AssetInterface::query()->with('connectionType')->findOrFail($request->integer('local_interface_id'));

        abort_unless((int) $local->asset_id === (int) $asset->id, 404);

        DB::transaction(function () use ($request, $local, $buses): void {
            $buses->join($local, [
                'connection_bus_id' => $request->input('connection_bus_id'),
                'new_bus_name' => $request->input('new_bus_name'),
                'notes' => $request->input('notes'),
            ]);
        });

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Joined device bus.');
    }

    public function destroy(
        Asset $asset,
        AssetInterface $interface,
        ConnectionBusService $buses,
    ): RedirectResponse {
        abort_unless((int) $interface->asset_id === (int) $asset->id, 404);

        $buses->leave($interface);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Left device bus.');
    }
}

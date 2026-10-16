<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetConnectionBusRequest;
use App\Models\Asset;
use App\Models\AssetInterface;
use App\Services\ConnectionBusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AssetConnectionBusApiController extends Controller
{
    public function store(
        StoreAssetConnectionBusRequest $request,
        Asset $asset,
        ConnectionBusService $buses,
    ): JsonResponse {
        $local = AssetInterface::query()->with('connectionType')->findOrFail($request->integer('local_interface_id'));
        abort_unless((int) $local->asset_id === (int) $asset->id, 404);

        DB::transaction(function () use ($request, $local, $buses): void {
            $buses->join($local, [
                'connection_bus_id' => $request->input('connection_bus_id'),
                'new_bus_name' => $request->input('new_bus_name'),
                'notes' => $request->input('notes'),
            ]);
        });

        return response()->json(['message' => 'Joined device bus.'], 201);
    }

    public function destroy(Asset $asset, AssetInterface $interface, ConnectionBusService $buses): JsonResponse
    {
        abort_unless((int) $interface->asset_id === (int) $asset->id, 404);
        $buses->leave($interface);

        return response()->json(['message' => 'Left device bus.']);
    }
}

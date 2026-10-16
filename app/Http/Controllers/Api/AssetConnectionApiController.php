<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AssetConnectionController;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetConnectionRequest;
use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Services\AssetConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AssetConnectionApiController extends Controller
{
    public function store(
        StoreAssetConnectionRequest $request,
        Asset $asset,
        AssetConnectionService $connections,
    ): JsonResponse {
        $local = AssetInterface::query()->with('connectionType')->findOrFail($request->integer('local_interface_id'));

        abort_unless((int) $local->asset_id === (int) $asset->id, 404);
        abort_unless($local->connectionType !== null, 422);

        $type = $local->connectionType;
        $peerAssetId = $request->integer('peer_asset_id');
        $attributes = $request->only(['protocol', 'label', 'notes']);

        $connection = null;

        DB::transaction(function () use ($request, $local, $type, $peerAssetId, $connections, $attributes, &$connection): void {
            if ($request->filled('new_peer_interface_name')) {
                $peerAsset = Asset::query()->findOrFail($peerAssetId);
                $sortOrder = ((int) $peerAsset->interfaces()->max('sort_order')) + 10;

                $peer = $peerAsset->interfaces()->create([
                    'name' => trim((string) $request->input('new_peer_interface_name')),
                    'connection_type_id' => $type->id,
                    'sort_order' => $sortOrder,
                    'item_type_interface_template_id' => null,
                    'notes' => null,
                ]);
            } else {
                $peer = AssetInterface::query()->findOrFail($request->integer('peer_interface_id'));

                if ($peer->connection_type_id === null) {
                    $peer->update(['connection_type_id' => $type->id]);
                    $peer = $peer->fresh();
                }
            }

            $connection = $connections->create($local, $peer, $type, $attributes);
        });

        return response()->json($connection, 201);
    }

    public function destroy(
        Asset $asset,
        AssetConnection $connection,
        AssetConnectionService $connections,
    ): JsonResponse {
        $connection->load(['interfaceA', 'interfaceB']);

        $belongsToAsset = in_array((int) $asset->id, [
            (int) $connection->interfaceA->asset_id,
            (int) $connection->interfaceB->asset_id,
        ], true);

        abort_unless($belongsToAsset, 404);

        $connections->delete($connection);

        return response()->json(['message' => 'Connection removed.']);
    }

    public function peerInterfaces(Asset $asset, Asset $peer): JsonResponse
    {
        return app(AssetConnectionController::class)->peerInterfaces($asset, $peer);
    }
}

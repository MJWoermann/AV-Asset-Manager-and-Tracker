<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetConnectionRequest;
use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Models\ConnectionType;
use App\Services\AssetConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AssetConnectionController extends Controller
{
    public function store(
        StoreAssetConnectionRequest $request,
        Asset $asset,
        AssetConnectionService $connections,
    ): RedirectResponse {
        $local = AssetInterface::query()->findOrFail($request->integer('local_interface_id'));
        $peer = AssetInterface::query()->findOrFail($request->integer('peer_interface_id'));
        $type = ConnectionType::query()->findOrFail($request->integer('connection_type_id'));

        abort_unless((int) $local->asset_id === (int) $asset->id, 404);

        $connections->create($local, $peer, $type, $request->only(['protocol', 'label', 'notes']));

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Connection created.');
    }

    public function destroy(Asset $asset, AssetConnection $connection): RedirectResponse
    {
        $connection->load(['interfaceA', 'interfaceB']);

        $belongsToAsset = in_array((int) $asset->id, [
            (int) $connection->interfaceA->asset_id,
            (int) $connection->interfaceB->asset_id,
        ], true);

        abort_unless($belongsToAsset, 404);

        $connection->delete();

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Connection removed.');
    }

    public function peerInterfaces(Asset $asset, Asset $peer): JsonResponse
    {
        abort_if((int) $asset->id === (int) $peer->id, 422, 'Choose a different asset.');

        $interfaces = $peer->interfaces()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (AssetInterface $interface): array => [
                'id' => $interface->id,
                'name' => $interface->name,
            ])
            ->values();

        return response()->json(['interfaces' => $interfaces]);
    }
}

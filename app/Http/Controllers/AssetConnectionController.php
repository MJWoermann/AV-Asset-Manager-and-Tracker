<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetConnectionRequest;
use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Services\AssetConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AssetConnectionController extends Controller
{
    public function store(
        StoreAssetConnectionRequest $request,
        Asset $asset,
        AssetConnectionService $connections,
    ): RedirectResponse {
        $local = AssetInterface::query()->with('connectionType')->findOrFail($request->integer('local_interface_id'));

        abort_unless((int) $local->asset_id === (int) $asset->id, 404);
        abort_unless($local->connectionType !== null, 422);

        $type = $local->connectionType;
        $peerAssetId = $request->integer('peer_asset_id');
        $attributes = $request->only(['protocol', 'label', 'notes']);

        DB::transaction(function () use ($request, $local, $type, $peerAssetId, $connections, $attributes): void {
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

            $connections->create($local, $peer, $type, $attributes);
        });

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Connection created.');
    }

    public function destroy(
        Asset $asset,
        AssetConnection $connection,
        AssetConnectionService $connections,
    ): RedirectResponse {
        $connection->load(['interfaceA', 'interfaceB']);

        $belongsToAsset = in_array((int) $asset->id, [
            (int) $connection->interfaceA->asset_id,
            (int) $connection->interfaceB->asset_id,
        ], true);

        abort_unless($belongsToAsset, 404);

        $connections->delete($connection);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Connection removed.');
    }

    public function peerInterfaces(Asset $asset, Asset $peer): JsonResponse
    {
        abort_if((int) $asset->id === (int) $peer->id, 422, 'Choose a different asset.');

        $matchTypeId = request()->integer('connection_type_id') ?: null;

        $interfaces = $peer->interfaces()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'connection_type_id'])
            ->sortBy(function (AssetInterface $interface) use ($matchTypeId): array {
                $matches = $matchTypeId !== null
                    && (int) $interface->connection_type_id === $matchTypeId;

                return [$matches ? 0 : 1, $interface->sort_order, $interface->id];
            })
            ->values()
            ->map(fn (AssetInterface $interface): array => [
                'id' => $interface->id,
                'name' => $interface->name,
                'connection_type_id' => $interface->connection_type_id
                    ? (string) $interface->connection_type_id
                    : null,
                'matches_type' => $matchTypeId !== null
                    && (int) $interface->connection_type_id === $matchTypeId,
            ])
            ->values();

        return response()->json(['interfaces' => $interfaces]);
    }
}

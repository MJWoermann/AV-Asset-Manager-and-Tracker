<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetInterfaceRequest;
use App\Http\Requests\UpdateAssetInterfaceRequest;
use App\Models\Asset;
use App\Models\AssetInterface;
use App\Services\AssetInterfaceService;
use App\Services\ConnectionBusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AssetInterfaceController extends Controller
{
    public function store(StoreAssetInterfaceRequest $request, Asset $asset): RedirectResponse
    {
        $data = $request->validated();
        $sortOrder = array_key_exists('sort_order', $data) && $data['sort_order'] !== null
            ? (int) $data['sort_order']
            : ((int) $asset->interfaces()->max('sort_order')) + 10;

        $asset->interfaces()->create([
            'name' => $data['name'],
            'connection_type_id' => (int) $data['connection_type_id'],
            'notes' => $data['notes'] ?? null,
            'sort_order' => $sortOrder,
            'item_type_interface_template_id' => null,
            'manufacturer_model_interface_template_id' => null,
        ]);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Interface added.');
    }

    public function update(
        UpdateAssetInterfaceRequest $request,
        Asset $asset,
        AssetInterface $interface,
        ConnectionBusService $buses,
    ): RedirectResponse {
        abort_unless((int) $interface->asset_id === (int) $asset->id, 404);

        $data = $request->validated();
        $newTypeId = (int) $data['connection_type_id'];

        DB::transaction(function () use ($interface, $data, $newTypeId, $buses): void {
            $interface->loadMissing('buses');
            $bus = $interface->currentBus();

            if ($bus && (int) $bus->connection_type_id !== $newTypeId) {
                $buses->leave($interface);
            }

            $interface->update([
                'name' => $data['name'],
                'connection_type_id' => $newTypeId,
                'notes' => $data['notes'] ?? null,
                'sort_order' => array_key_exists('sort_order', $data) && $data['sort_order'] !== null
                    ? (int) $data['sort_order']
                    : $interface->sort_order,
            ]);
        });

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Interface updated.');
    }

    public function destroy(Asset $asset, AssetInterface $interface): RedirectResponse
    {
        abort_unless((int) $interface->asset_id === (int) $asset->id, 404);

        $interface->delete();

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Interface removed.');
    }

    public function applyTemplates(Asset $asset, AssetInterfaceService $interfaces): RedirectResponse
    {
        $created = $interfaces->syncMissingForAsset($asset);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', $created === 0
                ? 'No missing template interfaces to apply.'
                : "Applied {$created} missing template interface".($created === 1 ? '' : 's').'.');
    }
}

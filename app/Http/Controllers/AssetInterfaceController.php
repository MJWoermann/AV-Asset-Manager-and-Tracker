<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetInterfaceRequest;
use App\Http\Requests\UpdateAssetInterfaceRequest;
use App\Models\Asset;
use App\Models\AssetInterface;
use Illuminate\Http\RedirectResponse;

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
        ]);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'connections'])
            ->with('status', 'Interface added.');
    }

    public function update(UpdateAssetInterfaceRequest $request, Asset $asset, AssetInterface $interface): RedirectResponse
    {
        abort_unless((int) $interface->asset_id === (int) $asset->id, 404);

        $data = $request->validated();

        $interface->update([
            'name' => $data['name'],
            'connection_type_id' => (int) $data['connection_type_id'],
            'notes' => $data['notes'] ?? null,
            'sort_order' => array_key_exists('sort_order', $data) && $data['sort_order'] !== null
                ? (int) $data['sort_order']
                : $interface->sort_order,
        ]);

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
}

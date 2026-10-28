<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetStockToken;
use App\Services\KanbanTagService;
use App\Support\StockTracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetStockTokenController extends Controller
{
    public function rotate(
        Request $request,
        Asset $asset,
        KanbanTagService $kanban,
    ): RedirectResponse {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless($request->user()?->can('stock:manage'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:64'],
        ]);

        $result = $kanban->rotate($asset, $data['label']);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'stock'])
            ->with('status', 'Kanban token rotated for '.$result['token']->label.'.')
            ->with('kanban_plain_token', $result['plain_token'])
            ->with('kanban_guest_url', $result['guest_url'])
            ->with('kanban_label', $result['token']->label);
    }

    public function revoke(
        Request $request,
        Asset $asset,
        AssetStockToken $stockToken,
        KanbanTagService $kanban,
    ): RedirectResponse {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless($request->user()?->can('stock:manage'), 403);
        abort_unless((int) $stockToken->asset_id === (int) $asset->id, 404);

        $kanban->revoke($stockToken);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'stock'])
            ->with('status', 'Kanban token revoked.');
    }
}

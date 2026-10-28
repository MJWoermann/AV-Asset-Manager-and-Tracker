<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementSource;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Models\Asset;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\KanbanTagService;
use App\Services\StockMovementService;
use App\Support\StockTracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AssetStockController extends Controller
{
    public function store(
        StoreStockMovementRequest $request,
        Asset $asset,
        StockMovementService $movements,
    ): RedirectResponse {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);

        $data = $request->validated();
        $this->ensureBaselineUnlessBackdated($asset, $data['recorded_at'] ?? null, $request->user(), $movements);
        $asset->refresh();

        $movements->record(
            $asset,
            [
                'type' => $data['type'],
                'quantity' => $data['quantity'] ?? null,
                'quantity_delta' => $data['quantity_delta'] ?? null,
                'notes' => $data['notes'] ?? null,
                'external_po_number' => $data['external_po_number'] ?? null,
                'external_po_url' => $data['external_po_url'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? null,
                'source' => StockMovementSource::Web,
            ],
            $request->user(),
            $request->file('pdfs'),
            allowPdf: true,
        );

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'stock', 'action' => $data['type']])
            ->with('status', 'Stock movement recorded.');
    }

    public function update(
        UpdateStockMovementRequest $request,
        Asset $asset,
        StockMovement $stockMovement,
        StockMovementService $movements,
    ): RedirectResponse {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless((int) $stockMovement->asset_id === (int) $asset->id, 404);

        $data = $request->validated();
        $movements->update($stockMovement, $data, $request->user());

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'stock'])
            ->with('status', 'Stock event updated.');
    }

    public function destroy(
        Request $request,
        Asset $asset,
        StockMovement $stockMovement,
        StockMovementService $movements,
    ): RedirectResponse {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless($request->user()?->can('stock:adjust'), 403);
        abort_unless((int) $stockMovement->asset_id === (int) $asset->id, 404);

        $movements->delete($stockMovement, $request->user());

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'stock'])
            ->with('status', 'Stock event deleted.');
    }

    public function updateLevels(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless($request->user()?->can('stock:write'), 403);

        $data = $request->validate([
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'max_stock_level' => ['nullable', 'integer', 'min:0'],
        ]);

        if (
            isset($data['min_stock_level'], $data['max_stock_level'])
            && $data['max_stock_level'] < $data['min_stock_level']
        ) {
            return back()->withErrors([
                'max_stock_level' => 'Max stock level must be greater than or equal to min.',
            ]);
        }

        $asset->update([
            'min_stock_level' => $data['min_stock_level'] ?? null,
            'max_stock_level' => $data['max_stock_level'] ?? null,
        ]);

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'stock'])
            ->with('status', 'Stock levels updated.');
    }

    public function printTags(
        Request $request,
        Asset $asset,
        KanbanTagService $kanban,
        StockMovementService $movements,
    ): View {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless($request->user()?->can('stock:manage'), 403);

        $movements->ensureBaseline($asset, $request->user());
        $plainById = $kanban->ensureDefaultTags($asset);
        $tags = $kanban->activeTagsForPrint($asset->fresh(), $plainById);

        return view('stock.kanban-print', [
            'asset' => $asset,
            'tags' => $tags,
            'sku' => $kanban->restockingField($asset, 'sku'),
            'supplier' => $kanban->restockingField($asset, 'supplier') ?: $asset->supplier,
        ]);
    }

    /**
     * A first backdated event is the opening movement. Do not also insert a count dated today,
     * or the restock would be applied against that later count.
     */
    protected function ensureBaselineUnlessBackdated(
        Asset $asset,
        mixed $recordedAt,
        ?User $user,
        StockMovementService $movements,
    ): void {
        if ($asset->stockMovements()->exists()) {
            $movements->ensureBaseline($asset, $user);

            return;
        }

        if (is_string($recordedAt) && $recordedAt !== '' && Carbon::parse($recordedAt)->startOfDay()->lt(now()->startOfDay())) {
            return;
        }

        $movements->ensureBaseline($asset, $user);
    }
}

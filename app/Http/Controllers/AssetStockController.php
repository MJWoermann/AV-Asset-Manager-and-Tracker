<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementSource;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Asset;
use App\Services\KanbanTagService;
use App\Services\StockMovementService;
use App\Support\StockTracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $movements->ensureBaseline($asset, $request->user());
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

    public function updateLevels(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless($request->user()?->hasAnyRole(['admin', 'inventory_manager', 'scanner']), 403);

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
        abort_unless($request->user()?->hasAnyRole(['admin', 'inventory_manager']), 403);

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
}

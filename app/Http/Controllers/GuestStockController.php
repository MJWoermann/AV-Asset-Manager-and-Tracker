<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementSource;
use App\Http\Requests\StoreGuestStockMovementRequest;
use App\Services\KanbanTagService;
use App\Services\StockMovementService;
use App\Support\StockTracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GuestStockController extends Controller
{
    public function show(string $token, KanbanTagService $kanban): View
    {
        abort_unless(StockTracking::guestSurfaceEnabled(), 404);

        $stockToken = $kanban->findActiveByPlainToken($token);
        abort_if($stockToken === null, 404);

        $asset = $stockToken->asset()->with('itemType')->firstOrFail();
        abort_unless((bool) $asset->itemType?->supports_stock_tracking, 404);

        $stockToken->update(['last_used_at' => now()]);

        return view('stock.guest', [
            'asset' => $asset,
            'token' => $token,
            'action' => request()->string('action')->toString() ?: 'count',
            'sku' => $kanban->restockingField($asset, 'sku'),
        ]);
    }

    public function store(
        StoreGuestStockMovementRequest $request,
        string $token,
        KanbanTagService $kanban,
        StockMovementService $movements,
    ): RedirectResponse {
        abort_unless(StockTracking::guestSurfaceEnabled(), 404);

        $stockToken = $kanban->findActiveByPlainToken($token);
        abort_if($stockToken === null, 404);

        $asset = $stockToken->asset()->with('itemType')->firstOrFail();
        abort_unless((bool) $asset->itemType?->supports_stock_tracking, 404);

        $data = $request->validated();
        $movements->ensureBaseline($asset, null);
        $asset->refresh();

        $movements->record(
            $asset,
            [
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'external_po_number' => $data['external_po_number'] ?? null,
                'external_po_url' => $data['external_po_url'] ?? null,
                'source' => StockMovementSource::GuestQr,
            ],
            null,
            null,
            allowPdf: false,
        );

        $stockToken->update(['last_used_at' => now()]);

        return redirect()
            ->route('stock.guest.show', ['token' => $token, 'action' => $data['type']])
            ->with('status', 'Stock updated.');
    }
}

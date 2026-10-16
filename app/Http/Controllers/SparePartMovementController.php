<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSparePartMovementRequest;
use App\Models\SparePart;
use App\Services\SparePartService;
use Illuminate\Http\RedirectResponse;

class SparePartMovementController extends Controller
{
    public function store(
        StoreSparePartMovementRequest $request,
        SparePart $spare,
        SparePartService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->ensureBaseline($spare, $request->user());
        $spare->refresh();

        $service->record(
            $spare,
            [
                'type' => $data['type'],
                'quantity' => $data['quantity'] ?? null,
                'quantity_delta' => $data['quantity_delta'] ?? null,
                'against_asset_id' => $data['against_asset_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'ticket_ref' => $data['ticket_ref'] ?? null,
                'quoting_status' => $data['quoting_status'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
            $request->user(),
            $request->file('pdfs'),
        );

        return redirect()
            ->route('spares.show', [$spare, 'action' => $data['type']])
            ->with('status', 'Movement recorded.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\StockMovementSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Asset;
use App\Models\AssetStockToken;
use App\Services\KanbanTagService;
use App\Services\StockMovementService;
use App\Support\StockTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AssetStockApiController extends Controller
{
    public function storeMovement(
        StoreStockMovementRequest $request,
        Asset $asset,
        StockMovementService $movements,
    ): JsonResponse {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);

        $data = $request->validated();
        $recordedAt = $data['recorded_at'] ?? null;
        $backdatedFirstEvent = ! $asset->stockMovements()->exists()
            && is_string($recordedAt)
            && $recordedAt !== ''
            && Carbon::parse($recordedAt)->startOfDay()->lt(now()->startOfDay());
        if (! $backdatedFirstEvent) {
            $movements->ensureBaseline($asset, $request->user());
            $asset->refresh();
        }

        $movement = $movements->record(
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

        return response()->json($movement, 201);
    }

    public function updateLevels(Request $request, Asset $asset): JsonResponse
    {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);

        $data = $request->validate([
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'max_stock_level' => ['nullable', 'integer', 'min:0'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
        ]);

        $asset->update($data);

        return response()->json($asset->fresh());
    }

    public function rotateToken(Request $request, Asset $asset, KanbanTagService $kanban): JsonResponse
    {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless($request->user()->can('stock:manage'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:64'],
        ]);

        $result = $kanban->rotate($asset, $data['label']);

        return response()->json([
            'token' => $result['token'],
            'plain_token' => $result['plain_token'],
            'guest_url' => $result['guest_url'],
        ], 201);
    }

    public function revokeToken(
        Request $request,
        Asset $asset,
        AssetStockToken $stockToken,
        KanbanTagService $kanban,
    ): JsonResponse {
        abort_unless(StockTracking::availableForAsset($request->user(), $asset), 404);
        abort_unless((int) $stockToken->asset_id === (int) $asset->id, 404);

        $kanban->revoke($stockToken);

        return response()->json(['message' => 'Stock token revoked.']);
    }
}

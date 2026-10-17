<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSparePartMovementRequest;
use App\Http\Requests\StoreSparePartRequest;
use App\Http\Requests\SupersedeSparePartRequest;
use App\Http\Requests\UpdateSparePartRequest;
use App\Models\SparePart;
use App\Services\SparePartChartService;
use App\Services\SparePartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SparePartApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SparePart::query()
            ->with(['manufacturer', 'manufacturerModel', 'location'])
            ->orderBy('code');

        if (! $request->boolean('include_eol')) {
            $query->activeLifecycle();
        }

        if ($term = trim($request->string('q')->toString())) {
            $query->search($term);
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function lookup(Request $request): JsonResponse
    {
        $code = trim($request->string('code')->toString());
        abort_if($code === '', 422, 'code is required');

        $spare = SparePart::query()->where('code', $code)->first();

        return response()->json(['data' => $spare]);
    }

    public function showByCode(string $code): JsonResponse
    {
        $spare = SparePart::query()->where('code', $code)->firstOrFail();

        return response()->json($spare->load(['manufacturer', 'manufacturerModel', 'location']));
    }

    public function show(SparePart $spare): JsonResponse
    {
        return response()->json($spare->load(['manufacturer', 'manufacturerModel', 'location', 'groupItems.group']));
    }

    public function chart(SparePart $spare, SparePartChartService $charts): JsonResponse
    {
        return response()->json($charts->build($spare));
    }

    public function store(StoreSparePartRequest $request, SparePartService $service): JsonResponse
    {
        $data = $request->spareAttributes();
        $spare = $service->create(
            $data,
            $data['compatible_model_ids'] ?? [],
            $data['compatible_asset_ids'] ?? [],
        );

        return response()->json($spare, 201);
    }

    public function update(UpdateSparePartRequest $request, SparePart $spare, SparePartService $service): JsonResponse
    {
        $data = $request->spareAttributes();
        $service->update(
            $spare,
            $data,
            $data['compatible_model_ids'] ?? [],
            $data['compatible_asset_ids'] ?? [],
        );

        return response()->json($spare->fresh());
    }

    public function destroy(Request $request, SparePart $spare): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager']), 403);

        if ($spare->movements()->exists()) {
            return response()->json(['message' => 'Cannot delete a spare with movement history.'], 422);
        }

        $spare->delete();

        return response()->json(['message' => 'Spare deleted.']);
    }

    public function supersede(SupersedeSparePartRequest $request, SparePart $spare, SparePartService $service): JsonResponse
    {
        $successor = SparePart::query()->findOrFail($request->validated('superseded_by_id'));
        $service->supersede($spare, $successor, $request->user());

        return response()->json($spare->fresh(['supersededBy']));
    }

    public function storeMovement(
        StoreSparePartMovementRequest $request,
        SparePart $spare,
        SparePartService $service,
    ): JsonResponse {
        $data = $request->validated();
        $service->ensureBaseline($spare, $request->user());
        $spare->refresh();

        $movement = $service->record(
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
        );

        return response()->json($movement, 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRepairProcedureRequest;
use App\Http\Requests\UpdateRepairProcedureRequest;
use App\Models\RepairProcedure;
use App\Services\RepairProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepairProcedureApiController extends Controller
{
    public function index(): JsonResponse
    {
        $procedures = RepairProcedure::query()
            ->withCount('steps')
            ->orderBy('title')
            ->get();

        return response()->json(['data' => $procedures]);
    }

    public function show(RepairProcedure $repairProcedure): JsonResponse
    {
        $repairProcedure->load(['steps', 'recommendedSpareParts', 'files']);

        return response()->json($repairProcedure);
    }

    public function store(StoreRepairProcedureRequest $request, RepairProcedureService $service): JsonResponse
    {
        $data = $request->validated();
        $procedure = $service->create(
            $data,
            $data['steps'] ?? [],
            $data['recommended_parts'] ?? [],
        );

        return response()->json($procedure, 201);
    }

    public function update(
        UpdateRepairProcedureRequest $request,
        RepairProcedure $repairProcedure,
        RepairProcedureService $service,
    ): JsonResponse {
        $data = $request->validated();
        $service->update(
            $repairProcedure,
            $data,
            $data['steps'] ?? [],
            $data['recommended_parts'] ?? [],
        );

        return response()->json($repairProcedure->fresh(['steps']));
    }

    public function destroy(Request $request, RepairProcedure $repairProcedure, RepairProcedureService $service): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager']), 403);
        $service->delete($repairProcedure);

        return response()->json(['message' => 'Repair procedure deleted.']);
    }
}

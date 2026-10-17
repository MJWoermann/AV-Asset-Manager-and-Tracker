<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelRepairRequest;
use App\Http\Requests\CompleteRepairRequest;
use App\Http\Requests\StoreRepairFromFaultRequest;
use App\Http\Requests\StoreRepairFromSpareRequest;
use App\Http\Requests\StoreRepairPartMovementRequest;
use App\Http\Requests\StoreRepairStepRequest;
use App\Http\Requests\StoreRepairWithNewFaultRequest;
use App\Http\Requests\UpdateRepairStepRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\Repair;
use App\Models\RepairStep;
use App\Models\SparePart;
use App\Services\RepairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepairApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = trim((string) $request->query('status', 'active'));
        $term = trim((string) $request->query('q', ''));

        $query = Repair::query()
            ->with(['asset', 'assignee'])
            ->orderByDesc('started_at');

        if ($status === 'active') {
            $query->active();
        } elseif ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($term !== '') {
            $query->search($term);
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function show(Repair $repair): JsonResponse
    {
        $repair->load(['asset', 'steps', 'movements.sparePart', 'procedure', 'assignee']);

        return response()->json($repair);
    }

    public function storeFromFault(StoreRepairFromFaultRequest $request, Asset $asset, RepairService $service): JsonResponse
    {
        $data = $request->validated();
        $fault = AssetMaintenanceLog::query()->findOrFail($data['maintenance_log_id']);

        $repair = $service->startFromFault($asset, $fault, $request->user(), [
            'summary' => $data['summary'] ?? null,
            'notes' => $data['notes'] ?? null,
            'repair_procedure_id' => $data['repair_procedure_id'] ?? null,
            'assignee_user_id' => $data['assignee_user_id'] ?? null,
            'set_asset_maintenance' => (bool) ($data['set_asset_maintenance'] ?? false),
        ]);

        return response()->json($repair, 201);
    }

    public function storeWithNewFault(StoreRepairWithNewFaultRequest $request, Asset $asset, RepairService $service): JsonResponse
    {
        $repair = $service->startWithNewFault($asset, $request->validated(), $request->user());

        return response()->json($repair, 201);
    }

    public function storeFromSpare(StoreRepairFromSpareRequest $request, SparePart $spare, RepairService $service): JsonResponse
    {
        $data = $request->validated();
        $asset = Asset::query()->findOrFail($data['asset_id']);

        $repair = $service->startFromSpare($spare, $asset, $request->user(), [
            'summary' => $data['summary'] ?? null,
            'notes' => $data['notes'] ?? null,
            'repair_procedure_id' => $data['repair_procedure_id'] ?? null,
            'assignee_user_id' => $data['assignee_user_id'] ?? null,
        ]);

        return response()->json($repair, 201);
    }

    public function storeStep(StoreRepairStepRequest $request, Repair $repair, RepairService $service): JsonResponse
    {
        $service->addAdHocStep($repair, $request->validated());

        return response()->json($repair->fresh('steps'), 201);
    }

    public function updateStep(UpdateRepairStepRequest $request, Repair $repair, RepairStep $step, RepairService $service): JsonResponse
    {
        abort_unless((int) $step->repair_id === (int) $repair->id, 404);

        if ($request->boolean('completed')) {
            $service->markStepComplete($step, $request->user(), $request->validated('notes'));
        } else {
            $service->markStepIncomplete($step);
        }

        return response()->json($step->fresh());
    }

    public function storePart(StoreRepairPartMovementRequest $request, Repair $repair, RepairService $service): JsonResponse
    {
        $data = $request->validated();
        $spare = SparePart::query()->findOrFail($data['spare_part_id']);
        $service->recordPartUse($repair, $spare, $data, $request->user());

        return response()->json($repair->fresh('movements'), 201);
    }

    public function complete(CompleteRepairRequest $request, Repair $repair, RepairService $service): JsonResponse
    {
        $service->complete($repair, $request->validated(), $request->user());

        return response()->json($repair->fresh());
    }

    public function cancel(CancelRepairRequest $request, Repair $repair, RepairService $service): JsonResponse
    {
        $service->cancel($repair, $request->validated('reason'), $request->user());

        return response()->json($repair->fresh());
    }
}

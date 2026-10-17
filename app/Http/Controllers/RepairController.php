<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Enums\RepairStatus;
use App\Enums\SparePartMovementType;
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
use App\Support\ScannableIdentifierFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RepairController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', 'active'));
        $term = trim((string) $request->query('q', ''));

        $query = Repair::query()
            ->with(['asset', 'assignee', 'maintenanceLog'])
            ->orderByDesc('started_at')
            ->orderByDesc('id');

        if ($status === 'active') {
            $query->active();
        } elseif ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term): void {
                $q->where('summary', 'like', "%{$term}%")
                    ->orWhereHas('asset', function ($assetQuery) use ($term): void {
                        $assetQuery->where(function ($q) use ($term): void {
                            $like = "%{$term}%";
                            $q->where('name', 'like', $like)
                                ->orWhere('fmi_ast', 'like', $like)
                                ->orWhere('rig_tag', 'like', $like);
                            ScannableIdentifierFields::orWherePartialMatch($q, $term);
                        });
                    });
            });
        }

        $assignee = trim((string) $request->query('assignee', ''));
        if ($assignee === 'me') {
            $query->where('assignee_user_id', $request->user()->id);
        }

        return view('repairs.index', [
            'repairs' => $query->paginate(25)->withQueryString(),
            'status' => $status,
            'term' => $term,
            'statusOptions' => array_merge(
                ['active' => 'Open / in progress', 'all' => 'All'],
                RepairStatus::options(),
            ),
        ]);
    }

    public function show(Repair $repair, RepairService $service): View
    {
        $repair->load([
            'asset.itemType',
            'asset.files',
            'maintenanceLog',
            'procedure.recommendedSpareParts.manufacturer',
            'procedure.files',
            'steps.completedBy',
            'assignee',
            'completedBy',
            'movements.sparePart',
            'movements.user',
            'files',
        ]);

        $guidanceFiles = $service->guidanceFiles(
            $repair->asset,
            null,
            $repair->procedure,
        );

        $compatibleSpares = SparePart::query()
            ->activeLifecycle()
            ->where(function ($q) use ($repair): void {
                $q->whereHas('compatibleAssets', fn ($aq) => $aq->whereKey($repair->asset_id));
                if ($repair->asset->manufacturer_model_id) {
                    $q->orWhere('manufacturer_model_id', $repair->asset->manufacturer_model_id)
                        ->orWhereHas('compatibleModels', fn ($mq) => $mq->whereKey($repair->asset->manufacturer_model_id));
                }
            })
            ->orderBy('code')
            ->limit(100)
            ->get(['id', 'code', 'component', 'quantity_on_hand']);

        $spareOptions = SparePart::query()
            ->activeLifecycle()
            ->orderBy('code')
            ->limit(300)
            ->get(['id', 'code', 'component', 'quantity_on_hand']);

        $fileRows = $repair->files->map(fn ($file) => [
            'file' => $file,
            'inherited' => false,
            'link_id' => (int) $file->pivot->id,
            'note' => $file->pivot->note,
        ])->all();

        return view('repairs.show', [
            'repair' => $repair,
            'guidanceFiles' => $guidanceFiles,
            'compatibleSpares' => $compatibleSpares,
            'spareOptions' => $spareOptions,
            'statuses' => AssetStatus::options(),
            'movementTypes' => [
                SparePartMovementType::Use->value => SparePartMovementType::Use->label(),
                SparePartMovementType::Replace->value => SparePartMovementType::Replace->label(),
            ],
            'fileRows' => $fileRows,
            'fileCategories' => collect(FileCategory::options())->only([
                FileCategory::Photo->value,
                FileCategory::Maintenance->value,
                FileCategory::Other->value,
            ])->all(),
        ]);
    }

    public function storeFromFault(
        StoreRepairFromFaultRequest $request,
        Asset $asset,
        RepairService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $fault = AssetMaintenanceLog::query()->findOrFail($data['maintenance_log_id']);

        $repair = $service->startFromFault($asset, $fault, $request->user(), [
            'summary' => $data['summary'] ?? null,
            'notes' => $data['notes'] ?? null,
            'repair_procedure_id' => $data['repair_procedure_id'] ?? null,
            'assignee_user_id' => $data['assignee_user_id'] ?? null,
            'set_asset_maintenance' => (bool) ($data['set_asset_maintenance'] ?? false),
        ]);

        return redirect()
            ->route('repairs.show', $repair)
            ->with('status', 'Repair started.');
    }

    public function storeWithNewFault(
        StoreRepairWithNewFaultRequest $request,
        Asset $asset,
        RepairService $service,
    ): RedirectResponse {
        $data = $request->validated();

        $repair = $service->startWithNewFault($asset, $data, $request->user());

        return redirect()
            ->route('repairs.show', $repair)
            ->with('status', 'Fault logged and repair started.');
    }

    public function storeFromSpare(
        StoreRepairFromSpareRequest $request,
        SparePart $spare,
        RepairService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $asset = Asset::query()->findOrFail($data['asset_id']);

        $repair = $service->startFromSpare($spare, $asset, $request->user(), [
            'summary' => $data['summary'] ?? null,
            'notes' => $data['notes'] ?? null,
            'repair_procedure_id' => $data['repair_procedure_id'] ?? null,
            'assignee_user_id' => $data['assignee_user_id'] ?? null,
        ]);

        return redirect()
            ->route('repairs.show', $repair)
            ->with('status', 'Repair started from spare.');
    }

    public function updateStep(
        UpdateRepairStepRequest $request,
        Repair $repair,
        RepairStep $step,
        RepairService $service,
    ): RedirectResponse {
        abort_unless((int) $step->repair_id === (int) $repair->id, 404);

        if ($request->boolean('completed')) {
            $service->markStepComplete($step, $request->user(), $request->validated('notes'));
        } else {
            $service->markStepIncomplete($step);
        }

        return back()->with('status', 'Checklist updated.');
    }

    public function storeStep(
        StoreRepairStepRequest $request,
        Repair $repair,
        RepairService $service,
    ): RedirectResponse {
        $service->addAdHocStep($repair, $request->validated());

        return back()->with('status', 'Step added.');
    }

    public function storePart(
        StoreRepairPartMovementRequest $request,
        Repair $repair,
        RepairService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $spare = SparePart::query()->findOrFail($data['spare_part_id']);

        $service->recordPartUse($repair, $spare, $data, $request->user());

        return back()->with('status', 'Spare issued to repair.');
    }

    public function complete(
        CompleteRepairRequest $request,
        Repair $repair,
        RepairService $service,
    ): RedirectResponse {
        $service->complete($repair, $request->validated(), $request->user());

        return redirect()
            ->route('repairs.show', $repair)
            ->with('status', 'Repair completed.');
    }

    public function cancel(
        CancelRepairRequest $request,
        Repair $repair,
        RepairService $service,
    ): RedirectResponse {
        $service->cancel($repair, $request->validated('reason'), $request->user());

        return redirect()
            ->route('repairs.show', $repair)
            ->with('status', 'Repair cancelled.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\FileCategory;
use App\Http\Requests\StoreRepairProcedureRequest;
use App\Http\Requests\UpdateRepairProcedureRequest;
use App\Models\ItemType;
use App\Models\ManufacturerModel;
use App\Models\RepairProcedure;
use App\Models\SparePart;
use App\Services\RepairProcedureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RepairProcedureController extends Controller
{
    public function index(): View
    {
        $procedures = RepairProcedure::query()
            ->with(['manufacturerModel.manufacturer', 'itemType', 'sparePart'])
            ->withCount('steps')
            ->orderBy('title')
            ->orderBy('id')
            ->paginate(25);

        return view('repairs.procedures.index', compact('procedures'));
    }

    public function create(): View
    {
        return view('repairs.procedures.form', $this->formData(new RepairProcedure));
    }

    public function store(StoreRepairProcedureRequest $request, RepairProcedureService $service): RedirectResponse
    {
        $data = $request->validated();
        $procedure = $service->create(
            $data,
            $data['steps'] ?? [],
            $data['recommended_parts'] ?? [],
        );

        return redirect()
            ->route('repair-procedures.show', $procedure)
            ->with('status', 'Repair procedure created.');
    }

    public function show(RepairProcedure $repairProcedure): View
    {
        $repairProcedure->load([
            'steps',
            'recommendedSpareParts',
            'manufacturerModel.manufacturer',
            'itemType',
            'sparePart',
            'files',
        ]);

        $fileRows = $repairProcedure->files->map(fn ($file) => [
            'file' => $file,
            'inherited' => false,
            'link_id' => (int) $file->pivot->id,
            'note' => $file->pivot->note,
        ])->all();

        return view('repairs.procedures.show', [
            'procedure' => $repairProcedure,
            'fileRows' => $fileRows,
            'fileCategories' => collect(FileCategory::options())->only([
                FileCategory::Manual->value,
                FileCategory::Photo->value,
                FileCategory::Other->value,
            ])->all(),
        ]);
    }

    public function edit(RepairProcedure $repairProcedure): View
    {
        $repairProcedure->load(['steps', 'recommendedSpareParts']);

        return view('repairs.procedures.form', $this->formData($repairProcedure));
    }

    public function update(
        UpdateRepairProcedureRequest $request,
        RepairProcedure $repairProcedure,
        RepairProcedureService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->update(
            $repairProcedure,
            $data,
            $data['steps'] ?? [],
            $data['recommended_parts'] ?? [],
        );

        return redirect()
            ->route('repair-procedures.show', $repairProcedure)
            ->with('status', 'Repair procedure updated.');
    }

    public function destroy(RepairProcedure $repairProcedure, RepairProcedureService $service): RedirectResponse
    {
        abort_unless(auth()->user()?->can('repairs:procedures'), 403);
        $service->delete($repairProcedure);

        return redirect()
            ->route('repair-procedures.index')
            ->with('status', 'Repair procedure deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(RepairProcedure $procedure): array
    {
        return [
            'procedure' => $procedure,
            'models' => ManufacturerModel::query()
                ->with('manufacturer')
                ->orderBy('name')
                ->limit(500)
                ->get(),
            'itemTypes' => ItemType::query()->orderBy('name')->get(['id', 'name']),
            'spareOptions' => SparePart::query()->orderBy('code')->limit(500)->get(['id', 'code', 'component']),
        ];
    }
}

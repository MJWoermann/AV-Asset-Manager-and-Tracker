<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteManufacturerModelRequest;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Services\ManufacturerCatalogueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManufacturerModelController extends Controller
{
    public function create(Manufacturer $manufacturer): View
    {
        return view('manufacturers.models.form', [
            'manufacturer' => $manufacturer,
            'manufacturerModel' => new ManufacturerModel(['manufacturer_id' => $manufacturer->id]),
            'replacementModels' => collect(),
            'assetCount' => 0,
        ]);
    }

    public function store(Request $request, Manufacturer $manufacturer): RedirectResponse
    {
        $manufacturer->models()->create($this->validated($request, $manufacturer));

        return redirect()->route('manufacturers.index')->with('status', 'Model created.');
    }

    public function edit(Manufacturer $manufacturer, ManufacturerModel $manufacturerModel): View
    {
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $replacementModels = ManufacturerModel::query()
            ->with('manufacturer:id,name')
            ->whereKeyNot($manufacturerModel->id)
            ->orderBy('name')
            ->get();

        return view('manufacturers.models.form', [
            'manufacturer' => $manufacturer,
            'manufacturerModel' => $manufacturerModel,
            'replacementModels' => $replacementModels,
            'assetCount' => $manufacturerModel->assets()->count(),
        ]);
    }

    public function update(
        Request $request,
        Manufacturer $manufacturer,
        ManufacturerModel $manufacturerModel,
    ): RedirectResponse {
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $manufacturerModel->update($this->validated($request, $manufacturer, $manufacturerModel));

        return redirect()->route('manufacturers.index')->with('status', 'Model updated.');
    }

    public function destroy(
        DeleteManufacturerModelRequest $request,
        Manufacturer $manufacturer,
        ManufacturerModel $manufacturerModel,
        ManufacturerCatalogueService $catalogue,
    ): RedirectResponse {
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $replacement = ManufacturerModel::query()->findOrFail(
            (int) $request->validated('replacement_manufacturer_model_id')
        );

        $catalogue->deleteModel($manufacturerModel, $replacement);

        return redirect()->route('manufacturers.index')->with('status', 'Model deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(
        Request $request,
        Manufacturer $manufacturer,
        ?ManufacturerModel $manufacturerModel = null,
    ): array {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('manufacturer_models', 'name')
                    ->where(fn ($query) => $query->where('manufacturer_id', $manufacturer->id))
                    ->ignore($manufacturerModel?->id),
            ],
        ]);
    }
}

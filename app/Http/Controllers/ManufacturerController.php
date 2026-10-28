<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteManufacturerRequest;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Services\ManufacturerCatalogueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManufacturerController extends Controller
{
    public function index(Request $request): View
    {
        $manufacturers = Manufacturer::query()
            ->select(['id', 'name'])
            ->withCount(['assets', 'models'])
            ->orderBy('name')
            ->get();

        $manufacturerOptions = $manufacturers
            ->map(fn (Manufacturer $manufacturer) => [
                'id' => $manufacturer->id,
                'name' => $manufacturer->name,
            ])
            ->values();

        $canManageCatalogue = $request->user()?->can('catalog:update') ?? false;

        $initialDeleteManufacturer = null;
        $deletingManufacturerId = (int) old('deleting_manufacturer_id');
        if ($canManageCatalogue && $deletingManufacturerId > 0) {
            $deletingManufacturer = $manufacturers->firstWhere('id', $deletingManufacturerId);
            if ($deletingManufacturer) {
                $initialDeleteManufacturer = [
                    'id' => $deletingManufacturer->id,
                    'name' => $deletingManufacturer->name,
                    'action' => route('manufacturers.destroy', $deletingManufacturer),
                    'replacementId' => (string) old('replacement_manufacturer_id', ''),
                ];
            }
        }

        $initialDeleteModel = null;
        $deletingModelId = (int) old('deleting_model_id');
        if ($canManageCatalogue && $deletingModelId > 0) {
            $deletingModel = ManufacturerModel::query()
                ->select(['id', 'manufacturer_id', 'name'])
                ->with(['manufacturer:id,name'])
                ->withCount('assets')
                ->find($deletingModelId);

            if ($deletingModel?->manufacturer) {
                $initialDeleteModel = [
                    'id' => $deletingModel->id,
                    'name' => $deletingModel->name,
                    'label' => $deletingModel->manufacturer->name.' / '.$deletingModel->name,
                    'assetsCount' => (int) $deletingModel->assets_count,
                    'action' => route('manufacturers.models.destroy', [$deletingModel->manufacturer, $deletingModel]),
                    'replacementId' => (string) old('replacement_manufacturer_model_id', ''),
                    'manufacturerId' => $deletingModel->manufacturer_id,
                ];
            }
        }

        return view('manufacturers.index', [
            'manufacturers' => $manufacturers,
            'manufacturerOptions' => $manufacturerOptions,
            'totalModelsCount' => (int) ManufacturerModel::query()->count(),
            'canManageCatalogue' => $canManageCatalogue,
            'replacementModelsUrl' => $canManageCatalogue
                ? route('manufacturers.replacement-models')
                : null,
            'initialDeleteManufacturer' => $initialDeleteManufacturer,
            'initialDeleteModel' => $initialDeleteModel,
        ]);
    }

    public function create(): View
    {
        return view('manufacturers.form', [
            'manufacturer' => new Manufacturer,
            'replacementManufacturers' => collect(),
            'assetCount' => 0,
            'modelCount' => 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Manufacturer::create($this->validated($request));

        return redirect()->route('manufacturers.index')->with('status', 'Manufacturer created.');
    }

    public function edit(Manufacturer $manufacturer): View
    {
        $manufacturer->load('aliases');

        return view('manufacturers.form', [
            'manufacturer' => $manufacturer,
            'replacementManufacturers' => Manufacturer::query()
                ->whereKeyNot($manufacturer->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'assetCount' => $manufacturer->assets()->count(),
            'modelCount' => $manufacturer->models()->count(),
        ]);
    }

    public function update(Request $request, Manufacturer $manufacturer): RedirectResponse
    {
        $manufacturer->update($this->validated($request, $manufacturer));

        return redirect()->route('manufacturers.index')->with('status', 'Manufacturer updated.');
    }

    public function destroy(
        DeleteManufacturerRequest $request,
        Manufacturer $manufacturer,
        ManufacturerCatalogueService $catalogue,
    ): RedirectResponse {
        $replacement = Manufacturer::query()->findOrFail(
            (int) $request->validated('replacement_manufacturer_id')
        );

        $catalogue->deleteManufacturer($manufacturer, $replacement);

        return redirect()->route('manufacturers.index')->with('status', 'Manufacturer deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?Manufacturer $manufacturer = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('manufacturers', 'name')->ignore($manufacturer?->id),
            ],
        ]);
    }
}

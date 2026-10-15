<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteManufacturerRequest;
use App\Models\Manufacturer;
use App\Services\ManufacturerCatalogueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManufacturerController extends Controller
{
    public function index(): View
    {
        $manufacturers = Manufacturer::query()
            ->with(['models' => fn ($query) => $query->withCount('assets')->orderBy('name')])
            ->withCount(['assets', 'models'])
            ->orderBy('name')
            ->get();

        return view('manufacturers.index', [
            'manufacturers' => $manufacturers,
            'replacementManufacturers' => $manufacturers,
            'allModels' => $manufacturers->flatMap(
                fn (Manufacturer $manufacturer) => $manufacturer->models->map(fn ($model) => [
                    'id' => $model->id,
                    'label' => $manufacturer->name.' / '.$model->name,
                    'manufacturer_id' => $manufacturer->id,
                ])
            )->values(),
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

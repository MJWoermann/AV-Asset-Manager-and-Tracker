<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteManufacturerModelRequest;
use App\Models\ConnectionType;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\SparePart;
use App\Services\AssetInterfaceService;
use App\Services\ManufacturerCatalogueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManufacturerModelController extends Controller
{
    public function index(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        $canManageCatalogue = $request->user()?->can('catalog:update') ?? false;

        $models = $manufacturer->models()
            ->select(['id', 'manufacturer_id', 'name'])
            ->withCount('assets')
            ->orderBy('name')
            ->get()
            ->map(fn (ManufacturerModel $model) => [
                'id' => $model->id,
                'name' => $model->name,
                'label' => $manufacturer->name.' / '.$model->name,
                'assets_count' => (int) $model->assets_count,
                'assets_url' => route('assets.index', ['manufacturer_model_id' => $model->id]),
                'edit_url' => $canManageCatalogue
                    ? route('manufacturers.models.edit', [$manufacturer, $model])
                    : null,
                'destroy_url' => $canManageCatalogue
                    ? route('manufacturers.models.destroy', [$manufacturer, $model])
                    : null,
            ])
            ->values();

        return response()->json(['data' => $models]);
    }

    public function replacementOptions(): JsonResponse
    {
        $options = Manufacturer::query()
            ->select(['id', 'name'])
            ->with(['models' => fn ($query) => $query->select(['id', 'manufacturer_id', 'name'])->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->flatMap(
                fn (Manufacturer $manufacturer) => $manufacturer->models->map(fn (ManufacturerModel $model) => [
                    'id' => $model->id,
                    'label' => $manufacturer->name.' / '.$model->name,
                ])
            )
            ->values();

        return response()->json(['data' => $options]);
    }

    public function create(Manufacturer $manufacturer): View
    {
        return view('manufacturers.models.form', [
            'manufacturer' => $manufacturer,
            'manufacturerModel' => new ManufacturerModel(['manufacturer_id' => $manufacturer->id]),
            'replacementModels' => collect(),
            'assetCount' => 0,
            'interfaceTemplates' => [],
            'connectionTypes' => ConnectionType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'linkedSpares' => collect(),
            'assignmentManufacturers' => collect(),
        ]);
    }

    public function store(Request $request, Manufacturer $manufacturer, AssetInterfaceService $interfaces): RedirectResponse
    {
        $data = $this->validated($request, $manufacturer);

        $manufacturerModel = $manufacturer->models()->create($data);

        if ($request->user()?->hasFeature('asset_connections')) {
            $interfaces->syncModelTemplates($manufacturerModel, $data['interface_templates'] ?? []);
        }

        return redirect()->route('manufacturers.index')->with('status', 'Model created.');
    }

    public function edit(Manufacturer $manufacturer, ManufacturerModel $manufacturerModel): View
    {
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $manufacturerModel->load(['interfaceTemplates', 'aliases']);

        $replacementModels = ManufacturerModel::query()
            ->with('manufacturer:id,name')
            ->whereKeyNot($manufacturerModel->id)
            ->orderBy('name')
            ->get();

        $interfaceTemplates = old('interface_templates');
        if ($interfaceTemplates === null) {
            $interfaceTemplates = $manufacturerModel->interfaceTemplates->map(fn ($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'notes' => $template->notes,
                'connection_type_id' => $template->connection_type_id,
            ])->values()->all();
        }

        $compatibleIds = $manufacturerModel->compatibleSpareParts()->pluck('spare_parts.id');
        $linkedSpares = $manufacturerModel->exists
            ? SparePart::query()
                ->where(function ($query) use ($manufacturerModel, $compatibleIds): void {
                    $query->where('manufacturer_model_id', $manufacturerModel->id);
                    if ($compatibleIds->isNotEmpty()) {
                        $query->orWhereIn('id', $compatibleIds);
                    }
                })
                ->orderBy('code')
                ->get()
            : collect();

        return view('manufacturers.models.form', [
            'manufacturer' => $manufacturer,
            'manufacturerModel' => $manufacturerModel,
            'replacementModels' => $replacementModels,
            'assetCount' => $manufacturerModel->assets()->count(),
            'interfaceTemplates' => $interfaceTemplates,
            'connectionTypes' => ConnectionType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'linkedSpares' => $linkedSpares,
            'assignmentManufacturers' => $this->assignmentManufacturers($manufacturer),
        ]);
    }

    public function update(
        Request $request,
        Manufacturer $manufacturer,
        ManufacturerModel $manufacturerModel,
        AssetInterfaceService $interfaces,
        ManufacturerCatalogueService $catalogue,
    ): RedirectResponse {
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $data = $this->validated($request, $manufacturer, $manufacturerModel);
        $saved = $this->saveModel($manufacturer, $manufacturerModel, $data, $catalogue);

        if ($request->user()?->hasFeature('asset_connections') && $saved->is($manufacturerModel)) {
            $interfaces->syncModelTemplates($saved, $data['interface_templates'] ?? []);
        }

        $redirect = redirect()
            ->route('manufacturers.index')
            ->with('status', $saved->is($manufacturerModel)
                ? ((int) $saved->manufacturer_id === (int) $manufacturer->id ? 'Model updated.' : 'Manufacturer assigned.')
                : 'Model assigned to the existing manufacturer model.');

        if ($saved->manufacturer_id !== $manufacturer->id) {
            $redirect->with('expand_manufacturer_id', (int) $saved->manufacturer_id);
        }

        return $redirect;
    }

    public function syncMissingInterfaces(
        Manufacturer $manufacturer,
        ManufacturerModel $manufacturerModel,
        AssetInterfaceService $interfaces,
    ): RedirectResponse {
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $created = $interfaces->syncMissingForManufacturerModel($manufacturerModel);

        return redirect()
            ->route('manufacturers.models.edit', [$manufacturer, $manufacturerModel])
            ->with('status', $created === 0
                ? 'No missing interfaces to sync.'
                : "Synced {$created} missing interface".($created === 1 ? '' : 's').' onto existing assets.');
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

        return redirect()
            ->route('manufacturers.index')
            ->with('status', 'Model deleted.')
            ->with('expand_manufacturer_id', (int) $manufacturer->id);
    }

    /** @return array<string, mixed> */
    protected function validated(
        Request $request,
        Manufacturer $manufacturer,
        ?ManufacturerModel $manufacturerModel = null,
    ): array {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('manufacturer_models', 'name')
                    ->where(fn ($query) => $query->where('manufacturer_id', $manufacturer->id))
                    ->ignore($manufacturerModel?->id),
            ],
        ];

        if ($request->user()?->hasFeature('asset_connections')) {
            $rules['interface_templates'] = ['nullable', 'array'];
            $rules['interface_templates.*.id'] = ['nullable', 'integer'];
            $rules['interface_templates.*.name'] = ['nullable', 'string', 'max:255'];
            $rules['interface_templates.*.notes'] = ['nullable', 'string'];
            $rules['interface_templates.*.connection_type_id'] = ['nullable', 'integer', 'exists:connection_types,id'];
        }

        if ($manufacturerModel !== null && $manufacturer->isUnspecified()) {
            $rules['manufacturer_id'] = [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '' || $value === '__new__') {
                        return;
                    }

                    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                        return;
                    }

                    $fail('Choose or add a manufacturer.');
                },
            ];
            $rules['new_manufacturer'] = [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $request->input('manufacturer_id') === '__new__'),
            ];
        }

        return $request->validate($rules, [
            'new_manufacturer.required' => 'Enter a manufacturer name.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveModel(
        Manufacturer $manufacturer,
        ManufacturerModel $manufacturerModel,
        array $data,
        ManufacturerCatalogueService $catalogue,
    ): ManufacturerModel {
        $target = $manufacturer->isUnspecified()
            ? $catalogue->manufacturerForModelAssignment(
                isset($data['manufacturer_id']) ? (string) $data['manufacturer_id'] : null,
                isset($data['new_manufacturer']) ? (string) $data['new_manufacturer'] : null,
            )
            : null;

        if ($target === null) {
            $manufacturerModel->update(['name' => $data['name']]);

            return $manufacturerModel;
        }

        return $catalogue->assignManufacturer($manufacturerModel, $target, (string) $data['name']);
    }

    /** @return Collection<int, Manufacturer> */
    protected function assignmentManufacturers(Manufacturer $manufacturer): Collection
    {
        if (! $manufacturer->isUnspecified()) {
            return collect();
        }

        return Manufacturer::query()
            ->where('name', '!=', Manufacturer::UNSPECIFIED_NAME)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}

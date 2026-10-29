<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteManufacturerModelRequest;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Services\AssetInterfaceService;
use App\Services\ManufacturerCatalogueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManufacturerModelApiController extends Controller
{
    public function store(Request $request, Manufacturer $manufacturer, AssetInterfaceService $interfaces): JsonResponse
    {
        $data = $this->validated($request, $manufacturer);
        $model = $manufacturer->models()->create($data);

        if ($request->user()?->hasFeature('asset_connections')) {
            $interfaces->syncModelTemplates($model, $data['interface_templates'] ?? []);
        }

        return response()->json($model, 201);
    }

    public function update(
        Request $request,
        Manufacturer $manufacturer,
        ManufacturerModel $manufacturerModel,
        AssetInterfaceService $interfaces,
        ManufacturerCatalogueService $catalogue,
    ): JsonResponse {
        abort_unless($request->user()->can('catalog:update'), 403);
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $data = $this->validated($request, $manufacturer, $manufacturerModel);
        $target = $manufacturer->isUnspecified()
            ? $catalogue->manufacturerForModelAssignment(
                isset($data['manufacturer_id']) ? (string) $data['manufacturer_id'] : null,
                isset($data['new_manufacturer']) ? (string) $data['new_manufacturer'] : null,
            )
            : null;
        $saved = $target === null
            ? tap($manufacturerModel, fn (ManufacturerModel $model) => $model->update(['name' => $data['name']]))
            : $catalogue->assignManufacturer($manufacturerModel, $target, (string) $data['name']);

        if ($request->user()?->hasFeature('asset_connections') && $saved->is($manufacturerModel)) {
            $interfaces->syncModelTemplates($saved, $data['interface_templates'] ?? []);
        }

        return response()->json($saved->fresh());
    }

    public function destroy(
        DeleteManufacturerModelRequest $request,
        Manufacturer $manufacturer,
        ManufacturerModel $manufacturerModel,
        ManufacturerCatalogueService $catalogue,
    ): JsonResponse {
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $replacement = ManufacturerModel::query()->findOrFail(
            (int) $request->validated('replacement_manufacturer_model_id')
        );

        $catalogue->deleteModel($manufacturerModel, $replacement);

        return response()->json(['message' => 'Model deleted.']);
    }

    public function syncInterfaces(
        Manufacturer $manufacturer,
        ManufacturerModel $manufacturerModel,
        AssetInterfaceService $interfaces,
    ): JsonResponse {
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $created = $interfaces->syncMissingForManufacturerModel($manufacturerModel);

        return response()->json(['message' => 'Interfaces synced.', 'created' => $created]);
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
}

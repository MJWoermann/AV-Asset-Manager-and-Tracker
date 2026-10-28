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
    ): JsonResponse {
        abort_unless($request->user()->can('catalog:update'), 403);
        abort_unless($manufacturerModel->manufacturer_id === $manufacturer->id, 404);

        $data = $this->validated($request, $manufacturer, $manufacturerModel);
        $manufacturerModel->update(['name' => $data['name']]);

        if ($request->user()?->hasFeature('asset_connections')) {
            $interfaces->syncModelTemplates($manufacturerModel, $data['interface_templates'] ?? []);
        }

        return response()->json($manufacturerModel->fresh());
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

        return $request->validate($rules);
    }
}

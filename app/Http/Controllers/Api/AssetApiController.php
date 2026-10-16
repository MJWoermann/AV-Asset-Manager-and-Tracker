<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkUpdateAssetsRequest;
use App\Http\Requests\StoreAssetMaintenanceLogRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Services\AssetMaintenanceLogService;
use App\Services\BulkAssetUpdateService;
use App\Services\ManufacturerCatalogueService;
use App\Services\SyncAssetCustomFields;
use App\Support\AssetHistoryFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssetApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->string('q')->toString() ?: null;

        $assets = Asset::query()
            ->with(['itemType:id,name,slug', 'location:id,name', 'manufacturer:id,name', 'manufacturerModel:id,name,manufacturer_id'])
            ->search($q)
            ->orderBySearchRelevance($q)
            ->paginate(min($request->integer('per_page', 50), 100));

        return response()->json($assets);
    }

    public function show(Asset $asset): JsonResponse
    {
        $asset->load([
            'itemType.fieldSets.definitions',
            'location',
            'children',
            'customFieldValues.definition',
            'parent',
            'manufacturer',
            'manufacturerModel',
        ]);

        return response()->json($asset);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager', 'scanner']), 403);

        $data = $this->validatedAsset($request);

        $asset = Asset::create($data);
        app(SyncAssetCustomFields::class)->sync($asset, $request);

        return response()->json($asset->load(['manufacturer', 'manufacturerModel', 'customFieldValues.definition']), 201);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager', 'scanner']), 403);

        $data = $this->validatedAsset($request, partial: true);

        $asset->update($data);
        app(SyncAssetCustomFields::class)->sync($asset, $request);

        return response()->json($asset->fresh(['manufacturer', 'manufacturerModel', 'customFieldValues.definition']));
    }

    public function destroy(Request $request, Asset $asset): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager']), 403);
        $asset->delete();

        return response()->json(['message' => 'Asset deleted.']);
    }

    public function bulkUpdate(BulkUpdateAssetsRequest $request, BulkAssetUpdateService $bulkUpdate): JsonResponse
    {
        $result = $bulkUpdate->handle($request->bulkPayload(), (int) $request->user()->id);

        return response()->json(['message' => 'Bulk update applied.', 'result' => $result]);
    }

    public function history(Request $request, Asset $asset): JsonResponse
    {
        $historyQuery = $asset->auditLogs()->with('user');
        AssetHistoryFilters::apply($historyQuery, $request);

        return response()->json($historyQuery->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function storeMaintenanceLog(
        StoreAssetMaintenanceLogRequest $request,
        Asset $asset,
        AssetMaintenanceLogService $maintenanceLogs,
    ): JsonResponse {
        $payload = $request->safe()->only(['type', 'summary', 'body', 'occurred_at', 'status']);

        $log = $maintenanceLogs->create(
            $asset,
            $payload,
            $request->user(),
            $request->file('files'),
        );

        return response()->json($log, 201);
    }

    public function destroyMaintenanceLog(Asset $asset, AssetMaintenanceLog $maintenanceLog): JsonResponse
    {
        abort_unless((int) $maintenanceLog->asset_id === (int) $asset->id, 404);
        $maintenanceLog->delete();

        return response()->json(['message' => 'Maintenance entry removed.']);
    }

    /** @return array<string, mixed> */
    protected function validatedAsset(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'item_type_id' => [$partial ? 'sometimes' : 'required', 'exists:item_types,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'parent_id' => ['nullable', 'exists:assets,id'],
            'status' => [$partial ? 'sometimes' : 'nullable', Rule::enum(AssetStatus::class)],
            'fmi_ast' => ['nullable', 'string', 'max:255'],
            'tp_barcode' => ['nullable', 'string', 'max:255'],
            'rig_tag' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:32'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'manufacturer_model_id' => ['nullable', 'exists:manufacturer_models,id'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if (array_key_exists('status', $data) && $data['status'] instanceof AssetStatus) {
            $data['status'] = $data['status']->value;
        }

        if (array_key_exists('manufacturer', $data) || array_key_exists('model', $data)) {
            $resolved = app(ManufacturerCatalogueService::class)->resolveFromNames(
                $data['manufacturer'] ?? null,
                $data['model'] ?? null,
            );
            $data['manufacturer_id'] = $resolved['manufacturer_id'];
            $data['manufacturer_model_id'] = $resolved['manufacturer_model_id'];
        }

        unset($data['manufacturer'], $data['model']);

        if (! empty($data['manufacturer_model_id'])) {
            $model = ManufacturerModel::query()->find((int) $data['manufacturer_model_id']);
            if ($model === null) {
                throw ValidationException::withMessages([
                    'manufacturer_model_id' => 'The selected model is invalid.',
                ]);
            }

            if (
                ! empty($data['manufacturer_id'])
                && (int) $model->manufacturer_id !== (int) $data['manufacturer_id']
            ) {
                throw ValidationException::withMessages([
                    'manufacturer_model_id' => 'The selected model does not belong to the chosen manufacturer.',
                ]);
            }

            $data['manufacturer_id'] = $model->manufacturer_id;
        } elseif (
            array_key_exists('manufacturer_id', $data)
            && $data['manufacturer_id'] !== null
            && ! Manufacturer::query()->whereKey($data['manufacturer_id'])->exists()
        ) {
            throw ValidationException::withMessages([
                'manufacturer_id' => 'The selected manufacturer is invalid.',
            ]);
        }

        return $data;
    }
}

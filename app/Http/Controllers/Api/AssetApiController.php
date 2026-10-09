<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->string('q')->toString() ?: null;

        $assets = Asset::query()
            ->with(['itemType:id,name,slug', 'location:id,name'])
            ->search($q)
            ->orderBySearchRelevance($q)
            ->paginate(min($request->integer('per_page', 50), 100));

        return response()->json($assets);
    }

    public function show(Asset $asset): JsonResponse
    {
        $asset->load(['itemType.fieldSets.definitions', 'location', 'children', 'customFieldValues.definition', 'parent']);

        return response()->json($asset);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager', 'scanner']), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'item_type_id' => ['required', 'exists:item_types,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'parent_id' => ['nullable', 'exists:assets,id'],
            'status' => ['nullable', 'string'],
            'fmi_ast' => ['nullable', 'string'],
            'tp_barcode' => ['nullable', 'string'],
            'rig_tag' => ['nullable', 'string'],
            'ip_address' => ['nullable', 'string'],
            'mac_address' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string'],
        ]);

        $asset = Asset::create($data);

        return response()->json($asset, 201);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager', 'scanner']), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'item_type_id' => ['sometimes', 'exists:item_types,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'parent_id' => ['nullable', 'exists:assets,id'],
            'status' => ['sometimes', 'string'],
            'fmi_ast' => ['nullable', 'string'],
            'tp_barcode' => ['nullable', 'string'],
            'rig_tag' => ['nullable', 'string'],
            'ip_address' => ['nullable', 'string'],
            'mac_address' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $asset->update($data);

        return response()->json($asset->fresh());
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteItemTypeRequest;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\ItemTypeAlias;
use App\Services\AssetInterfaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        $itemTypes = ItemType::with('fieldSets')->withCount('assets')->orderBy('name')->get();

        return response()->json(['data' => $itemTypes]);
    }

    public function show(ItemType $itemType): JsonResponse
    {
        $itemType->load(['fieldSets.definitions', 'interfaceTemplates']);

        return response()->json($itemType);
    }

    public function store(Request $request, AssetInterfaceService $interfaces): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager']), 403);

        $data = $this->validatedItemType($request);

        $itemType = ItemType::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        $itemType->fieldSets()->sync($data['field_sets'] ?? []);

        if ($request->user()?->hasFeature('asset_connections')) {
            $interfaces->syncTemplates($itemType, $data['interface_templates'] ?? []);
        }

        return response()->json($itemType->load('fieldSets'), 201);
    }

    public function update(Request $request, ItemType $itemType, AssetInterfaceService $interfaces): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager']), 403);

        $data = $this->validatedItemType($request, $itemType);

        $itemType->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        $itemType->fieldSets()->sync($data['field_sets'] ?? []);

        if ($request->user()?->hasFeature('asset_connections')) {
            $interfaces->syncTemplates($itemType, $data['interface_templates'] ?? []);
        }

        return response()->json($itemType->fresh(['fieldSets']));
    }

    public function destroy(DeleteItemTypeRequest $request, ItemType $itemType): JsonResponse
    {
        $replacement = ItemType::query()->findOrFail((int) $request->validated('replacement_item_type_id'));

        DB::transaction(function () use ($itemType, $replacement): void {
            Asset::query()
                ->where('item_type_id', $itemType->id)
                ->update(['item_type_id' => $replacement->id]);

            ItemTypeAlias::recordMerge($itemType, $replacement);

            $itemType->fieldSets()->detach();
            $itemType->delete();
        });

        return response()->json(['message' => 'Item type deleted.']);
    }

    public function syncInterfaces(ItemType $itemType, AssetInterfaceService $interfaces): JsonResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'inventory_manager']), 403);

        $created = $interfaces->syncMissingForItemType($itemType);

        return response()->json(['message' => 'Interfaces synced.', 'created' => $created]);
    }

    /** @return array<string, mixed> */
    protected function validatedItemType(Request $request, ?ItemType $itemType = null): array
    {
        $nameRule = ['required', 'string', 'max:255', 'unique:item_types,name'];
        if ($itemType) {
            $nameRule = ['required', 'string', 'max:255', 'unique:item_types,name,'.$itemType->id];
        }

        $rules = [
            'name' => $nameRule,
            'description' => ['nullable', 'string'],
            'field_sets' => ['nullable', 'array'],
            'field_sets.*' => ['exists:custom_field_sets,id'],
            'interface_templates' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}

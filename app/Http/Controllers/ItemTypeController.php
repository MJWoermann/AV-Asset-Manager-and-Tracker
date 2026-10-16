<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteItemTypeRequest;
use App\Models\Asset;
use App\Models\ConnectionType;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\ItemTypeAlias;
use App\Services\AssetInterfaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ItemTypeController extends Controller
{
    public function index(): View
    {
        $itemTypes = ItemType::with('fieldSets')->withCount('assets')->orderBy('name')->get();

        return view('item-types.index', compact('itemTypes'));
    }

    public function create(): View
    {
        return view('item-types.form', [
            'itemType' => new ItemType,
            'fieldSets' => CustomFieldSet::with('definitions')->orderBy('name')->get(),
            'replacementTypes' => collect(),
            'assetCount' => 0,
            'fileRows' => [],
            'interfaceTemplates' => [],
            'connectionTypes' => ConnectionType::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AssetInterfaceService $interfaces): RedirectResponse
    {
        $data = $this->validatedItemType($request);

        $attributes = [
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ];

        if ($request->user()?->hasDeveloperFeature('ru_visualiser')) {
            $attributes['supports_ru_visualiser'] = (bool) ($data['supports_ru_visualiser'] ?? false);
        }

        if ($request->user()?->hasDeveloperFeature('stock_consumables')) {
            $attributes['supports_stock_tracking'] = (bool) ($data['supports_stock_tracking'] ?? false);
        }

        if ($request->user()?->hasDeveloperFeature('rigging')) {
            $attributes['supports_rigging_calculator'] = (bool) ($data['supports_rigging_calculator'] ?? false);
            $attributes['rigging_weight_kg'] = $data['rigging_weight_kg'] ?? null;
        }

        $itemType = ItemType::create($attributes);
        $itemType->fieldSets()->sync($data['field_sets'] ?? []);

        if ($request->user()?->hasFeature('asset_connections')) {
            $interfaces->syncTemplates($itemType, $data['interface_templates'] ?? []);
        }

        return redirect()->route('item-types.index')->with('status', 'Item type created.');
    }

    public function edit(ItemType $itemType): View
    {
        $itemType->load(['fieldSets', 'files', 'interfaceTemplates', 'aliases']);

        $fileRows = $itemType->files->map(fn ($file): array => [
            'file' => $file,
            'inherited' => false,
            'link_id' => (int) $file->pivot->id,
            'note' => $file->pivot->note,
        ])->all();

        $interfaceTemplates = old('interface_templates');
        if ($interfaceTemplates === null) {
            $interfaceTemplates = $itemType->interfaceTemplates->map(fn ($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'notes' => $template->notes,
                'connection_type_id' => $template->connection_type_id,
            ])->values()->all();
        }

        return view('item-types.form', [
            'itemType' => $itemType,
            'fieldSets' => CustomFieldSet::with('definitions')->orderBy('name')->get(),
            'replacementTypes' => ItemType::query()
                ->whereKeyNot($itemType->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'assetCount' => $itemType->assets()->count(),
            'fileRows' => $fileRows,
            'interfaceTemplates' => $interfaceTemplates,
            'connectionTypes' => ConnectionType::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ItemType $itemType, AssetInterfaceService $interfaces): RedirectResponse
    {
        $data = $this->validatedItemType($request, $itemType);

        $attributes = [
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ];

        if ($request->user()?->hasDeveloperFeature('ru_visualiser')) {
            $attributes['supports_ru_visualiser'] = (bool) ($data['supports_ru_visualiser'] ?? false);
        }

        if ($request->user()?->hasDeveloperFeature('stock_consumables')) {
            $attributes['supports_stock_tracking'] = (bool) ($data['supports_stock_tracking'] ?? false);
        }

        if ($request->user()?->hasDeveloperFeature('rigging')) {
            $attributes['supports_rigging_calculator'] = (bool) ($data['supports_rigging_calculator'] ?? false);
            $attributes['rigging_weight_kg'] = $data['rigging_weight_kg'] ?? null;
        }

        $itemType->update($attributes);
        $itemType->fieldSets()->sync($data['field_sets'] ?? []);

        if ($request->user()?->hasFeature('asset_connections')) {
            $interfaces->syncTemplates($itemType, $data['interface_templates'] ?? []);
        }

        return redirect()->route('item-types.index')->with('status', 'Item type updated.');
    }

    public function syncMissingInterfaces(ItemType $itemType, AssetInterfaceService $interfaces): RedirectResponse
    {
        $created = $interfaces->syncMissingForItemType($itemType);

        return redirect()
            ->route('item-types.edit', $itemType)
            ->with('status', $created === 0
                ? 'No missing interfaces to sync.'
                : "Synced {$created} missing interface".($created === 1 ? '' : 's').' onto existing assets.');
    }

    /** @return array<string, mixed> */
    protected function validatedItemType(Request $request, ?ItemType $itemType = null): array
    {
        $nameRule = ['required', 'string', 'max:255', 'unique:item_types,name'];
        if ($itemType) {
            $nameRule = ['required', 'string', 'max:255', 'unique:item_types,name,'.$itemType->id];
        }

        if ($request->has('supports_ru_visualiser')) {
            $request->merge([
                'supports_ru_visualiser' => $request->boolean('supports_ru_visualiser'),
            ]);
        }

        if ($request->has('supports_stock_tracking')) {
            $request->merge([
                'supports_stock_tracking' => $request->boolean('supports_stock_tracking'),
            ]);
        }

        if ($request->has('supports_rigging_calculator')) {
            $request->merge([
                'supports_rigging_calculator' => $request->boolean('supports_rigging_calculator'),
            ]);
        }

        $rules = [
            'name' => $nameRule,
            'description' => ['nullable', 'string'],
            'field_sets' => ['nullable', 'array'],
            'field_sets.*' => ['exists:custom_field_sets,id'],
        ];

        if ($request->user()?->hasDeveloperFeature('ru_visualiser')) {
            $rules['supports_ru_visualiser'] = ['sometimes', 'boolean'];
        }

        if ($request->user()?->hasDeveloperFeature('stock_consumables')) {
            $rules['supports_stock_tracking'] = ['sometimes', 'boolean'];
        }

        if ($request->user()?->hasDeveloperFeature('rigging')) {
            $rules['supports_rigging_calculator'] = ['sometimes', 'boolean'];
            $rules['rigging_weight_kg'] = ['nullable', 'numeric', 'min:0'];
        }

        if ($request->user()?->hasFeature('asset_connections')) {
            $rules['interface_templates'] = ['nullable', 'array'];
            $rules['interface_templates.*.id'] = ['nullable', 'integer'];
            $rules['interface_templates.*.name'] = ['nullable', 'string', 'max:255'];
            $rules['interface_templates.*.notes'] = ['nullable', 'string'];
            $rules['interface_templates.*.connection_type_id'] = ['nullable', 'integer', 'exists:connection_types,id'];
        }

        return $request->validate($rules);
    }

    public function destroy(DeleteItemTypeRequest $request, ItemType $itemType): RedirectResponse
    {
        $replacementId = (int) $request->validated('replacement_item_type_id');
        $replacement = ItemType::query()->findOrFail($replacementId);

        DB::transaction(function () use ($itemType, $replacement): void {
            Asset::query()
                ->where('item_type_id', $itemType->id)
                ->update(['item_type_id' => $replacement->id]);

            ItemTypeAlias::recordMerge($itemType, $replacement);

            $itemType->fieldSets()->detach();
            $itemType->delete();
        });

        return redirect()->route('item-types.index')->with('status', 'Item type deleted. Imports using the old name will map to '.$replacement->name.'.');
    }
}

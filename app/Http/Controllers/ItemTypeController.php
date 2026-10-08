<?php

namespace App\Http\Controllers;

use App\Models\CustomFieldSet;
use App\Models\ItemType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ItemTypeController extends Controller
{
    public function index(): View
    {
        $itemTypes = ItemType::with('fieldSets')->orderBy('name')->get();

        return view('item-types.index', compact('itemTypes'));
    }

    public function create(): View
    {
        return view('item-types.form', [
            'itemType' => new ItemType,
            'fieldSets' => CustomFieldSet::with('definitions')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:item_types,name'],
            'description' => ['nullable', 'string'],
            'field_sets' => ['nullable', 'array'],
            'field_sets.*' => ['exists:custom_field_sets,id'],
        ]);

        $itemType = ItemType::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);
        $itemType->fieldSets()->sync($data['field_sets'] ?? []);

        return redirect()->route('item-types.index')->with('status', 'Item type created.');
    }

    public function edit(ItemType $itemType): View
    {
        $itemType->load('fieldSets');

        return view('item-types.form', [
            'itemType' => $itemType,
            'fieldSets' => CustomFieldSet::with('definitions')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ItemType $itemType): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:item_types,name,'.$itemType->id],
            'description' => ['nullable', 'string'],
            'field_sets' => ['nullable', 'array'],
            'field_sets.*' => ['exists:custom_field_sets,id'],
        ]);

        $itemType->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);
        $itemType->fieldSets()->sync($data['field_sets'] ?? []);

        return redirect()->route('item-types.index')->with('status', 'Item type updated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCustomFieldSets;
use App\Models\CustomFieldSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomFieldSetController extends Controller
{
    use ManagesCustomFieldSets;

    public function index(): View
    {
        $fieldSets = CustomFieldSet::query()
            ->withCount('definitions')
            ->with('itemTypes')
            ->orderBy('name')
            ->get();

        return view('custom-field-sets.index', compact('fieldSets'));
    }

    public function create(): View
    {
        return view('custom-field-sets.form', [
            'fieldSet' => new CustomFieldSet,
            'fieldTypes' => self::FIELD_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data): void {
            $fieldSet = CustomFieldSet::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSetSlug($data['name']),
                'description' => $data['description'] ?? null,
            ]);

            $this->syncDefinitions($fieldSet, $data['fields'] ?? []);
        });

        return redirect()->route('custom-field-sets.index')->with('status', 'Custom field set created.');
    }

    public function edit(CustomFieldSet $customFieldSet): View
    {
        $customFieldSet->load('definitions');

        return view('custom-field-sets.form', [
            'fieldSet' => $customFieldSet,
            'fieldTypes' => self::FIELD_TYPES,
        ]);
    }

    public function update(Request $request, CustomFieldSet $customFieldSet): RedirectResponse
    {
        $data = $this->validated($request, $customFieldSet);

        DB::transaction(function () use ($customFieldSet, $data): void {
            $customFieldSet->update([
                'name' => $data['name'],
                'slug' => $this->uniqueSetSlug($data['name'], $customFieldSet->id),
                'description' => $data['description'] ?? null,
            ]);

            $this->syncDefinitions($customFieldSet, $data['fields'] ?? []);
        });

        return redirect()->route('custom-field-sets.index')->with('status', 'Custom field set updated.');
    }

    public function destroy(CustomFieldSet $customFieldSet): RedirectResponse
    {
        $customFieldSet->delete();

        return redirect()->route('custom-field-sets.index')->with('status', 'Custom field set deleted.');
    }
}

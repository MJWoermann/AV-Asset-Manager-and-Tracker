<?php

namespace App\Http\Controllers;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomFieldSetController extends Controller
{
    /** @var list<string> */
    public const FIELD_TYPES = ['text', 'number', 'checkbox', 'select'];

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

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?CustomFieldSet $fieldSet = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('custom_field_sets', 'name')->ignore($fieldSet?->id),
            ],
            'description' => ['nullable', 'string'],
            'fields' => ['nullable', 'array'],
            'fields.*.id' => [
                'nullable',
                'integer',
                Rule::exists('custom_field_definitions', 'id')->where(
                    fn ($query) => $fieldSet
                        ? $query->where('custom_field_set_id', $fieldSet->id)
                        : $query->whereRaw('0 = 1')
                ),
            ],
            'fields.*.name' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::in(self::FIELD_TYPES)],
            'fields.*.options' => ['nullable', 'string', 'max:2000'],
            'fields.*.is_required' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function syncDefinitions(CustomFieldSet $fieldSet, array $fields): void
    {
        $keptIds = [];

        foreach (array_values($fields) as $index => $field) {
            $name = trim((string) $field['name']);
            $type = (string) $field['type'];
            $options = $this->parseOptions($type, $field['options'] ?? null);
            $isRequired = filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $definitionId = isset($field['id']) ? (int) $field['id'] : null;

            if ($definitionId) {
                $definition = CustomFieldDefinition::query()
                    ->where('custom_field_set_id', $fieldSet->id)
                    ->whereKey($definitionId)
                    ->firstOrFail();

                $definition->update([
                    'name' => $name,
                    'type' => $type,
                    'options' => $options,
                    'is_required' => $isRequired,
                    'sort_order' => $index,
                ]);
            } else {
                $definition = CustomFieldDefinition::create([
                    'custom_field_set_id' => $fieldSet->id,
                    'name' => $name,
                    'slug' => $this->uniqueDefinitionSlug($fieldSet, $name),
                    'type' => $type,
                    'options' => $options,
                    'is_required' => $isRequired,
                    'sort_order' => $index,
                ]);
            }

            $keptIds[] = $definition->id;
        }

        CustomFieldDefinition::query()
            ->where('custom_field_set_id', $fieldSet->id)
            ->when(
                $keptIds !== [],
                fn ($query) => $query->whereNotIn('id', $keptIds),
                fn ($query) => $query
            )
            ->delete();
    }

    protected function parseOptions(string $type, mixed $options): ?array
    {
        if ($type !== 'select') {
            return null;
        }

        $raw = is_string($options) ? $options : '';
        $parsed = collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->values()
            ->all();

        return $parsed === [] ? null : $parsed;
    }

    protected function uniqueSetSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'field-set';
        $slug = $base;
        $suffix = 2;

        while (
            CustomFieldSet::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function uniqueDefinitionSlug(CustomFieldSet $fieldSet, string $name): string
    {
        $base = Str::slug($name) ?: 'field';
        $slug = $base;
        $suffix = 2;

        while (
            CustomFieldDefinition::query()
                ->where('custom_field_set_id', $fieldSet->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}

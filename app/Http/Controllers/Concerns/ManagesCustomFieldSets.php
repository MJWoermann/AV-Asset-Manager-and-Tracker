<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ManagesCustomFieldSets
{
    /** @var list<string> */
    public const FIELD_TYPES = ['text', 'number', 'checkbox', 'select', 'url', 'spacer', 'divider'];

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?CustomFieldSet $fieldSet = null): array
    {
        $data = $request->validate([
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
            'fields.*.name' => ['nullable', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::in(self::FIELD_TYPES)],
            'fields.*.options' => ['nullable', 'string', 'max:2000'],
            'fields.*.is_required' => ['sometimes', 'boolean'],
        ]);

        $errors = [];
        foreach ($data['fields'] ?? [] as $index => $field) {
            $type = (string) ($field['type'] ?? '');
            $name = trim((string) ($field['name'] ?? ''));
            if ($name === '' && ! in_array($type, CustomFieldDefinition::LAYOUT_TYPES, true)) {
                $errors["fields.$index.name"] = 'The field name is required.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function syncDefinitions(CustomFieldSet $fieldSet, array $fields): void
    {
        $keptIds = [];

        foreach (array_values($fields) as $index => $field) {
            $type = (string) $field['type'];
            $isLayout = in_array($type, CustomFieldDefinition::LAYOUT_TYPES, true);
            $name = trim((string) ($field['name'] ?? ''));
            if ($name === '') {
                $name = match ($type) {
                    'spacer' => 'Spacer',
                    'divider' => 'Divider',
                    default => $name,
                };
            }
            $options = $isLayout ? null : $this->parseOptions($type, $field['options'] ?? null);
            $isRequired = $isLayout ? false : filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN);
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

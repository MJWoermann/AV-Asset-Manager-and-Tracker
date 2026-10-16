<?php

namespace App\Http\Requests;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateLocationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['update_parent', 'clear_parent', 'confirm_delete'] as $key) {
            if ($this->has($key)) {
                $merged[$key] = $this->boolean($key);
            }
        }

        if ($this->has('location_ids') && is_array($this->input('location_ids'))) {
            $merged['location_ids'] = collect($this->input('location_ids'))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        if ($this->has('parent_id') && $this->input('parent_id') === '') {
            $merged['parent_id'] = null;
        }

        $this->merge($merged);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'location_ids' => ['required', 'array', 'min:1'],
            'location_ids.*' => ['integer', 'distinct', 'exists:locations,id'],
            'action' => ['required', Rule::in(['update', 'delete', 'merge_duplicates'])],
            'confirm_delete' => ['exclude_unless:action,delete', 'accepted'],

            'update_parent' => ['sometimes', 'boolean'],
            'clear_parent' => ['sometimes', 'boolean'],
            'parent_id' => [
                'nullable',
                Rule::excludeIf(fn () => $this->boolean('clear_parent')),
                Rule::requiredIf(fn () => $this->input('action') === 'update' && $this->boolean('update_parent') && ! $this->boolean('clear_parent')),
                Rule::exists('locations', 'id')->whereNot('type', LocationType::Rack->value),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'parent_id.exists' => 'Racks cannot be a parent for another location.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('action') !== 'update') {
                return;
            }

            $updatingParent = $this->boolean('update_parent') || $this->boolean('clear_parent');

            if (! $updatingParent) {
                $validator->errors()->add('update_parent', 'Choose at least one field to update, or use delete / merge.');
            }

            if ($this->boolean('update_parent') && $this->boolean('clear_parent')) {
                $validator->errors()->add('clear_parent', 'Choose either update parent or clear parent, not both.');
            }

            $parentId = $this->input('parent_id');
            if (! $this->boolean('update_parent') || $parentId === null || $parentId === '') {
                return;
            }

            $parentId = (int) $parentId;
            $selected = collect($this->input('location_ids', []))->map(fn ($id) => (int) $id);

            if ($selected->contains($parentId)) {
                $validator->errors()->add('parent_id', 'Parent cannot be one of the selected locations.');

                return;
            }

            foreach ($selected as $locationId) {
                if (in_array($parentId, Location::selfAndDescendantIds($locationId), true)) {
                    $validator->errors()->add('parent_id', 'Parent cannot be a descendant of a selected location.');

                    return;
                }
            }
        });
    }

    /**
     * @return array{
     *     location_ids: array<int, int>,
     *     action: string,
     *     fields?: array<string, mixed>,
     * }
     */
    public function bulkPayload(): array
    {
        $payload = [
            'location_ids' => $this->validated('location_ids'),
            'action' => $this->validated('action'),
        ];

        if ($payload['action'] !== 'update') {
            return $payload;
        }

        $fields = [];

        if ($this->boolean('clear_parent')) {
            $fields['parent_id'] = null;
        } elseif ($this->boolean('update_parent')) {
            $fields['parent_id'] = $this->validated('parent_id');
        }

        $payload['fields'] = $fields;

        return $payload;
    }
}

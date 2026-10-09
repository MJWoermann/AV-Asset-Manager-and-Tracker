<?php

namespace App\Http\Requests;

use App\Enums\AssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($this->input('action') === 'delete') {
            return $user->hasAnyRole(['admin', 'inventory_manager']);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $booleanKeys = [
            'update_status',
            'update_location',
            'clear_location',
            'update_parent',
            'clear_parent',
            'update_test_tag_expiry',
            'clear_test_tag_expiry',
            'update_warranty_expiry',
            'clear_warranty_expiry',
            'update_notes',
            'clear_notes',
            'remove_from_list',
            'confirm_delete',
        ];

        $merged = [];

        foreach ($booleanKeys as $key) {
            if ($this->has($key)) {
                $merged[$key] = $this->boolean($key);
            }
        }

        if ($this->has('asset_ids') && is_array($this->input('asset_ids'))) {
            $merged['asset_ids'] = collect($this->input('asset_ids'))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        $this->merge($merged);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
            'action' => ['required', Rule::in(['update', 'delete'])],
            'confirm_delete' => ['exclude_unless:action,delete', 'accepted'],

            'update_status' => ['sometimes', 'boolean'],
            'status' => [
                Rule::requiredIf(fn () => $this->boolean('update_status')),
                'nullable',
                Rule::in(array_keys(AssetStatus::options())),
            ],

            'update_location' => ['sometimes', 'boolean'],
            'clear_location' => ['sometimes', 'boolean'],
            'location_id' => [
                'nullable',
                Rule::excludeIf(fn () => $this->boolean('clear_location')),
                Rule::requiredIf(fn () => $this->boolean('update_location') && ! $this->boolean('clear_location')),
                'exists:locations,id',
            ],

            'update_parent' => ['sometimes', 'boolean'],
            'clear_parent' => ['sometimes', 'boolean'],
            'parent_id' => [
                'nullable',
                Rule::excludeIf(fn () => $this->boolean('clear_parent')),
                Rule::requiredIf(fn () => $this->boolean('update_parent') && ! $this->boolean('clear_parent')),
                'exists:assets,id',
            ],

            'update_test_tag_expiry' => ['sometimes', 'boolean'],
            'clear_test_tag_expiry' => ['sometimes', 'boolean'],
            'test_tag_expiry' => [
                'nullable',
                Rule::excludeIf(fn () => $this->boolean('clear_test_tag_expiry')),
                Rule::requiredIf(fn () => $this->boolean('update_test_tag_expiry') && ! $this->boolean('clear_test_tag_expiry')),
                'date',
            ],

            'update_warranty_expiry' => ['sometimes', 'boolean'],
            'clear_warranty_expiry' => ['sometimes', 'boolean'],
            'warranty_expiry' => [
                'nullable',
                Rule::excludeIf(fn () => $this->boolean('clear_warranty_expiry')),
                Rule::requiredIf(fn () => $this->boolean('update_warranty_expiry') && ! $this->boolean('clear_warranty_expiry')),
                'date',
            ],

            'update_notes' => ['sometimes', 'boolean'],
            'clear_notes' => ['sometimes', 'boolean'],
            'notes' => [
                'nullable',
                Rule::excludeIf(fn () => $this->boolean('clear_notes')),
                Rule::requiredIf(fn () => $this->boolean('update_notes') && ! $this->boolean('clear_notes')),
                'string',
            ],

            'add_to_list_id' => ['nullable', 'exists:asset_lists,id'],
            'remove_from_list' => ['sometimes', 'boolean'],
            'current_list_id' => ['nullable', 'exists:asset_lists,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('action') === 'delete') {
                return;
            }

            $hasFieldUpdate = collect([
                'update_status',
                'update_location',
                'clear_location',
                'update_parent',
                'clear_parent',
                'update_test_tag_expiry',
                'clear_test_tag_expiry',
                'update_warranty_expiry',
                'clear_warranty_expiry',
                'update_notes',
                'clear_notes',
            ])->contains(fn (string $key) => $this->boolean($key));

            $hasListAction = filled($this->input('add_to_list_id')) || $this->boolean('remove_from_list');

            if (! $hasFieldUpdate && ! $hasListAction) {
                $validator->errors()->add('action', 'Select at least one field or list action to apply.');
            }

            if ($this->boolean('remove_from_list') && blank($this->input('current_list_id'))) {
                $validator->errors()->add('remove_from_list', 'Removing from a list is only available when viewing a list.');
            }

            $parentId = $this->input('parent_id');
            if ($this->boolean('update_parent') && filled($parentId)) {
                $selected = collect($this->input('asset_ids', []))->map(fn ($id) => (int) $id);
                if ($selected->contains((int) $parentId)) {
                    $validator->errors()->add('parent_id', 'Parent asset cannot be one of the selected assets.');
                }
            }
        });
    }

    /**
     * @return array{
     *     asset_ids: array<int, int>,
     *     action: string,
     *     fields?: array<string, mixed>,
     *     add_to_list_id?: int|null,
     *     remove_from_list_id?: int|null,
     * }
     */
    public function bulkPayload(): array
    {
        $payload = [
            'asset_ids' => $this->validated('asset_ids'),
            'action' => $this->validated('action'),
        ];

        if ($payload['action'] === 'delete') {
            return $payload;
        }

        $fields = [];

        if ($this->boolean('update_status')) {
            $fields['status'] = $this->validated('status');
        }

        if ($this->boolean('clear_location')) {
            $fields['location_id'] = null;
        } elseif ($this->boolean('update_location')) {
            $fields['location_id'] = $this->validated('location_id');
        }

        if ($this->boolean('clear_parent')) {
            $fields['parent_id'] = null;
        } elseif ($this->boolean('update_parent')) {
            $fields['parent_id'] = $this->validated('parent_id');
        }

        if ($this->boolean('clear_test_tag_expiry')) {
            $fields['test_tag_expiry'] = null;
        } elseif ($this->boolean('update_test_tag_expiry')) {
            $fields['test_tag_expiry'] = $this->validated('test_tag_expiry');
        }

        if ($this->boolean('clear_warranty_expiry')) {
            $fields['warranty_expiry'] = null;
        } elseif ($this->boolean('update_warranty_expiry')) {
            $fields['warranty_expiry'] = $this->validated('warranty_expiry');
        }

        if ($this->boolean('clear_notes')) {
            $fields['notes'] = null;
        } elseif ($this->boolean('update_notes')) {
            $fields['notes'] = $this->validated('notes');
        }

        $payload['fields'] = $fields;
        $payload['add_to_list_id'] = $this->validated('add_to_list_id');
        $payload['remove_from_list_id'] = $this->boolean('remove_from_list')
            ? $this->validated('current_list_id')
            : null;

        return $payload;
    }
}

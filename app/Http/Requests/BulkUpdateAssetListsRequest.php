<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateAssetListsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('lists:bulk') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('update_department')) {
            $merged['update_department'] = $this->boolean('update_department');
        }

        if ($this->has('asset_list_ids') && is_array($this->input('asset_list_ids'))) {
            $merged['asset_list_ids'] = collect($this->input('asset_list_ids'))
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
            'asset_list_ids' => ['required', 'array', 'min:1'],
            'asset_list_ids.*' => ['integer', 'distinct', 'exists:asset_lists,id'],
            'action' => ['required', Rule::in(['update'])],
            'update_department' => ['sometimes', 'boolean'],
            'department_id' => [
                Rule::requiredIf(fn () => $this->boolean('update_department')),
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->boolean('update_department')) {
                $validator->errors()->add('update_department', 'Select at least one field to update.');
            }
        });
    }

    /**
     * @return array{
     *     asset_list_ids: array<int, int>,
     *     action: string,
     *     fields: array<string, mixed>,
     * }
     */
    public function bulkPayload(): array
    {
        $fields = [];

        if ($this->boolean('update_department')) {
            $fields['department_id'] = (int) $this->validated('department_id');
        }

        return [
            'asset_list_ids' => $this->validated('asset_list_ids'),
            'action' => $this->validated('action'),
            'fields' => $fields,
        ];
    }
}

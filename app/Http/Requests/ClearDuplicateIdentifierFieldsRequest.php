<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Services\AssetDuplicateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClearDuplicateIdentifierFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets:delete') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('asset_ids') && is_array($this->input('asset_ids'))) {
            $merged['asset_ids'] = collect($this->input('asset_ids'))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        if ($this->has('fields') && is_array($this->input('fields'))) {
            $merged['fields'] = collect($this->input('fields'))
                ->map(fn ($field) => (string) $field)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if ($this->has('confirm_clear')) {
            $merged['confirm_clear'] = $this->boolean('confirm_clear');
        }

        $this->merge($merged);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', Rule::in(AssetDuplicateService::MATCH_KEYS)],
            'confirm_clear' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ids = collect($this->input('asset_ids', []))->map(fn ($id) => (int) $id);
            $assets = Asset::query()->whereIn('id', $ids)->get();

            if ($assets->count() !== $ids->count()) {
                $validator->errors()->add('asset_ids', 'One or more selected assets are not available in your department.');

                return;
            }

            $departmentIds = $assets
                ->map(fn (Asset $asset) => $asset->department_id !== null ? (int) $asset->department_id : null)
                ->unique();

            if ($departmentIds->count() > 1) {
                $validator->errors()->add('asset_ids', 'All selected assets must belong to the same department.');
            }
        });
    }

    /**
     * @return array{asset_ids: list<int>, fields: list<string>}
     */
    public function clearPayload(): array
    {
        return [
            'asset_ids' => array_map('intval', $this->validated('asset_ids')),
            'fields' => array_values($this->validated('fields')),
        ];
    }
}

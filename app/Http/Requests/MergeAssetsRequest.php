<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Services\AssetMergeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MergeAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false;
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

        if ($this->has('confirm_merge')) {
            $merged['confirm_merge'] = $this->boolean('confirm_merge');
        }

        if ($this->has('set_quantity_to_total')) {
            $merged['set_quantity_to_total'] = $this->boolean('set_quantity_to_total');
        }

        $this->merge($merged);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'asset_ids' => ['required', 'array', 'min:2', 'max:'.AssetMergeService::MAX_ASSETS],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
            'survivor_id' => ['required', 'integer', 'exists:assets,id'],
            'confirm_merge' => ['accepted'],
            'set_quantity_to_total' => ['sometimes', 'boolean'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ids = collect($this->input('asset_ids', []))->map(fn ($id) => (int) $id);
            $survivorId = (int) $this->input('survivor_id');

            if (! $ids->contains($survivorId)) {
                $validator->errors()->add('survivor_id', 'The survivor asset must be one of the selected assets.');

                return;
            }

            $assets = Asset::query()->whereIn('id', $ids)->get();

            if ($assets->count() !== $ids->count()) {
                $validator->errors()->add('asset_ids', 'One or more selected assets are not available in your department.');

                return;
            }

            $departmentIds = $assets
                ->map(fn (Asset $asset) => $asset->department_id !== null ? (int) $asset->department_id : null)
                ->unique();

            if ($departmentIds->count() > 1) {
                $validator->errors()->add('asset_ids', 'All assets in a merge must belong to the same department.');
            }

            $allowedSources = $ids
                ->map(fn (int $id) => (string) $id)
                ->push(AssetMergeService::EMPTY_SOURCE)
                ->all();

            foreach (AssetMergeService::MERGEABLE_FIELDS as $field) {
                $source = $this->input("fields.{$field}");
                if ($source === null || $source === '') {
                    continue;
                }
                if (! in_array((string) $source, $allowedSources, true)) {
                    $validator->errors()->add("fields.{$field}", 'Invalid field source for '.$field.'.');
                }
                if (
                    in_array($field, AssetMergeService::REQUIRED_FIELDS, true)
                    && (string) $source === AssetMergeService::EMPTY_SOURCE
                ) {
                    $validator->errors()->add("fields.{$field}", 'This field cannot be cleared during a merge.');
                }
            }

            foreach ($this->input('custom_fields', []) as $definitionId => $source) {
                if ($source === null || $source === '') {
                    continue;
                }
                if (! in_array((string) $source, $allowedSources, true)) {
                    $validator->errors()->add(
                        "custom_fields.{$definitionId}",
                        'Invalid custom field source.'
                    );
                }
            }
        });
    }

    /**
     * @return array{
     *     asset_ids: list<int>,
     *     survivor_id: int,
     *     fields: array<string, string>,
     *     custom_fields: array<int, string>,
     *     set_quantity_to_total: bool
     * }
     */
    public function mergePayload(): array
    {
        $fields = [];
        foreach (AssetMergeService::MERGEABLE_FIELDS as $field) {
            $source = $this->input("fields.{$field}");
            if ($source === null || $source === '') {
                $fields[$field] = AssetMergeService::EMPTY_SOURCE;
            } else {
                $fields[$field] = (string) $source;
            }
        }

        $custom = [];
        foreach ($this->input('custom_fields', []) as $definitionId => $source) {
            $definitionId = (int) $definitionId;
            if ($definitionId < 1) {
                continue;
            }
            $custom[$definitionId] = ($source === null || $source === '')
                ? AssetMergeService::EMPTY_SOURCE
                : (string) $source;
        }

        return [
            'asset_ids' => array_map('intval', $this->validated('asset_ids')),
            'survivor_id' => (int) $this->validated('survivor_id'),
            'fields' => $fields,
            'custom_fields' => $custom,
            'set_quantity_to_total' => $this->boolean('set_quantity_to_total'),
        ];
    }
}

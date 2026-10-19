<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Services\AssetMergeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PreviewAssetMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('asset_ids') && is_array($this->input('asset_ids'))) {
            $this->merge([
                'asset_ids' => collect($this->input('asset_ids'))
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'asset_ids' => ['required', 'array', 'min:2', 'max:'.AssetMergeService::MAX_ASSETS],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
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
                ->map(fn ($asset) => $asset->department_id !== null ? (int) $asset->department_id : null)
                ->unique();

            if ($departmentIds->count() > 1) {
                $validator->errors()->add('asset_ids', 'All assets in a merge must belong to the same department.');
            }
        });
    }
}

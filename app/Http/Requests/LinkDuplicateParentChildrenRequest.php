<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LinkDuplicateParentChildrenRequest extends FormRequest
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

        if ($this->has('confirm_parent_child')) {
            $merged['confirm_parent_child'] = $this->boolean('confirm_parent_child');
        }

        $this->merge($merged);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'asset_ids' => ['required', 'array', 'min:2'],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
            'parent_id' => ['required', 'integer', 'exists:assets,id'],
            'confirm_parent_child' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ids = collect($this->input('asset_ids', []))->map(fn ($id) => (int) $id);
            $parentId = (int) $this->input('parent_id');

            if (! $ids->contains($parentId)) {
                $validator->errors()->add('parent_id', 'The parent must be one of the selected assets.');

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
                $validator->errors()->add('asset_ids', 'All selected assets must belong to the same department.');
            }
        });
    }

    /**
     * @return array{asset_ids: list<int>, parent_id: int}
     */
    public function linkPayload(): array
    {
        return [
            'asset_ids' => array_map('intval', $this->validated('asset_ids')),
            'parent_id' => (int) $this->validated('parent_id'),
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesShelfTagFeature;
use App\Services\ShelfTagRenderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShelfTagPrintRequest extends FormRequest
{
    use AuthorizesShelfTagFeature;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'shelf_tag_layout_id' => ['required', 'integer', 'exists:shelf_tag_layouts,id'],
            'asset_list_id' => ['required', 'integer', 'exists:asset_lists,id'],
            'mode' => ['required', 'string', Rule::in(['all', 'selected'])],
            'asset_ids' => [
                Rule::requiredIf(fn () => $this->input('mode') === 'selected'),
                'nullable',
                'array',
                'min:1',
                'max:'.ShelfTagRenderService::MAX_BATCH,
            ],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
        ];
    }

    /** @return list<int>|null */
    public function selectedAssetIds(): ?array
    {
        if ($this->validated('mode') === 'all') {
            return null;
        }

        return array_map('intval', $this->validated('asset_ids') ?? []);
    }
}

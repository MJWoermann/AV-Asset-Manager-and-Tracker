<?php

namespace App\Http\Requests;

use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRuPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasDeveloperFeature('ru_visualiser') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('confirm_parent_change')) {
            $this->merge([
                'confirm_parent_change' => $this->boolean('confirm_parent_change'),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'face' => ['sometimes', Rule::enum(RuPlacementFace::class)],
            'start_ru' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'ru_height' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'kind' => ['sometimes', Rule::enum(RuPlacementKind::class)],
            'child_asset_id' => [
                'nullable',
                'required_if:kind,asset',
                'integer',
                'exists:assets,id',
            ],
            'note' => [
                'nullable',
                'required_if:kind,note',
                'string',
                'max:5000',
            ],
            'confirm_parent_change' => ['sometimes', 'boolean'],
        ];
    }
}

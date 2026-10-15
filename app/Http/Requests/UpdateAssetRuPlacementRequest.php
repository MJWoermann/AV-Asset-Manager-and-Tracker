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
        $merge = [];

        if ($this->has('confirm_parent_change')) {
            $merge['confirm_parent_change'] = $this->boolean('confirm_parent_change');
        }

        if ($this->has('full_depth')) {
            $merge['full_depth'] = $this->boolean('full_depth');
        }

        if ($merge !== []) {
            $this->merge($merge);
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
            'full_depth' => ['sometimes', 'boolean'],
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

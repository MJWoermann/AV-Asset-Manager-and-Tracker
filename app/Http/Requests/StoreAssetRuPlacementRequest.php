<?php

namespace App\Http\Requests;

use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRuPlacementRequest extends FormRequest
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
            'face' => ['required', Rule::enum(RuPlacementFace::class)],
            'start_ru' => ['required', 'integer', 'min:1', 'max:65535'],
            'ru_height' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'kind' => ['required', Rule::enum(RuPlacementKind::class)],
            'child_asset_id' => [
                'nullable',
                'required_if:kind,asset',
                'prohibited_unless:kind,asset',
                'integer',
                'exists:assets,id',
            ],
            'note' => [
                'nullable',
                'required_if:kind,note',
                'prohibited_unless:kind,note',
                'string',
                'max:5000',
            ],
            'confirm_parent_change' => ['sometimes', 'boolean'],
        ];
    }
}

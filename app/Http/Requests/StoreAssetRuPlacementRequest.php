<?php

namespace App\Http\Requests;

use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use App\Enums\RuPlacementWidth;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssetRuPlacementRequest extends FormRequest
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
            'face' => ['required', Rule::enum(RuPlacementFace::class)],
            'start_ru' => ['required', 'integer', 'min:1', 'max:65535'],
            'ru_height' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'full_depth' => [
                'sometimes',
                'boolean',
                'prohibited_unless:kind,asset',
            ],
            'width' => ['sometimes', Rule::enum(RuPlacementWidth::class)],
            'slot' => ['sometimes', 'integer', 'min:1', 'max:3'],
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

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['width', 'slot'])) {
                    return;
                }

                $width = RuPlacementWidth::tryFrom((string) $this->input('width', RuPlacementWidth::Full->value))
                    ?? RuPlacementWidth::Full;
                $slotProvided = $this->exists('slot');

                if ($width === RuPlacementWidth::Full && ! $slotProvided) {
                    return;
                }

                $slot = $slotProvided ? (int) $this->input('slot') : 1;
                if ($width->acceptsSlot($slot)) {
                    return;
                }

                $validator->errors()->add(
                    'slot',
                    $width === RuPlacementWidth::Full
                        ? RuPlacementWidth::FULL_SLOT_MESSAGE
                        : RuPlacementWidth::INVALID_SLOT_MESSAGE,
                );
            },
        ];
    }
}

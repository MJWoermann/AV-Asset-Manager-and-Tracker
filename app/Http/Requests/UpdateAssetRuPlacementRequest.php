<?php

namespace App\Http\Requests;

use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use App\Enums\RuPlacementWidth;
use App\Models\AssetRuPlacement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

        if ($this->has('move_row')) {
            $merge['move_row'] = $this->boolean('move_row');
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
            'width' => ['sometimes', Rule::enum(RuPlacementWidth::class)],
            'slot' => ['sometimes', 'integer', 'min:1', 'max:3'],
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
            'move_row' => ['sometimes', 'boolean'],
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

                $placement = $this->route('ruPlacement');
                $widthValue = $this->input('width');
                $width = is_string($widthValue) && $widthValue !== ''
                    ? RuPlacementWidth::tryFrom($widthValue)
                    : ($placement instanceof AssetRuPlacement ? $placement->width : RuPlacementWidth::Full);

                if (! $width instanceof RuPlacementWidth) {
                    return;
                }

                $slotProvided = $this->exists('slot');
                if ($width === RuPlacementWidth::Full && ! $slotProvided) {
                    return;
                }

                $slot = $slotProvided
                    ? (int) $this->input('slot')
                    : (int) ($placement instanceof AssetRuPlacement ? $placement->slot : 1);

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

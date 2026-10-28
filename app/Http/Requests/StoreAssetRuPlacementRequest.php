<?php

namespace App\Http\Requests;

use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
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

        // #region agent log
        try {
            file_put_contents(base_path('debug-ca5636.log'), json_encode([
                'sessionId' => 'ca5636',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H1-H3',
                'location' => 'StoreAssetRuPlacementRequest.php:prepareForValidation',
                'message' => 'store ru placement input',
                'data' => [
                    'has_face' => $this->has('face'),
                    'face' => $this->input('face'),
                    'full_depth' => $this->input('full_depth'),
                    'kind' => $this->input('kind'),
                    'keys' => array_values(array_diff(array_keys($this->all()), ['_token'])),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
        }
        // #endregion
    }

    protected function failedValidation(Validator $validator): void
    {
        // #region agent log
        try {
            file_put_contents(base_path('debug-ca5636.log'), json_encode([
                'sessionId' => 'ca5636',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H1-H3',
                'location' => 'StoreAssetRuPlacementRequest.php:failedValidation',
                'message' => 'store ru placement validation failed',
                'data' => [
                    'error_keys' => array_keys($validator->errors()->toArray()),
                    'face_errors' => $validator->errors()->get('face'),
                    'has_face' => $this->has('face'),
                    'face' => $this->input('face'),
                    'full_depth' => $this->input('full_depth'),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
        }
        // #endregion

        parent::failedValidation($validator);
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

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteManufacturerModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $modelId = (int) $this->route('manufacturer_model')->id;

        return [
            'replacement_manufacturer_model_id' => [
                'required',
                'integer',
                Rule::exists('manufacturer_models', 'id')->where(
                    fn ($query) => $query->where('id', '!=', $modelId)
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'replacement_manufacturer_model_id.required' => 'Choose a replacement model for assets that use this model.',
            'replacement_manufacturer_model_id.exists' => 'Choose a valid replacement model.',
        ];
    }
}

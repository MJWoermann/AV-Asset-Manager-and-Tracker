<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteManufacturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('catalog:update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $manufacturerId = (int) $this->route('manufacturer')->id;

        return [
            'replacement_manufacturer_id' => [
                'required',
                'integer',
                Rule::exists('manufacturers', 'id')->where(
                    fn ($query) => $query->where('id', '!=', $manufacturerId)
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'replacement_manufacturer_id.required' => 'Choose a replacement manufacturer for models and assets.',
            'replacement_manufacturer_id.exists' => 'Choose a valid replacement manufacturer.',
        ];
    }
}

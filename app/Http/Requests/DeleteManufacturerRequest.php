<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Models\Manufacturer;
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
                Rule::excludeIf(fn (): bool => ! $this->requiresReplacement()),
                Rule::requiredIf(fn (): bool => $this->requiresReplacement()),
                'integer',
                Rule::exists('manufacturers', 'id')->where(
                    fn ($query) => $query->where('id', '!=', $manufacturerId)
                ),
            ],
        ];
    }

    public function requiresReplacement(): bool
    {
        $manufacturer = $this->route('manufacturer');

        if (! $manufacturer instanceof Manufacturer) {
            return true;
        }

        return Asset::withoutDepartmentScope()
            ->where('manufacturer_id', $manufacturer->id)
            ->exists();
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

<?php

namespace App\Http\Requests;

use App\Models\CheckoutPurpose;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCheckoutPurposeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $purpose = $this->route('checkoutPurpose');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('checkout_purposes', 'name')->ignore(
                    $purpose instanceof CheckoutPurpose ? $purpose->id : null
                ),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

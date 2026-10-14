<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = (string) $this->input('type');

        return [
            'type' => [
                'required',
                Rule::in([
                    StockMovementType::Count->value,
                    StockMovementType::Restock->value,
                    StockMovementType::Breakage->value,
                ]),
            ],
            'quantity' => ['required', 'integer', 'min:0'],
            'notes' => [
                Rule::requiredIf($type === StockMovementType::Breakage->value),
                'nullable',
                'string',
                'max:5000',
            ],
            'external_po_number' => ['nullable', 'string', 'max:255'],
            'external_po_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}

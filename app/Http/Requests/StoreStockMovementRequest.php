<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use App\Support\StockTracking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! StockTracking::enabledFor($user)) {
            return false;
        }

        return $user?->hasAnyRole(['admin', 'inventory_manager', 'scanner']) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = (string) $this->input('type');

        return [
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => [
                Rule::requiredIf(in_array($type, [
                    StockMovementType::Count->value,
                    StockMovementType::Restock->value,
                    StockMovementType::Breakage->value,
                ], true)),
                'nullable',
                'integer',
                'min:0',
            ],
            'quantity_delta' => [
                Rule::requiredIf($type === StockMovementType::Adjustment->value),
                'nullable',
                'integer',
            ],
            'notes' => [
                Rule::requiredIf($type === StockMovementType::Breakage->value),
                'nullable',
                'string',
                'max:5000',
            ],
            'external_po_number' => ['nullable', 'string', 'max:255'],
            'external_po_url' => ['nullable', 'url', 'max:2048'],
            'pdfs' => ['nullable', 'array', 'max:5'],
            'pdfs.*' => [
                'file',
                'mimes:pdf',
                'max:'.(int) config('attachments.max_kilobytes', 25600),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\SparePartMovementType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSparePartMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'inventory_manager', 'scanner']) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = (string) $this->input('type');
        $requiresAsset = in_array($type, [
            SparePartMovementType::Use->value,
            SparePartMovementType::Replace->value,
        ], true);

        return [
            'type' => ['required', Rule::enum(SparePartMovementType::class)],
            'quantity' => [
                Rule::requiredIf(in_array($type, [
                    SparePartMovementType::Count->value,
                    SparePartMovementType::Restock->value,
                    SparePartMovementType::Use->value,
                    SparePartMovementType::Replace->value,
                ], true)),
                'nullable',
                'integer',
                'min:1',
            ],
            'quantity_delta' => [
                Rule::requiredIf($type === SparePartMovementType::Adjustment->value),
                'nullable',
                'integer',
            ],
            'against_asset_id' => [
                Rule::requiredIf($requiresAsset),
                'nullable',
                'integer',
                'exists:assets,id',
            ],
            'reason' => [
                Rule::requiredIf($requiresAsset),
                'nullable',
                'string',
                'max:2000',
            ],
            'ticket_ref' => ['nullable', 'string', 'max:255'],
            'quoting_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'pdfs' => ['nullable', 'array', 'max:5'],
            'pdfs.*' => [
                'file',
                'mimes:pdf',
                'max:'.(int) config('attachments.max_kilobytes', 25600),
            ],
        ];
    }
}

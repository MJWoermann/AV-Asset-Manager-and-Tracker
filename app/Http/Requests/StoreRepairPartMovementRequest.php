<?php

namespace App\Http\Requests;

use App\Enums\SparePartMovementType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairPartMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'spare_part_id' => ['required', 'integer', Rule::exists('spare_parts', 'id')],
            'type' => ['required', Rule::in([
                SparePartMovementType::Use->value,
                SparePartMovementType::Replace->value,
            ])],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

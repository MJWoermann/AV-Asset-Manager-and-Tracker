<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'safety_notes' => ['nullable', 'string', 'max:10000'],
            'tools_required' => ['nullable', 'string', 'max:5000'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'manufacturer_model_id' => ['nullable', 'integer', Rule::exists('manufacturer_models', 'id')],
            'item_type_id' => ['nullable', 'integer', Rule::exists('item_types', 'id')],
            'spare_part_id' => ['nullable', 'integer', Rule::exists('spare_parts', 'id')],
            'steps' => ['nullable', 'array'],
            'steps.*.title' => ['required_with:steps', 'string', 'max:255'],
            'steps.*.body' => ['nullable', 'string', 'max:10000'],
            'steps.*.is_required' => ['nullable', 'boolean'],
            'steps.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'recommended_parts' => ['nullable', 'array'],
            'recommended_parts.*.spare_part_id' => ['required_with:recommended_parts', 'integer', Rule::exists('spare_parts', 'id')],
            'recommended_parts.*.quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'recommended_parts.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $steps = collect($this->input('steps', []))
            ->map(function ($step) {
                if (! is_array($step)) {
                    return $step;
                }
                $step['is_required'] = filter_var($step['is_required'] ?? true, FILTER_VALIDATE_BOOLEAN);

                return $step;
            })
            ->all();

        $this->merge(['steps' => $steps]);
    }
}

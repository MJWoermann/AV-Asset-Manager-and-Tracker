<?php

namespace App\Http\Requests;

use App\Enums\SparePartKind;
use App\Enums\SparePartLifecycleStatus;
use App\Enums\SparePartSeverity;
use App\Enums\SparePartStandbyMode;
use App\Enums\SparePartStockStatus;
use App\Support\SparePartLuhn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSparePartRequest extends FormRequest
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
            'code' => [
                'nullable',
                'string',
                'max:32',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }
                    $normalized = preg_replace('/\s+/', '', trim((string) $value)) ?? '';
                    if (! SparePartLuhn::isValid($normalized)) {
                        $fail('Spare code must pass the Luhn check.');
                    }
                },
                Rule::unique('spare_parts', 'code'),
            ],
            'kind' => ['required', Rule::enum(SparePartKind::class)],
            'component' => ['required', 'string', 'max:255'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'manufacturer_model_id' => ['nullable', 'integer', 'exists:manufacturer_models,id'],
            'product_code' => ['nullable', 'string', 'max:255'],
            'quantity_deployed' => ['nullable', 'integer', 'min:0'],
            'quantity_on_hand' => ['nullable', 'integer', 'min:0'],
            'min_holding' => ['nullable', 'integer', 'min:0'],
            'max_holding' => ['nullable', 'integer', 'min:0'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'shelf_location' => ['nullable', 'string', 'max:255'],
            'stock_status' => ['nullable', Rule::enum(SparePartStockStatus::class)],
            'standby_mode' => ['nullable', Rule::enum(SparePartStandbyMode::class)],
            'supplier' => ['nullable', 'string', 'max:255'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lead_time' => ['nullable', 'string', 'max:255'],
            'replacement_time' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'replacement_time_minutes' => ['nullable', 'integer', 'min:0'],
            'severity' => ['nullable', Rule::enum(SparePartSeverity::class)],
            'lifecycle_status' => ['nullable', Rule::enum(SparePartLifecycleStatus::class)],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'shelf_tag_layout_id' => ['nullable', 'integer', 'exists:shelf_tag_layouts,id'],
            'ordered_at' => ['nullable', 'date'],
            'expected_arrival_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'compatible_model_ids' => ['nullable', 'array'],
            'compatible_model_ids.*' => ['integer', 'exists:manufacturer_models,id'],
            'compatible_asset_ids' => ['nullable', 'array'],
            'compatible_asset_ids.*' => ['integer', 'exists:assets,id'],
        ];
    }
}

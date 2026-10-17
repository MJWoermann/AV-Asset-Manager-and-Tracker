<?php

namespace App\Http\Requests;

use App\Enums\SparePartKind;
use App\Enums\SparePartLifecycleStatus;
use App\Enums\SparePartSeverity;
use App\Enums\SparePartStandbyMode;
use App\Enums\SparePartStockStatus;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Services\ManufacturerCatalogueService;
use App\Support\SparePartLuhn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $creatingManufacturer = $this->input('manufacturer_id') === '__new__';
        $creatingModel = $this->input('manufacturer_model_id') === '__new__';

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
            'manufacturer_id' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) use ($creatingManufacturer): void {
                if ($value === null || $value === '' || $creatingManufacturer) {
                    return;
                }
                if (! Manufacturer::query()->whereKey($value)->exists()) {
                    $fail('The selected manufacturer is invalid.');
                }
            }],
            'new_manufacturer' => [$creatingManufacturer ? 'required' : 'nullable', 'string', 'max:255'],
            'manufacturer_model_id' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) use ($creatingModel): void {
                if ($value === null || $value === '' || $creatingModel) {
                    return;
                }
                if (! ManufacturerModel::query()->whereKey($value)->exists()) {
                    $fail('The selected model is invalid.');
                }
            }],
            'new_manufacturer_model' => [$creatingModel ? 'required' : 'nullable', 'string', 'max:255'],
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

    /**
     * Validated spare attributes with manufacturer/model catalogue IDs resolved.
     *
     * @return array<string, mixed>
     */
    public function spareAttributes(): array
    {
        $data = $this->validated();
        unset($data['new_manufacturer'], $data['new_manufacturer_model']);

        $creatingManufacturer = $this->input('manufacturer_id') === '__new__';
        $creatingModel = $this->input('manufacturer_model_id') === '__new__';
        $catalogue = app(ManufacturerCatalogueService::class);
        $manufacturer = null;

        if ($creatingManufacturer) {
            $manufacturer = $catalogue->resolveManufacturer($this->string('new_manufacturer')->toString());
            $data['manufacturer_id'] = $manufacturer?->id;
        } elseif (! empty($data['manufacturer_id'])) {
            $manufacturer = Manufacturer::query()->find((int) $data['manufacturer_id']);
            $data['manufacturer_id'] = $manufacturer?->id;
        } else {
            $data['manufacturer_id'] = null;
        }

        if ($creatingModel) {
            if ($manufacturer === null) {
                throw ValidationException::withMessages([
                    'manufacturer_id' => 'Choose or create a manufacturer before adding a model.',
                ]);
            }
            $model = $catalogue->resolveModel($manufacturer, $this->string('new_manufacturer_model')->toString());
            $data['manufacturer_model_id'] = $model?->id;
            $data['manufacturer_id'] = $manufacturer->id;
        } elseif (! empty($data['manufacturer_model_id'])) {
            $model = ManufacturerModel::query()->find((int) $data['manufacturer_model_id']);
            if ($model === null) {
                throw ValidationException::withMessages([
                    'manufacturer_model_id' => 'The selected model is invalid.',
                ]);
            }
            if ($manufacturer !== null && (int) $model->manufacturer_id !== (int) $manufacturer->id) {
                throw ValidationException::withMessages([
                    'manufacturer_model_id' => 'The selected model does not belong to the chosen manufacturer.',
                ]);
            }
            $data['manufacturer_model_id'] = $model->id;
            $data['manufacturer_id'] = $model->manufacturer_id;
        } else {
            $data['manufacturer_model_id'] = null;
        }

        return $data;
    }
}

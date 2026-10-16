<?php

namespace App\Http\Requests;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceLogType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetMaintenanceLogRequest extends FormRequest
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
        $maxKb = (int) config('attachments.max_kilobytes', 25600);
        $mimes = implode(',', config('attachments.allowed_mimes', []));

        return [
            'type' => ['required', Rule::enum(MaintenanceLogType::class)],
            'summary' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'occurred_at' => ['nullable', 'date'],
            'status' => [
                'nullable',
                'prohibited_unless:type,'.MaintenanceLogType::Fault->value,
                Rule::enum(AssetStatus::class),
            ],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'max:'.$maxKb, 'mimes:'.$mimes],
            'start_repair_now' => ['nullable', 'boolean'],
            'repair_procedure_id' => ['nullable', 'integer', Rule::exists('repair_procedures', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('start_repair_now')) {
            $this->merge([
                'start_repair_now' => filter_var($this->input('start_repair_now'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}

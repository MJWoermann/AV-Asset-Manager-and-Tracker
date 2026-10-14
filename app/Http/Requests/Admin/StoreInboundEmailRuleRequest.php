<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetStatus;
use App\Enums\InboundEmailRuleAction;
use App\Enums\MaintenanceLogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInboundEmailRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'require_review' => $this->boolean('require_review'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['required', 'integer', 'min:0', 'max:10000'],
            'action' => ['required', Rule::in(array_keys(InboundEmailRuleAction::options()))],
            'log_type' => ['required', Rule::in(array_keys(MaintenanceLogType::options()))],
            'fault_status' => ['nullable', Rule::in(array_keys(AssetStatus::options()))],
            'require_review' => ['sometimes', 'boolean'],
            'subject_contains' => ['nullable', 'string', 'max:255'],
            'from_contains' => ['nullable', 'string', 'max:255'],
            'body_contains' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedRule(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'is_active' => $this->boolean('is_active'),
            'priority' => (int) $data['priority'],
            'action' => $data['action'],
            'log_type' => $data['log_type'],
            'fault_status' => $data['fault_status'] ?? null,
            'require_review' => $this->boolean('require_review'),
            'match_conditions' => array_filter([
                'subject_contains' => $data['subject_contains'] ?? null,
                'from_contains' => $data['from_contains'] ?? null,
                'body_contains' => $data['body_contains'] ?? null,
            ], fn ($value) => filled($value)),
            'field_mapping' => [],
        ];
    }
}

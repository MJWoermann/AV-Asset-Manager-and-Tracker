<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairFromFaultRequest extends FormRequest
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
            'maintenance_log_id' => ['required', 'integer', Rule::exists('asset_maintenance_logs', 'id')],
            'summary' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'repair_procedure_id' => ['nullable', 'integer', Rule::exists('repair_procedures', 'id')],
            'assignee_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'set_asset_maintenance' => ['nullable', 'boolean'],
        ];
    }
}

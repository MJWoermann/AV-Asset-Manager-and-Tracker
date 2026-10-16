<?php

namespace App\Http\Requests;

use App\Enums\AssetStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairWithNewFaultRequest extends FormRequest
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
            'summary' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'status' => ['nullable', Rule::enum(AssetStatus::class)],
            'repair_procedure_id' => ['nullable', 'integer', Rule::exists('repair_procedures', 'id')],
            'assignee_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'set_asset_maintenance' => ['nullable', 'boolean'],
        ];
    }
}

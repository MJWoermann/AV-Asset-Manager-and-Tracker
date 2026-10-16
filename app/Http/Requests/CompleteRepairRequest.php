<?php

namespace App\Http\Requests;

use App\Enums\AssetStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteRepairRequest extends FormRequest
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
            'resolution_summary' => ['required', 'string', 'max:10000'],
            'asset_status' => ['nullable', Rule::enum(AssetStatus::class)],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

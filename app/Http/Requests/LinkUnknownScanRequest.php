<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkUnknownScanRequest extends FormRequest
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
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')],
            'add_to_event_list' => ['sometimes', 'boolean'],
            'set_identifier' => ['sometimes', 'boolean'],
        ];
    }
}

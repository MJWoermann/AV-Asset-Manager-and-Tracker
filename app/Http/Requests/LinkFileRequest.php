<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'linkable_type' => ['required', Rule::in(['asset', 'location', 'item_type', 'maintenance_log', 'rig_event'])],
            'linkable_id' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}

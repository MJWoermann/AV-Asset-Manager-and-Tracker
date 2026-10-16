<?php

namespace App\Http\Requests;

use App\Enums\FileCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFileUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(FileCategory::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'external_url' => ['required', 'url', 'max:2048'],
            'linkable_type' => ['nullable', Rule::in(['asset', 'location', 'item_type', 'maintenance_log', 'rig_event', 'spare_part'])],
            'linkable_id' => ['required_with:linkable_type', 'nullable', 'integer'],
        ];
    }
}

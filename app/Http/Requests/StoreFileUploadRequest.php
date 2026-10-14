<?php

namespace App\Http\Requests;

use App\Enums\FileCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maxKb = (int) config('attachments.max_kilobytes', 25600);
        $mimes = implode(',', config('attachments.allowed_mimes', []));

        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(FileCategory::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.$mimes],
            'linkable_type' => ['nullable', Rule::in(['asset', 'location', 'item_type'])],
            'linkable_id' => ['required_with:linkable_type', 'nullable', 'integer'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Choose a file to upload.',
            'file.max' => 'The file is too large. Maximum size is 25MB.',
            'file.mimes' => 'That file type is not supported.',
        ];
    }
}

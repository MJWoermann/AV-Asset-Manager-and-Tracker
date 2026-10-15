<?php

namespace App\Http\Requests;

use App\Enums\FileCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBulkFileUploadRequest extends FormRequest
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
        $maxFiles = (int) config('attachments.max_bulk_files', 20);

        return [
            'files' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'files.*' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.$mimes],
            'titles' => ['required', 'array'],
            'titles.*' => ['required', 'string', 'max:255'],
            'categories' => ['required', 'array'],
            'categories.*' => ['required', Rule::enum(FileCategory::class)],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:255'],
            'linkable_type' => ['nullable', Rule::in(['asset', 'location', 'item_type', 'maintenance_log'])],
            'linkable_id' => ['required_with:linkable_type', 'nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $fileCount = count($this->file('files', []));
            if ($fileCount === 0) {
                return;
            }

            if (count($this->input('titles', [])) !== $fileCount) {
                $validator->errors()->add('titles', 'Provide a title for each selected file.');
            }

            if (count($this->input('categories', [])) !== $fileCount) {
                $validator->errors()->add('categories', 'Provide a category for each selected file.');
            }

            $notes = $this->input('notes');
            if (is_array($notes) && count($notes) !== $fileCount) {
                $validator->errors()->add('notes', 'Notes must match the number of selected files.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $maxFiles = (int) config('attachments.max_bulk_files', 20);

        return [
            'files.required' => 'Choose at least one file to upload.',
            'files.max' => 'You can upload at most '.$maxFiles.' files at once.',
            'files.*.required' => 'Choose a file to upload.',
            'files.*.max' => 'Each file must be 25MB or smaller.',
            'files.*.mimes' => 'That file type is not supported.',
            'titles.*.required' => 'Each file needs a title.',
            'categories.*.required' => 'Each file needs a category.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:25600', 'mimes:csv,xlsx,xls,txt'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Choose a CSV or Excel file to import.',
            'file.file' => 'Choose a CSV or Excel file to import.',
            'file.max' => 'The file is too large. Maximum size is 25MB.',
            'file.mimes' => 'That file type is not supported. Upload a CSV or Excel file (.csv, .xlsx, or .xls).',
        ];
    }
}

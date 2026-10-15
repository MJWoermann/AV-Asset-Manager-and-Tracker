<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesShelfTagFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ImportShelfTagLbxRequest extends FormRequest
{
    use AuthorizesShelfTagFeature;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lbx' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('lbx')) {
                return;
            }

            $file = $this->file('lbx');
            if ($file === null) {
                return;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, ['lbx', 'zip'], true)) {
                $validator->errors()->add('lbx', 'The file must be a P-touch .lbx (or .zip) archive.');

                return;
            }

            $mime = (string) $file->getMimeType();
            $allowedMimes = [
                'application/zip',
                'application/x-zip-compressed',
                'application/octet-stream',
                'multipart/x-zip',
            ];
            if ($mime !== '' && ! in_array($mime, $allowedMimes, true)) {
                // Some environments report .lbx as octet-stream; extension already enforced.
                if ($extension !== 'lbx') {
                    $validator->errors()->add('lbx', 'The uploaded file must be a ZIP-based P-touch label archive.');
                }
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'lbx.required' => 'Choose a P-touch .lbx file to import.',
            'lbx.max' => 'The LBX file may not be larger than 10 MB.',
        ];
    }
}

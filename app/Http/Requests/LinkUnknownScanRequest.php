<?php

namespace App\Http\Requests;

use App\Support\ScannableIdentifierFields;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkUnknownScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports:write') ?? false;
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
            'identifier_field' => ['nullable', 'string', Rule::in(ScannableIdentifierFields::linkTargets())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('identifier_field') === '') {
            $this->merge(['identifier_field' => null]);
        }
    }

    /**
     * Resolved identifier column to write, or null when the caller did not ask to set one.
     *
     * `set_identifier` remains accepted for API clients and maps to `tp_barcode`.
     */
    public function resolvedIdentifierField(): ?string
    {
        $field = $this->validated('identifier_field');

        if (is_string($field) && $field !== '') {
            return $field;
        }

        if ($this->boolean('set_identifier')) {
            return 'tp_barcode';
        }

        return null;
    }
}

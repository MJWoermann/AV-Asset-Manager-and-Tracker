<?php

namespace App\Http\Requests;

use App\Support\ConnectionTypeColors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'is_active' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'string', Rule::in(ConnectionTypeColors::keys())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $color = $this->input('color');

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'color' => ($color === null || $color === '')
                ? null
                : strtolower((string) $color),
        ]);
    }
}

<?php

namespace App\Http\Requests;

use App\Support\ConnectionTypeColors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
        $department = $this->route('department');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'color' => ['sometimes', 'nullable', 'string', Rule::in(ConnectionTypeColors::keys())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'is_active' => $this->boolean('is_active'),
        ];

        if ($this->exists('color')) {
            $color = $this->input('color');
            $merge['color'] = ($color === null || $color === '')
                ? null
                : strtolower((string) $color);
        }

        $this->merge($merge);
    }
}

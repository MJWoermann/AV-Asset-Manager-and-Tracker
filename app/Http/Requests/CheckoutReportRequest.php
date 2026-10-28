<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports:export') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'overdue' => $this->boolean('overdue'),
        ];

        if ($this->exists('checked_out_only')) {
            $merge['checked_out_only'] = $this->boolean('checked_out_only');
        }

        foreach ([
            'manufacturer_id',
            'manufacturer_model_id',
            'item_type_id',
            'location_id',
            'holder_user_id',
            'purpose',
            'guest',
            'q',
            'checked_out_from',
            'checked_out_to',
        ] as $key) {
            if ($this->input($key) === '') {
                $merge[$key] = null;
            }
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'view' => ['nullable', 'in:models,holders'],
            'purpose' => ['nullable', 'string', 'max:32'],
            'manufacturer_id' => ['nullable', 'integer', Rule::exists('manufacturers', 'id')],
            'manufacturer_model_id' => ['nullable', 'integer', Rule::exists('manufacturer_models', 'id')],
            'item_type_id' => ['nullable', 'integer', Rule::exists('item_types', 'id')],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'holder_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'guest' => ['nullable', 'string', 'max:255'],
            'checked_out_from' => ['nullable', 'date'],
            'checked_out_to' => ['nullable', 'date'],
            'overdue' => ['nullable', 'boolean'],
            'checked_out_only' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:open,returned,all'],
            'format' => ['nullable', 'in:xlsx,csv,pdf'],
        ];
    }
}

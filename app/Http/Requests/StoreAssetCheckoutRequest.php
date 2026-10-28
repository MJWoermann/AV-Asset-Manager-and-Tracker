<?php

namespace App\Http\Requests;

use App\Enums\CheckoutPurpose;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssetCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($user->hasRole('external_repairer')) {
            return false;
        }

        return $user->can('assets:write');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purpose' => [
                'required',
                'string',
                'max:32',
                Rule::exists('checkout_purposes', 'slug')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'checked_out_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'checked_out_to_name' => ['nullable', 'string', 'max:255'],
            'checked_out_to_email' => ['nullable', 'email', 'max:255'],
            'checked_out_to_company' => ['nullable', 'string', 'max:255'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'expected_return_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'repair_id' => ['nullable', 'integer', Rule::exists('repairs', 'id')],
            'create_repair' => ['nullable', 'boolean'],
            'repair_summary' => ['nullable', 'string', 'max:255'],
            'repair_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'purpose.exists' => 'Choose an active checkout option.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('create_repair')) {
            $this->merge([
                'create_repair' => filter_var($this->input('create_repair'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $toUser = $this->input('checked_out_to_user_id');
            $toName = trim((string) $this->input('checked_out_to_name', ''));

            if (blank($toUser) && $toName === '') {
                $validator->errors()->add(
                    'checked_out_to_name',
                    'Provide a user or a guest name for checkout.',
                );
            }

            $purpose = (string) $this->input('purpose');
            if ($purpose === CheckoutPurpose::Repair->value) {
                $hasRepair = filled($this->input('repair_id')) || $this->boolean('create_repair');
                if (! $hasRepair) {
                    $validator->errors()->add(
                        'repair_id',
                        'Link an existing repair or create a new repair for a repair checkout.',
                    );
                }
            }

            if ($purpose === CheckoutPurpose::Hire->value && $this->user()?->hasRole('external_repairer')) {
                $validator->errors()->add('purpose', 'External repairers cannot create hire checkouts.');
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use App\Models\SparePart;
use App\Support\SparePartLuhn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateSparePartRequest extends StoreSparePartRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        /** @var SparePart|null $spare */
        $spare = $this->route('spare');

        $rules['code'] = [
            'nullable',
            'string',
            'max:32',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }
                $normalized = preg_replace('/\s+/', '', trim((string) $value)) ?? '';
                if (! SparePartLuhn::isValid($normalized)) {
                    $fail('Spare code must pass the Luhn check.');
                }
            },
            Rule::unique('spare_parts', 'code')->ignore($spare?->id),
        ];

        unset($rules['quantity_on_hand']);

        return $rules;
    }
}

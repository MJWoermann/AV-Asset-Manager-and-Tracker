<?php

namespace App\Http\Requests;

use App\Models\SparePart;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupersedeSparePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var SparePart $spare */
        $spare = $this->route('spare');

        return [
            'superseded_by_id' => [
                'required',
                'integer',
                Rule::exists('spare_parts', 'id')->whereNot('id', $spare->id),
            ],
        ];
    }
}

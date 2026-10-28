<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteItemTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('catalog:update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $itemTypeId = (int) $this->route('item_type')->id;

        return [
            'replacement_item_type_id' => [
                'required',
                'integer',
                Rule::exists('item_types', 'id')->where(
                    fn ($query) => $query->where('id', '!=', $itemTypeId)
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'replacement_item_type_id.required' => 'Choose a replacement item type for assets that use this type.',
            'replacement_item_type_id.exists' => 'Choose a valid replacement item type.',
        ];
    }
}

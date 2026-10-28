<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DeleteLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('locations:update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $location = $this->route('location');
        $locationId = $location instanceof Location ? (int) $location->id : (int) $location;

        return [
            'replacement_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(
                    fn ($query) => $query->where('id', '!=', $locationId)
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'replacement_location_id.required' => 'Choose a replacement location for assets at this location.',
            'replacement_location_id.exists' => 'Choose a valid replacement location.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $location = $this->route('location');

            if (! $location instanceof Location || $location->assets()->exists()) {
                return;
            }

            $validator->errors()->add('location', 'Locations with no assets stay on the list.');
        });
    }
}

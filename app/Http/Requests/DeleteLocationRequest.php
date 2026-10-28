<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                Rule::excludeIf(fn (): bool => ! $this->requiresReplacement()),
                Rule::requiredIf(fn (): bool => $this->requiresReplacement()),
                'integer',
                Rule::exists('locations', 'id')->where(
                    fn ($query) => $query->where('id', '!=', $locationId)
                ),
            ],
        ];
    }

    public function requiresReplacement(): bool
    {
        $location = $this->route('location');

        if (! $location instanceof Location) {
            return true;
        }

        return Asset::withoutDepartmentScope()
            ->where('location_id', $location->id)
            ->exists();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'replacement_location_id.required' => 'Choose a replacement location for assets at this location.',
            'replacement_location_id.exists' => 'Choose a valid replacement location.',
        ];
    }
}

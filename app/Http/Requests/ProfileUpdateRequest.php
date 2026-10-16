<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\DeveloperFeatures;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'theme' => ['required', 'in:light,dark'],
            'scan_noise' => ['sometimes', 'boolean'],
        ];

        if ($this->user()?->hasRole('admin')) {
            $featureRules = [];
            foreach (DeveloperFeatures::keys() as $key) {
                $featureRules['features.'.$key] = ['sometimes', 'boolean'];
            }

            $rules['features'] = ['sometimes', 'array'];
            $rules = [...$rules, ...$featureRules];
        }

        return $rules;
    }
}

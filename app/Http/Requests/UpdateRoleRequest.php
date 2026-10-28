<?php

namespace App\Http\Requests;

use App\Support\AccessPermissions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin:write') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        $nameRules = [
            'required',
            'string',
            'max:255',
            Rule::unique('roles', 'name')->ignore($role->id),
        ];

        if (AccessPermissions::isSystemRole($role->name)) {
            $nameRules[] = Rule::in([$role->name]);
        }

        return [
            'name' => $nameRules,
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(AccessPermissions::all())],
        ];
    }
}

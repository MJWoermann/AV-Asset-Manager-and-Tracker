<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Support\AccessPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->get()
            ->sortBy(fn (Role $role): string => sprintf(
                '%d-%s',
                AccessPermissions::isSystemRole($role->name) ? 0 : 1,
                strtolower(AccessPermissions::roleLabel($role->name)),
            ))
            ->values();

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', $this->formData(new Role));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('admin.roles.form', $this->formData($role));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if (! AccessPermissions::isSystemRole($role->name)) {
            $role->name = $request->validated('name');
            $role->save();
        }

        if ($role->name !== 'admin') {
            $role->syncPermissions($request->validated('permissions', []));
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (AccessPermissions::isSystemRole($role->name)) {
            return back()->withErrors([
                'role' => 'System roles cannot be deleted.',
            ]);
        }

        if ($role->users()->exists()) {
            return back()->withErrors([
                'role' => 'Remove this role from every user before deleting it.',
            ]);
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Role $role): array
    {
        $locked = $role->exists && $role->name === 'admin';
        $selected = old('permissions', $role->exists ? $role->permissions->pluck('name')->all() : []);

        return [
            'role' => $role,
            'locked' => $locked,
            'systemRole' => $role->exists && AccessPermissions::isSystemRole($role->name),
            'showFeatures' => ! $locked,
            'groups' => AccessPermissions::groups(),
            'features' => AccessPermissions::featureDefinitions(),
            'selected' => is_array($selected) ? $selected : [],
        ];
    }
}

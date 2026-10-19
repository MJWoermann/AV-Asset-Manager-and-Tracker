<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['roles', 'departments'])->orderBy('name')->paginate(50);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['theme' => 'light']),
            'roles' => Role::orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
            'theme' => ['required', 'in:light,dark'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'theme' => $data['theme'],
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([$data['role']]);
        $user->syncDepartmentsWithVisibility($data['department_ids'] ?? []);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $user->load('departments');

        return view('admin.users.form', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
            'theme' => ['required', 'in:light,dark'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'theme' => $data['theme'],
            ...(filled($data['password'] ?? null) ? ['password' => Hash::make($data['password'])] : []),
        ]);
        $user->syncRoles([$data['role']]);
        $user->syncDepartmentsWithVisibility($data['department_ids'] ?? []);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->hasRole('admin'), 403);

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}

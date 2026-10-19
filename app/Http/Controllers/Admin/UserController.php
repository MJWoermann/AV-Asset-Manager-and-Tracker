<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Support\AgentDebugLog;
use App\Support\DepartmentVisibility;
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
        $user->departments()->sync($data['department_ids'] ?? []);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $user->load('departments');

        // #region agent log
        $pivotIds = $user->departments->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $prefIds = DepartmentVisibility::normalizeIds(($user->preferences ?? [])[DepartmentVisibility::PREFERENCE_KEY] ?? null);
        AgentDebugLog::write('C', 'UserController.php:edit', 'Manage users form load sources', [
            'userId' => $user->id,
            'isAdmin' => $user->hasRole('admin'),
            'formShowsPivotIds' => $pivotIds,
            'preferenceIds' => $prefIds,
            'formWouldMismatchPref' => $user->hasRole('admin') && $pivotIds !== $prefIds,
        ]);
        // #endregion

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

        // #region agent log
        $prefBefore = DepartmentVisibility::normalizeIds(($user->preferences ?? [])[DepartmentVisibility::PREFERENCE_KEY] ?? null);
        $wasAdmin = $user->hasRole('admin');
        // #endregion
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'theme' => $data['theme'],
            ...(filled($data['password'] ?? null) ? ['password' => Hash::make($data['password'])] : []),
        ]);
        $user->syncRoles([$data['role']]);
        $user->departments()->sync($data['department_ids'] ?? []);

        // #region agent log
        $user->refresh();
        $pivotAfter = $user->departments()->pluck('departments.id')->map(fn ($id) => (int) $id)->values()->all();
        $prefAfter = DepartmentVisibility::normalizeIds(($user->preferences ?? [])[DepartmentVisibility::PREFERENCE_KEY] ?? null);
        $submitted = DepartmentVisibility::normalizeIds($data['department_ids'] ?? []);
        AgentDebugLog::write('B', 'UserController.php:update', 'Manage users departments saved', [
            'userId' => $user->id,
            'wasAdmin' => $wasAdmin,
            'isAdminAfter' => $user->hasRole('admin'),
            'submittedDepartmentIds' => $submitted,
            'pivotAfter' => $pivotAfter,
            'prefBefore' => $prefBefore,
            'prefAfter' => $prefAfter,
            'prefUnchangedWhenAdmin' => $wasAdmin && $prefBefore === $prefAfter && $submitted !== $prefAfter,
        ]);
        // #endregion

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->hasRole('admin'), 403);

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserApiController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with(['roles', 'departments'])->orderBy('name')->paginate(50);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['roles', 'departments']);

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
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

        return response()->json($user->load(['roles', 'departments']), 201);
    }

    public function update(Request $request, User $user): JsonResponse
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
        $user->departments()->sync($data['department_ids'] ?? []);

        return response()->json($user->fresh(['roles', 'departments']));
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->hasRole('admin'), 403);
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}

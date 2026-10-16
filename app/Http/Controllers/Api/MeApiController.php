<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Support\DepartmentVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeApiController extends Controller
{
    public function updateActiveDepartment(Request $request): JsonResponse
    {
        $user = $request->user();

        $allowedIds = $user->hasRole('admin')
            ? Department::query()->pluck('id')->all()
            : $user->departments()->pluck('departments.id')->all();

        $data = $request->validate([
            'department_id' => ['required', 'integer', Rule::in($allowedIds)],
        ]);

        $departmentId = (int) $data['department_id'];

        session([DepartmentVisibility::SESSION_KEY => $departmentId]);

        $preferences = $user->preferences ?? [];
        $preferences[DepartmentVisibility::SESSION_KEY] = $departmentId;
        $user->forceFill(['preferences' => $preferences])->save();

        return response()->json([
            'message' => 'Active department updated.',
            'department_id' => $departmentId,
        ]);
    }
}

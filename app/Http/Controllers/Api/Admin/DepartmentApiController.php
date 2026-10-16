<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DepartmentApiController extends Controller
{
    public function index(): JsonResponse
    {
        $departments = Department::query()->withCount('users')->orderBy('name')->paginate(50);

        return response()->json($departments);
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json($department);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $department = Department::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json($department, 201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $data = $request->validated();

        $department->update([
            'name' => $data['name'],
            'slug' => $department->isGeneral() ? $department->slug : Str::slug($data['name']),
            'is_active' => $data['is_active'] ?? $department->is_active,
        ]);

        return response()->json($department->fresh());
    }

    public function destroy(Department $department): JsonResponse
    {
        if ($department->isGeneral()) {
            return response()->json(['message' => 'The General department cannot be deleted.'], 422);
        }

        if ($department->hasOperationalData()) {
            return response()->json([
                'message' => 'Reassign or remove assets, lists, and other department records before deleting.',
            ], 422);
        }

        $department->delete();

        return response()->json(['message' => 'Department deleted.']);
    }
}

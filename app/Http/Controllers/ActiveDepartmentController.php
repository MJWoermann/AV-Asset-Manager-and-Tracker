<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Support\DepartmentVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActiveDepartmentController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $allowedIds = $user->hasRole('admin')
            ? Department::query()->pluck('id')->all()
            : $user->departments()->pluck('departments.id')->all();

        $data = $request->validate([
            'department_id' => ['required', 'integer', Rule::in($allowedIds)],
        ]);

        session([DepartmentVisibility::SESSION_KEY => (int) $data['department_id']]);

        return back()->with('status', 'Active department updated.');
    }
}

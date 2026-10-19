<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Support\ConnectionTypeColors;
use App\Support\DepartmentRowColourSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::query()
            ->withCount('users')
            ->orderBy('name')
            ->paginate(50);

        return view('admin.departments.index', [
            'departments' => $departments,
            'rowColoursEnabled' => DepartmentRowColourSettings::enabled(),
        ]);
    }

    public function updateRowColours(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled' => ['sometimes', 'boolean'],
        ]);

        DepartmentRowColourSettings::put($request->boolean('enabled'));

        return redirect()
            ->route('admin.departments.index')
            ->with('status', 'Department row colour settings saved.');
    }

    public function create(): View
    {
        $department = new Department(['is_active' => true]);

        return view('admin.departments.form', [
            'department' => $department,
            'colors' => ConnectionTypeColors::optionsFor($department->color),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Department::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'is_active' => $data['is_active'] ?? true,
            'color' => $data['color'] ?? null,
        ]);

        return redirect()->route('admin.departments.index')->with('status', 'Department created.');
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.form', [
            'department' => $department,
            'colors' => ConnectionTypeColors::optionsFor($department->color),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'slug' => $department->isGeneral() ? $department->slug : Str::slug($data['name']),
            'is_active' => $data['is_active'] ?? $department->is_active,
        ];

        if (array_key_exists('color', $data)) {
            $payload['color'] = $data['color'];
        }

        $department->update($payload);

        return redirect()->route('admin.departments.index')->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->isGeneral()) {
            return back()->withErrors(['department' => 'The General department cannot be deleted.']);
        }

        if ($department->hasOperationalData()) {
            return back()->withErrors([
                'department' => 'Reassign or remove assets, lists, and other department records before deleting.',
            ]);
        }

        $department->delete();

        return redirect()->route('admin.departments.index')->with('status', 'Department deleted.');
    }
}

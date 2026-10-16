@props(['variant' => 'dropdown'])

@php
    $user = Auth::user();
    $activeDepartmentId = $user?->resolveActiveDepartmentId();
    $isAdmin = $user?->hasRole('admin');
    $memberDepartments = $isAdmin
        ? \App\Models\Department::query()->where('is_active', true)->orderBy('name')->get()
        : ($user?->departments()->where('departments.is_active', true)->orderBy('departments.name')->get() ?? collect());
    $allDepartments = $isAdmin
        ? \App\Models\Department::query()->orderBy('name')->get()
        : collect();
    $visibleIds = $user?->visibleDepartmentIdsPreference() ?? [];
    $compact = $variant === 'mobile';
@endphp

@if($user && ($memberDepartments->isNotEmpty() || $isAdmin))
    <div @class(['px-4 py-2 space-y-3' => $compact, 'px-4 py-3 space-y-3 border-b border-gray-100 dark:border-brand-charcoal' => ! $compact])>
        @if($memberDepartments->isNotEmpty())
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-brand-silver/70 mb-1">Active department</p>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">New assets, lists, and scans use this department.</p>
                <form method="POST" action="{{ route('preferences.active-department') }}" class="space-y-2">
                    @csrf
                    @method('PATCH')
                    <select
                        name="department_id"
                        class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm"
                        onchange="this.form.requestSubmit()"
                    >
                        @foreach($memberDepartments as $department)
                            <option value="{{ $department->id }}" @selected((int) $activeDepartmentId === (int) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif

        @if($isAdmin)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-brand-silver/70 mb-1">Departments to show</p>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">
                    When any departments are selected, only those are shown. Clear all to show every department.
                </p>
                <form method="POST" action="{{ route('preferences.department-visibility') }}" class="space-y-2">
                    @csrf
                    @method('PATCH')
                    <div class="max-h-36 overflow-y-auto space-y-1 border border-brand-silver dark:border-brand-charcoal p-2">
                        @foreach($allDepartments as $department)
                            <label class="flex items-center gap-2 text-sm text-brand-charcoal dark:text-brand-silver">
                                <input
                                    type="checkbox"
                                    name="visible_department_ids[]"
                                    value="{{ $department->id }}"
                                    class="rounded text-brand"
                                    @checked($visibleIds !== [] && in_array($department->id, $visibleIds, true))
                                >
                                {{ $department->name }}
                            </label>
                        @endforeach
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="text-sm text-brand hover:underline">Apply visibility</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endif

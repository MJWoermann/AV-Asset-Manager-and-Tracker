@php
    $user = Auth::user();
    $activeDepartmentId = $user?->resolveActiveDepartmentId();
    $isAdmin = $user?->hasRole('admin');
    $needsAssignment = $user?->needsDepartmentAssignment() ?? false;
    $memberDepartments = $isAdmin
        ? \App\Models\Department::query()->where('is_active', true)->orderBy('name')->get()
        : ($user?->departments()->where('departments.is_active', true)->orderBy('departments.name')->get() ?? collect());
    $allDepartments = $isAdmin
        ? \App\Models\Department::query()->orderBy('name')->get()
        : collect();
    $visibleIds = $user?->visibleDepartmentIdsPreference() ?? [];
@endphp

@if($user && ($memberDepartments->isNotEmpty() || $isAdmin || $needsAssignment))
    <div class="p-4 sm:p-8 bg-white dark:bg-brand-slate border border-transparent dark:border-brand-charcoal shadow sm:rounded-lg">
        <div class="max-w-xl">
            <section>
                <header>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('Departments') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-brand-silver">
                        @if($needsAssignment)
                            {{ __('Your account needs a department membership before you can see inventory data.') }}
                        @else
                            {{ __('Choose which department new records use, and (for admins) which departments appear in lists.') }}
                        @endif
                    </p>
                </header>

                <div class="mt-6 space-y-8">
                    @if($needsAssignment)
                        <x-no-department-notice variant="inline" />
                    @elseif($memberDepartments->isNotEmpty())
                        <form method="POST" action="{{ route('preferences.active-department') }}" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <x-input-label for="active_department_id" :value="__('Active department')" />
                                <p class="mt-1 text-sm text-gray-600 dark:text-brand-silver">
                                    {{ __('New assets, lists, and scans use this department.') }}
                                </p>
                                <select
                                    id="active_department_id"
                                    name="department_id"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                >
                                    @foreach($memberDepartments as $department)
                                        <option value="{{ $department->id }}" @selected((int) $activeDepartmentId === (int) $department->id)>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('department_id')" />
                            </div>

                            <div class="flex items-center justify-end gap-4">
                                @if (session('status') === 'Active department updated.')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600 dark:text-brand-silver"
                                    >{{ __('Saved.') }}</p>
                                @endif

                                <x-primary-button>{{ __('Save active department') }}</x-primary-button>
                            </div>
                        </form>
                    @endif

                    @if($isAdmin)
                        <form method="POST" action="{{ route('preferences.department-visibility') }}" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('Departments to show') }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-brand-silver">
                                    {{ __('When any departments are selected, only those are shown. Clear all to show every department.') }}
                                </p>
                                <div class="mt-2 max-h-48 overflow-y-auto space-y-2 border border-gray-200 dark:border-brand-charcoal rounded-md p-3">
                                    @forelse($allDepartments as $department)
                                        <label for="visible_department_{{ $department->id }}" class="flex items-start gap-2 text-sm text-gray-800 dark:text-brand-silver">
                                            <input
                                                id="visible_department_{{ $department->id }}"
                                                type="checkbox"
                                                name="visible_department_ids[]"
                                                value="{{ $department->id }}"
                                                class="rounded border-gray-300 text-brand mt-0.5"
                                                @checked($visibleIds !== [] && in_array($department->id, $visibleIds, true))
                                            >
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $department->name }}</span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-gray-600 dark:text-brand-silver">
                                            {{ __('No departments exist yet.') }}
                                        </p>
                                    @endforelse
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('visible_department_ids')" />
                                <x-input-error class="mt-2" :messages="$errors->get('visible_department_ids.*')" />
                            </div>

                            <div class="flex items-center justify-end gap-4">
                                @if (session('status') === 'Department visibility updated.')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600 dark:text-brand-silver"
                                    >{{ __('Saved.') }}</p>
                                @endif

                                <x-primary-button>{{ __('Apply visibility') }}</x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endif

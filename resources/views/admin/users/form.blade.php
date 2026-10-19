<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $user->exists ? 'Edit user' : 'New user' }}</h2></x-slot>
    <div class="max-w-xl mx-auto px-4">
        <form
            method="POST"
            action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
            x-data="{
                password: '',
                passwordTouched: {{ $errors->has('password') ? 'true' : 'false' }},
                get passwordFeedback() {
                    if (! this.password) {
                        return '';
                    }
                    if (this.password.length < 8) {
                        return 'Password must be at least 8 characters.';
                    }
                    if (! /\p{Lu}/u.test(this.password)) {
                        return 'Password must contain at least one uppercase letter.';
                    }
                    if (! /[\d\p{Z}\p{S}\p{P}]/u.test(this.password)) {
                        return 'Password must contain at least one number or symbol.';
                    }
                    return '';
                },
                validatePasswordBeforeSubmit(event) {
                    this.passwordTouched = true;
                    if (this.password && this.passwordFeedback) {
                        event.preventDefault();
                    }
                },
            }"
            @submit="validatePasswordBeforeSubmit($event)"
        >
            @csrf
            @if($user->exists) @method('PUT') @endif

            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span> Required field
            </p>

            @if($user->exists)
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Leave the password blank unless you want to change it. Only enter a new password if you intend to update it.
                </p>
            @endif

            <div>
                <x-input-label for="name" value="Name" required />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="email" value="Email" required />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="password" value="Password" :required="! $user->exists" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    :required="! $user->exists"
                    x-model="password"
                    @input="passwordTouched = true"
                    @blur="passwordTouched = true"
                />
                <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                    @if($user->exists)
                        Optional. Only fill this in to change the password. Must be at least 8 characters, with one uppercase letter and one number or symbol.
                    @else
                        At least 8 characters, one uppercase letter, and one number or symbol.
                    @endif
                </p>
                <p
                    class="mt-1 text-sm text-red-600 dark:text-red-400"
                    x-show="passwordTouched && passwordFeedback"
                    x-text="passwordFeedback"
                    x-cloak
                ></p>
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="role" value="Role" required />
                <select id="role" name="role" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role', $user->roles->first()?->name) === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="theme" value="Theme" required />
                <select id="theme" name="theme" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    <option value="light" @selected(old('theme', $user->theme ?? 'light') === 'light')>Light</option>
                    <option value="dark" @selected(old('theme', $user->theme ?? 'light') === 'dark')>Dark</option>
                </select>
                <x-input-error :messages="$errors->get('theme')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Departments" />
                <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                    Non-admins only see operational data in assigned departments. For admins, this matches Profile → Departments to show (clear all = every department).
                </p>
                <div class="mt-2 space-y-2 max-h-48 overflow-y-auto border border-brand-silver dark:border-brand-charcoal p-3">
                    @php
                        $defaultDepartmentIds = $user->exists && $user->hasRole('admin')
                            ? $user->visibleDepartmentIdsPreference()
                            : ($user->departments?->pluck('id')->all() ?? []);
                        $selectedDepartmentIds = collect(old('department_ids', $defaultDepartmentIds))->map(fn ($id) => (int) $id);
                    @endphp
                    @forelse($departments as $department)
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                name="department_ids[]"
                                value="{{ $department->id }}"
                                class="rounded text-brand"
                                @checked($selectedDepartmentIds->contains($department->id))
                            >
                            {{ $department->name }}
                        </label>
                    @empty
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">No departments yet.</p>
                    @endforelse
                </div>
                <x-input-error :messages="$errors->get('department_ids')" class="mt-1" />
            </div>
            <div class="flex justify-end">
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

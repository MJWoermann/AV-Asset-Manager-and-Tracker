<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $user->exists ? 'Edit user' : 'New user' }}</h2></x-slot>
    <div class="max-w-xl mx-auto px-4">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-4 bg-white dark:bg-black border p-4">
            @csrf
            @if($user->exists) @method('PUT') @endif
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
            </div>
            <div>
                <x-input-label for="password" :value="$user->exists ? 'Password (leave blank to keep)' : 'Password'" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="!$user->exists" />
            </div>
            <div>
                <x-input-label for="role" value="Role" />
                <select id="role" name="role" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role', $user->roles->first()?->name) === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="theme" value="Theme" />
                <select id="theme" name="theme" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="light" @selected(old('theme', $user->theme ?? 'light') === 'light')>Light</option>
                    <option value="dark" @selected(old('theme', $user->theme ?? 'light') === 'dark')>Dark</option>
                </select>
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div>
</x-app-layout>

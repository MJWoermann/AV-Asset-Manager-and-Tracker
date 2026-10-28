<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Roles</h2>
            <a href="{{ route('admin.roles.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add role</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4">
        @if (session('status'))
            <p class="mb-4 text-sm text-brand">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-300 bg-red-50 dark:bg-red-950/40 dark:border-red-800 p-3 text-sm text-red-700 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
            Each user has one role. System roles keep their names. Administrator always has full operational access.
        </p>

        <table class="min-w-full text-sm bg-white dark:bg-brand-slate border">
            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                <tr>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Users</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                        <td class="px-3 py-2">{{ \App\Support\AccessPermissions::roleLabel($role->name) }}</td>
                        <td class="px-3 py-2">{{ \App\Support\AccessPermissions::isSystemRole($role->name) ? 'System' : 'Custom' }}</td>
                        <td class="px-3 py-2">{{ $role->users_count }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-between gap-3">
                                <a class="text-brand" href="{{ route('admin.roles.edit', $role) }}">Edit</a>
                                @if (! \App\Support\AccessPermissions::isSystemRole($role->name) && $role->users_count === 0)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.roles.destroy', $role) }}"
                                        onsubmit='return confirm(@js("Delete {$role->name}? This cannot be undone."))'
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>

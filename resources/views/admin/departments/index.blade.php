<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Departments</h2>
            <a href="{{ route('admin.departments.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add department</a>
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto px-4">
        @if ($errors->any())
            <div class="mb-4 rounded border border-red-300 bg-red-50 dark:bg-red-950/40 dark:border-red-800 p-3 text-sm text-red-700 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
            Departments isolate operational records (assets, lists, scans, files, spares). Locations and catalogue data stay shared.
        </p>

        <table class="min-w-full text-sm bg-white dark:bg-brand-slate border">
            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                <tr>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Members</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($departments as $department)
                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                        <td class="px-3 py-2">{{ $department->name }}</td>
                        <td class="px-3 py-2">{{ $department->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-3 py-2">{{ $department->users_count }}</td>
                        <td class="px-3 py-2 space-x-3 whitespace-nowrap">
                            <a class="text-brand" href="{{ route('admin.departments.edit', $department) }}">Edit</a>
                            @unless($department->isGeneral())
                                <form
                                    method="POST"
                                    action="{{ route('admin.departments.destroy', $department) }}"
                                    class="inline"
                                    onsubmit='return confirm(@js("Delete {$department->name}? This cannot be undone."))'
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Delete</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $departments->links() }}</div>
    </div>
</x-app-layout>

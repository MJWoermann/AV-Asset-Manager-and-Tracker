<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Departments</h2>
            <a href="{{ route('admin.departments.create') }}" data-history-roundtrip class="px-3 py-2 bg-brand text-white text-sm">Add department</a>
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

        <form
            method="POST"
            action="{{ route('admin.departments.row-colours') }}"
            class="mb-6 flex flex-wrap items-center justify-between gap-3 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4"
        >
            @csrf
            @method('PATCH')
            <label class="inline-flex items-start gap-2 text-sm max-w-2xl">
                <input type="hidden" name="enabled" value="0">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    class="mt-0.5 rounded text-brand"
                    @checked(old('enabled', $rowColoursEnabled))
                >
                <span>
                    Colour asset rows by department when a user can see 2+ departments
                    <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-1">
                        Applies soft background tint on Assets and list browse tables. Dashboard department colours always use the colour field below.
                    </span>
                </span>
            </label>
            <x-primary-button>Save</x-primary-button>
        </form>

        <table class="min-w-full text-sm bg-white dark:bg-brand-slate border">
            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                <tr>
                    <th class="px-3 py-2">Colour</th>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Members</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($departments as $department)
                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                        <td class="px-3 py-2">
                            @if($department->color)
                                <span
                                    class="inline-block h-5 w-5 rounded border border-brand-charcoal/30 dark:border-brand-silver/40"
                                    style="background-color: {{ $department->color }}"
                                    title="{{ $department->color }}"
                                    aria-label="{{ $department->color }}"
                                ></span>
                            @else
                                <span class="text-brand-charcoal dark:text-brand-silver">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $department->name }}</td>
                        <td class="px-3 py-2">{{ $department->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-3 py-2">{{ $department->users_count }}</td>
                        <td class="px-3 py-2 space-x-3 whitespace-nowrap">
                            <a class="text-brand" href="{{ route('admin.departments.edit', $department) }}" data-history-roundtrip>Edit</a>
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

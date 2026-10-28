<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">Lists</h2>
            @can('lists:write')
                <a href="{{ route('lists.create') }}" data-history-roundtrip class="px-3 py-2 bg-brand text-white text-sm">New list</a>
            @endcan
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
        @can('lists:bulk')
            x-data="bulkSelection(@js([
                'idAttribute' => 'data-bulk-list-id',
                'fields' => [
                    'update_department' => false,
                ],
            ]))"
        @endcan
    >
        @if (session('status'))
            <div class="mb-4 text-sm text-brand">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @can('lists:bulk')
            <x-bulk-list-actions :departments="$departments" />
        @endcan

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        @can('lists:bulk')
                            <th class="px-3 py-2 w-10">
                                <input
                                    type="checkbox"
                                    class="rounded border-brand-charcoal/40"
                                    :checked="allPageSelected"
                                    @change="toggleAll($event.target.checked)"
                                    aria-label="Select all lists"
                                >
                            </th>
                        @endcan
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Items</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lists as $list)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            @can('lists:bulk')
                                <td class="px-3 py-2">
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        data-bulk-list-id="{{ $list->id }}"
                                        :checked="!!selected[{{ $list->id }}]"
                                        @change="setSelected({{ $list->id }}, $event.target.checked)"
                                        aria-label="Select {{ $list->name }}"
                                    >
                                </td>
                            @endcan
                            <td class="px-3 py-2"><a class="text-brand" href="{{ route('lists.show', $list) }}">{{ $list->name }}</a></td>
                            <td class="px-3 py-2 capitalize">{{ $list->type }}</td>
                            <td class="px-3 py-2">{{ $list->items_count }}</td>
                        </tr>
                    @empty
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-4 text-brand-charcoal dark:text-brand-silver" colspan="{{ auth()->user()?->can('lists:bulk') ? 4 : 3 }}">
                                No lists yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

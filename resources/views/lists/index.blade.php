<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">Lists</h2>
            @role('admin|inventory_manager')
                <a href="{{ route('lists.create') }}" class="px-3 py-2 bg-brand text-white text-sm">New list</a>
            @endrole
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="overflow-x-auto bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Items</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lists as $list)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2"><a class="text-brand" href="{{ route('lists.show', $list) }}">{{ $list->name }}</a></td>
                            <td class="px-3 py-2 capitalize">{{ $list->type }}</td>
                            <td class="px-3 py-2">{{ $list->items_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

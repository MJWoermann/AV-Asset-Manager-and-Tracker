<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">{{ $list->name }}</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver capitalize">{{ $list->type }} list</p>
            </div>
            <div class="flex gap-2">
                @role('admin|inventory_manager')
                    <a href="{{ route('export.list', [$list, 'format' => 'xlsx']) }}" class="px-3 py-2 border text-sm">Export</a>
                    <a href="{{ route('lists.edit', $list) }}" class="px-3 py-2 border text-sm">Edit</a>
                @endrole
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ul class="bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal divide-y divide-brand-silver dark:divide-brand-charcoal">
            @forelse($items as $item)
                <li class="px-4 py-2 text-sm {{ $item->is_child_expand || $item->asset?->parent_id ? 'pl-10' : '' }}">
                    <a class="text-brand" href="{{ route('assets.show', $item->asset) }}">{{ $item->asset?->name }}</a>
                    <span class="text-brand-charcoal dark:text-brand-silver"> × {{ $item->quantity }} — {{ $item->asset?->tp_barcode }}</span>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-brand-charcoal">No items yet.</li>
            @endforelse
        </ul>
        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>

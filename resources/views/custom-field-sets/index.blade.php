<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Custom field sets</h2>
            <a href="{{ route('custom-field-sets.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add</a>
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto px-4 space-y-3">
        @forelse($fieldSets as $set)
            <div class="bg-white dark:bg-black border p-4 flex justify-between gap-3">
                <div>
                    <div class="font-medium">{{ $set->name }}</div>
                    <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                        {{ $set->definitions_count }} {{ $set->definitions_count === 1 ? 'field' : 'fields' }}
                        @if($set->itemTypes->isNotEmpty())
                            — used by {{ $set->itemTypes->pluck('name')->join(', ') }}
                        @else
                            — not assigned to any item type
                        @endif
                    </div>
                    @if($set->description)
                        <div class="text-sm mt-1">{{ $set->description }}</div>
                    @endif
                </div>
                <a href="{{ route('custom-field-sets.edit', $set) }}" class="text-brand text-sm shrink-0">Edit</a>
            </div>
        @empty
            <div class="bg-white dark:bg-black border p-4 text-sm text-brand-charcoal dark:text-brand-silver">
                No custom field sets yet.
                <a href="{{ route('custom-field-sets.create') }}" class="text-brand">Create one</a>
                to assign on item types.
            </div>
        @endforelse
    </div>
</x-app-layout>

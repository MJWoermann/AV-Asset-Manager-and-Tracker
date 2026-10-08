<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between"><h2 class="font-semibold text-xl">Item types</h2>
            <a href="{{ route('item-types.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add</a></div>
    </x-slot>
    <div class="max-w-7xl mx-auto px-4 space-y-3">
        @foreach($itemTypes as $type)
            <div class="bg-white dark:bg-black border p-4 flex justify-between gap-3">
                <div>
                    <div class="font-medium">{{ $type->name }}</div>
                    <div class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $type->fieldSets->pluck('name')->join(', ') ?: 'No field sets' }}</div>
                </div>
                <a href="{{ route('item-types.edit', $type) }}" class="text-brand text-sm">Edit</a>
            </div>
        @endforeach
    </div>
</x-app-layout>

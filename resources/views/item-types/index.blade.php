<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between"><h2 class="font-semibold text-xl">Item types</h2>
            <a href="{{ route('item-types.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add</a></div>
    </x-slot>
    <div
        class="max-w-7xl mx-auto px-4 space-y-3"
        @role('admin')
            x-data="bulkSelection(@js([
                'idAttribute' => 'data-bulk-item-type-id',
                'fields' => [
                    'update_department' => false,
                ],
            ]))"
        @endrole
    >
        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @role('admin')
            <x-bulk-item-type-actions :departments="$departments" />
        @endrole

        @foreach($itemTypes as $type)
            <div class="bg-white dark:bg-brand-slate border p-4 flex justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    @role('admin')
                        <input
                            type="checkbox"
                            class="rounded border-brand-charcoal/40 mt-1"
                            data-bulk-item-type-id="{{ $type->id }}"
                            :checked="!!selected[{{ $type->id }}]"
                            @change="setSelected({{ $type->id }}, $event.target.checked)"
                            aria-label="Select {{ $type->name }}"
                        >
                    @endrole
                    <div class="min-w-0">
                        <div class="font-medium">{{ $type->name }}</div>
                        <div class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $type->fieldSets->pluck('name')->join(', ') ?: 'No field sets' }}</div>
                        <div class="text-xs text-brand-charcoal dark:text-brand-silver mt-1">{{ $type->assets_count }} {{ $type->assets_count === 1 ? 'asset' : 'assets' }}</div>
                    </div>
                </div>
                <a href="{{ route('item-types.edit', $type) }}" class="text-brand text-sm shrink-0">Edit</a>
            </div>
        @endforeach
    </div>
</x-app-layout>

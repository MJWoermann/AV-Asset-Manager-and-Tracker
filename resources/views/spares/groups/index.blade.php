<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Spare groups / kits</h2>
            <div class="flex gap-2 text-sm">
                <a href="{{ route('spares.index') }}" class="px-3 py-2 border">Spares</a>
                @can('spares:manage')
                    <a href="{{ route('spares.groups.create') }}" data-history-roundtrip class="px-3 py-2 bg-brand text-white">Add group</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4">
        @if(session('status'))<p class="text-sm text-brand mb-3">{{ session('status') }}</p>@endif
        <ul class="divide-y border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate text-sm">
            @forelse($groups as $group)
                <li class="p-3 flex justify-between gap-2">
                    <div>
                        <a href="{{ route('spares.groups.show', $group) }}" class="font-medium text-brand">{{ $group->name }}</a>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver">{{ $group->items_count }} line(s) @if($group->linkedSystem)— {{ $group->linkedSystem->name }}@endif</p>
                    </div>
                    @can('spares:manage')
                        <a href="{{ route('spares.groups.edit', $group) }}" data-history-roundtrip class="text-xs border px-2 py-1">Edit</a>
                    @endcan
                </li>
            @empty
                <li class="p-4 text-brand-charcoal">No groups yet.</li>
            @endforelse
        </ul>
        {{ $groups->links() }}
    </div>
</x-app-layout>

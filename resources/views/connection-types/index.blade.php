<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Connection types</h2>
            <a href="{{ route('connection-types.create') }}" data-history-roundtrip class="px-3 py-2 bg-brand text-white text-sm">Add</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 space-y-3">
        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        @forelse($connectionTypes as $type)
            <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-center">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-block h-4 w-4 rounded-sm shrink-0" style="background-color: {{ $type->color }}"></span>
                    <x-connection-type-icon :icon="$type->icon" class="h-5 w-5 text-brand-charcoal dark:text-brand-silver" />
                    <div class="min-w-0">
                        <div class="font-medium">{{ $type->name }}</div>
                        <div class="text-xs text-brand-charcoal dark:text-brand-silver">
                            {{ $type->connections_count }} {{ $type->connections_count === 1 ? 'connection' : 'connections' }}
                            @if(($type->buses_count ?? 0) > 0)
                                · {{ $type->buses_count }} {{ $type->buses_count === 1 ? 'bus' : 'buses' }}
                            @endif
                            @if($type->allows_protocol)
                                · protocol select
                            @endif
                            @if($type->allows_bus)
                                · device bus
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('connection-types.edit', $type) }}" data-history-roundtrip class="text-brand">Edit</a>
                    @if($type->connections_count === 0 && ($type->buses_count ?? 0) === 0)
                        <form method="POST" action="{{ route('connection-types.destroy', $type) }}" onsubmit="return confirm('Delete this connection type?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No connection types yet.</p>
        @endforelse
    </div>
</x-app-layout>

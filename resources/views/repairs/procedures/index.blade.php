<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Repair procedures</h2>
            <div class="flex gap-2 text-sm">
                <a href="{{ route('maintenance.index') }}" class="px-3 py-2 border">Maintenance</a>
                <a href="{{ route('repair-procedures.create') }}" data-history-roundtrip class="px-3 py-2 bg-brand text-white">Add procedure</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4">
        @if(session('status'))
            <p class="text-sm text-brand mb-3">{{ session('status') }}</p>
        @endif

        <ul class="divide-y border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate text-sm">
            @forelse($procedures as $procedure)
                <li class="p-3 flex justify-between gap-2">
                    <div>
                        <a href="{{ route('repair-procedures.show', $procedure) }}" class="font-medium text-brand">{{ $procedure->title }}</a>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                            {{ $procedure->steps_count }} step(s)
                            @if($procedure->manufacturerModel)
                                — {{ $procedure->manufacturerModel->manufacturer?->name }} {{ $procedure->manufacturerModel->name }}
                            @elseif($procedure->itemType)
                                — {{ $procedure->itemType->name }}
                            @elseif($procedure->sparePart)
                                — {{ $procedure->sparePart->code }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('repair-procedures.edit', $procedure) }}" data-history-roundtrip class="text-xs border px-2 py-1 self-start">Edit</a>
                </li>
            @empty
                <li class="p-4 text-brand-charcoal">No repair procedures yet.</li>
            @endforelse
        </ul>
        {{ $procedures->links() }}
    </div>
</x-app-layout>

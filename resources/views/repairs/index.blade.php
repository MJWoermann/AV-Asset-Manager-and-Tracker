<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Repairs</h2>
            <a href="{{ route('data.index') }}" class="px-3 py-2 border text-sm">Data</a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 space-y-4">
        @if(session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        <form method="get" class="flex flex-col sm:flex-row flex-wrap gap-3 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 text-sm">
            <div class="min-w-[10rem]">
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="q" value="Search" />
                <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$term" placeholder="Summary or asset…" />
            </div>
            <div class="flex items-end">
                <x-primary-button>Filter</x-primary-button>
            </div>
        </form>

        <ul class="divide-y border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate text-sm">
            @forelse($repairs as $repair)
                <li class="p-3 flex flex-wrap justify-between gap-2">
                    <div>
                        <a href="{{ route('repairs.show', $repair) }}" class="font-medium text-brand">{{ $repair->summary }}</a>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                            {{ $repair->asset?->name }}
                            — {{ $repair->status?->label() }}
                            @if($repair->started_at)
                                — {{ $repair->started_at->format('Y-m-d H:i') }}
                            @endif
                        </p>
                    </div>
                    <span class="text-xs border px-2 py-1 self-start">{{ $repair->status?->label() }}</span>
                </li>
            @empty
                <li class="p-4 text-brand-charcoal">No repairs match.</li>
            @endforelse
        </ul>
        {{ $repairs->links() }}
    </div>
</x-app-layout>

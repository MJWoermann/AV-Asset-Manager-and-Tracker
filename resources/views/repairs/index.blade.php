<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Repairs</h2>
            <a href="{{ route('maintenance.index') }}" class="px-3 py-2 border text-sm">Maintenance</a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 space-y-4">
        @if(session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        @php
            $repairsFiltersActive = filled($term) || (string) $status !== 'active';
        @endphp
        <form method="get" class="flex flex-col sm:flex-row flex-wrap gap-3 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 text-sm">
            <div class="min-w-[10rem]">
                <x-input-label for="status" value="Status" />
                <x-searchable-select
                    name="status"
                    id="status"
                    :options="collect($statusOptions)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()"
                    :selected="$status"
                    auto-submit
                    aria-label="Filter by repair status"
                />
            </div>
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="repairs-search-q" value="Search" />
                <x-scannable-search
                    id="repairs-search-q"
                    name="q"
                    :value="$term"
                    placeholder="Summary or asset…"
                    class="mt-1"
                    scan-aria-label="Scan barcode into repairs search"
                />
            </div>
            <div class="flex items-end gap-2 flex-wrap">
                <x-primary-button>Search</x-primary-button>
                <x-clear-filters :href="route('repairs.index')" :active="$repairsFiltersActive" />
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

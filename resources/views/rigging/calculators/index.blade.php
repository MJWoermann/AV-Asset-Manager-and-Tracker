<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl">Rigging calculators</h2>
            <a href="{{ route('rigging.index') }}" class="text-sm text-brand">Rigging hub</a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-3">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Planning aid only — verify against certified engineering and venue limits before flying.
        </p>
        <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
            @foreach($calculators as $key => $label)
                <li>
                    <a href="{{ route('rigging.calculators.show', $key) }}" class="flex items-center justify-between px-4 py-3 text-sm hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal/40">
                        <span>{{ $label }}</span>
                        <span class="text-brand-charcoal dark:text-brand-silver">→</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</x-app-layout>

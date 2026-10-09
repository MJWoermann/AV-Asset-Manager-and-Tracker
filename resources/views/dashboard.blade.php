<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-black dark:text-white">Dashboard</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <p class="text-brand-charcoal dark:text-brand-silver">AV equipment stocktake and asset management.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('scan.index') }}" class="block p-5 bg-black text-white border border-brand-charcoal hover:bg-brand-slate hover:border-brand transition">
                <div class="text-brand text-sm uppercase tracking-wide">Primary</div>
                <div class="mt-2 text-lg font-semibold">Scan to event</div>
                <p class="mt-1 text-sm text-brand-silver">Camera barcode scan with inventory compare</p>
            </a>
            <a href="{{ route('assets.index') }}" class="block p-5 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal hover:border-brand dark:hover:bg-brand-charcoal transition">
                <div class="text-brand text-sm uppercase tracking-wide">Inventory</div>
                <div class="mt-2 text-lg font-semibold">Assets</div>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Search and edit equipment</p>
            </a>
            <a href="{{ route('reports.compare') }}" class="block p-5 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal hover:border-brand dark:hover:bg-brand-charcoal transition">
                <div class="text-brand text-sm uppercase tracking-wide">Reports</div>
                <div class="mt-2 text-lg font-semibold">Compare lists</div>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Matched / missing / extras</p>
            </a>
        </div>
    </div>
</x-app-layout>

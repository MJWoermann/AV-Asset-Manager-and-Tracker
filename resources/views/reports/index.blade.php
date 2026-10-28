<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Reports</h2></x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Stocktake comparisons, unknown scans, and related tools.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('reports.compare') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Compare</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Matched, inventory-only, and scanned-only between lists.</p>
            </a>
            @role('admin|inventory_manager')
                <a href="{{ route('reports.checkouts') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Checkout holdings</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Checked-out and available stock, and who currently holds each item.</p>
                </a>
            @endrole
            <a href="{{ route('reports.unknown-scans') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Unknown scans</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Codes that did not match an asset during scanning.</p>
            </a>
            @feature('asset_connections')
                <a href="{{ route('reports.connection-map') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Connection map</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Visualise asset interface connections.</p>
                </a>
            @endfeature
            @feature('shelf_tag_generator')
                @role('admin|inventory_manager')
                    <a href="{{ route('labels.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                        <h3 class="font-semibold">Labels</h3>
                        <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Shelf tag layouts and printing.</p>
                    </a>
                @endrole
            @endfeature
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Data</h2></x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Reference data used across assets, lists, and scanning.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('locations.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Locations</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Venue and storage locations for assets.</p>
            </a>
            <a href="{{ route('manufacturers.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Manufacturers</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Manufacturer catalogue and models.</p>
            </a>
            @can('assets:delete')
                <a href="{{ route('assets.duplicates.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Duplicate check</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Find assets sharing barcodes, serials, or tags and merge them side-by-side.</p>
                </a>
            @endcan
            @can('catalog:update')
                <a href="{{ route('item-types.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Item Types</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Templates that define asset fields and behaviour.</p>
                </a>
                <a href="{{ route('custom-field-sets.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Field sets</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Reusable custom fields for item types.</p>
                </a>
            @endcan
            @can('files:manage')
                <a href="{{ route('files.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Files</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Uploaded attachments and linked documents.</p>
                </a>
            @endcan
            @feature('asset_connections')
                @can('connections:write')
                    <a href="{{ route('connection-types.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                        <h3 class="font-semibold">Connection types</h3>
                        <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Cable and interface connection categories.</p>
                    </a>
                @endcan
            @endfeature
        </div>
    </div>
</x-app-layout>

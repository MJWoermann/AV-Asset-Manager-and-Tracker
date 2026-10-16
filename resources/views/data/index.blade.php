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
            <a href="{{ route('spares.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Spares</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Critical spares register, kits, and usage history.</p>
            </a>
            <a href="{{ route('repairs.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Repairs</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Open and completed repair work orders linked to faults.</p>
            </a>
            @role('admin|inventory_manager')
                <a href="{{ route('repair-procedures.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Repair procedures</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Reusable SOP checklists scoped to models, types, or spares.</p>
                </a>
                <a href="{{ route('item-types.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Item Types</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Templates that define asset fields and behaviour.</p>
                </a>
                <a href="{{ route('custom-field-sets.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Field sets</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Reusable custom fields for item types.</p>
                </a>
                <a href="{{ route('files.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Files</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Uploaded attachments and linked documents.</p>
                </a>
                @feature('asset_connections')
                    <a href="{{ route('connection-types.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                        <h3 class="font-semibold">Connection types</h3>
                        <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Cable and interface connection categories.</p>
                    </a>
                @endfeature
            @endrole
        </div>
    </div>
</x-app-layout>

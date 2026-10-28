<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Maintenance</h2></x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Repairs, procedures, and critical spares.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('repairs.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Repairs</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">
                    @role('external_repairer')
                        Jobs assigned to you.
                    @else
                        Open and completed repair work orders linked to faults.
                    @endrole
                </p>
            </a>
            @unlessrole('external_repairer')
                @can('repairs:procedures')
                    <a href="{{ route('repair-procedures.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                        <h3 class="font-semibold">Repair procedures</h3>
                        <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Reusable SOP checklists scoped to models, types, or spares.</p>
                    </a>
                @endcan
                <a href="{{ route('spares.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Spares</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Critical spares register, kits, and usage history.</p>
                </a>
            @endunlessrole
        </div>
    </div>
</x-app-layout>

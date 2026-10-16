<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Rigging</h2></x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Planning and process aids for venue rigging. Not a substitute for certified structural engineering, venue manuals, or Permit to Work procedures.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('rigging.events.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Rig Events</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Documentation, checklist, motor patching, and structure sign-off.</p>
            </a>
            <a href="{{ route('rigging.calculators.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Calculators</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Load share, bridles, levers, UDL, projector throw, and unit conversion.</p>
            </a>
        </div>
    </div>
</x-app-layout>

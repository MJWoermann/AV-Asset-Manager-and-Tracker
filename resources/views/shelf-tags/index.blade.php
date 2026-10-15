<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Labels</h2>
            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('labels.layouts.import') }}" class="px-3 py-2 border border-brand-charcoal/40 dark:border-brand-silver/40 text-sm inline-flex items-center justify-center">Import .lbx</a>
                <a href="{{ route('labels.layouts.create') }}" class="px-3 py-2 bg-brand text-white text-sm inline-flex items-center justify-center">New layout</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            <section class="min-w-0 lg:flex-[1.35] space-y-3 order-1">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Layouts</h3>
                @include('shelf-tags.layouts.partials.list')
            </section>

            <section class="min-w-0 lg:flex-1 order-2">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Print</h3>
                @include('shelf-tags.print.partials.setup')
            </section>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Spares register</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('spares.groups.index') }}" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Groups</a>
                @role('admin|inventory_manager')
                    <a href="{{ route('spares.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add spare</a>
                @endrole
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        <form method="get" class="flex flex-wrap gap-2 items-end text-sm">
            <div>
                <label class="block text-xs text-brand-charcoal dark:text-brand-silver">Search</label>
                <input type="search" name="q" value="{{ $term }}" class="border border-brand-silver dark:border-brand-charcoal px-2 py-1 w-48" placeholder="Code, component…">
            </div>
            <div>
                <label class="block text-xs text-brand-charcoal dark:text-brand-silver">Kind</label>
                <select name="kind" class="border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                    <option value="">All</option>
                    @foreach(\App\Enums\SparePartKind::options() as $value => $label)
                        <option value="{{ $value }}" @selected($kind === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-brand-charcoal dark:text-brand-silver">Severity</label>
                <select name="severity" class="border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                    <option value="">All</option>
                    @foreach(\App\Enums\SparePartSeverity::options() as $value => $label)
                        <option value="{{ $value }}" @selected($severity === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-brand-charcoal dark:text-brand-silver">Stock status</label>
                <select name="stock_status" class="border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                    <option value="">All</option>
                    @foreach(\App\Enums\SparePartStockStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($stockStatus ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 pb-1">
                <input type="checkbox" name="below_min" value="1" @checked($belowMin ?? false)>
                <span>Below minimum</span>
            </label>
            <label class="flex items-center gap-2 pb-1">
                <input type="checkbox" name="include_eol" value="1" @checked($includeEol)>
                <span>Include EOL / superseded</span>
            </label>
            <button type="submit" class="px-3 py-1 bg-black text-white">Filter</button>
        </form>

        @role('admin|inventory_manager')
            @feature('shelf_tag_generator')
                <form id="batch-print-form" method="post" action="{{ route('spares.labels.print') }}" class="text-sm border border-brand-silver dark:border-brand-charcoal p-3 space-y-2">
                    @csrf
                    <p class="font-medium">Print box labels (tick rows in the table)</p>
                    <div class="flex flex-wrap gap-2 items-end">
                        <div>
                            <label class="block text-xs">Layout</label>
                            <select name="shelf_tag_layout_id" required class="border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                                @foreach(\App\Models\ShelfTagLayout::query()->orderBy('name')->get() as $layout)
                                    <option value="{{ $layout->id }}">{{ $layout->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-3 py-1 border border-brand">Print HTML</button>
                        <button type="submit" name="format" value="pdf" class="px-3 py-1 bg-brand text-white">PDF</button>
                    </div>
                </form>
            @endfeature
        @endrole

        <div class="overflow-x-auto border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate text-sm">
            <table class="min-w-full">
                <thead class="bg-brand-charcoal/5 dark:bg-brand-charcoal text-left">
                    <tr>
                        @role('admin|inventory_manager')
                            @feature('shelf_tag_generator')
                                <th class="p-2 w-8"></th>
                            @endfeature
                        @endrole
                        <th class="p-2">Code</th>
                        <th class="p-2">Component</th>
                        <th class="p-2">Kind</th>
                        <th class="p-2">On hand</th>
                        <th class="p-2">Min/Max</th>
                        <th class="p-2">Severity</th>
                        <th class="p-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parts as $part)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal hover:bg-brand/5">
                            @role('admin|inventory_manager')
                                @feature('shelf_tag_generator')
                                    <td class="p-2">
                                        <input type="checkbox" name="spare_ids[]" value="{{ $part->id }}" form="batch-print-form">
                                    </td>
                                @endfeature
                            @endrole
                            <td class="p-2 font-mono">
                                <a href="{{ route('spares.show', $part) }}" class="text-brand hover:underline">{{ $part->code }}</a>
                            </td>
                            <td class="p-2">{{ $part->component }}</td>
                            <td class="p-2">{{ $part->kind?->label() }}</td>
                            <td class="p-2 @if($part->isBelowMin()) text-red-600 font-semibold @endif">{{ $part->quantity_on_hand }}</td>
                            <td class="p-2">{{ $part->min_holding ?? '—' }} / {{ $part->max_holding ?? '—' }}</td>
                            <td class="p-2">{{ $part->severity?->label() }}</td>
                            <td class="p-2">
                                {{ $part->lifecycle_status?->label() }}
                                @if($part->supersededBy)
                                    <span class="text-xs block text-brand-charcoal dark:text-brand-silver">→ {{ $part->supersededBy->code }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-4 text-brand-charcoal dark:text-brand-silver">No spare lines match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $parts->links() }}
    </div>
</x-app-layout>

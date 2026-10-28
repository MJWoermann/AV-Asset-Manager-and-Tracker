<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-brand-black dark:text-white">Dashboard</h2>
            <div
                class="relative"
                x-data="dashboardModules(@js($modules))"
                @click.outside="open = false"
            >
                <button
                    type="button"
                    class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver"
                    @click="open = ! open"
                    :aria-expanded="open.toString()"
                >
                    Customize modules
                </button>

                <div
                    x-show="open"
                    x-cloak
                    class="absolute z-40 mt-2 end-0 w-80 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-3 shadow-lg"
                    style="display: none;"
                >
                    <form method="POST" action="{{ route('preferences.dashboard-modules') }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                            Enable, disable, and reorder modules. Saved to your profile.
                        </p>
                        <ul class="space-y-2 max-h-80 overflow-y-auto">
                            <template x-for="(module, index) in modules" :key="module.id">
                                <li class="flex items-center gap-2 text-sm border border-brand-silver dark:border-brand-charcoal px-2 py-1.5">
                                    <input type="hidden" :name="'order[' + index + ']'" :value="module.id">
                                    <label class="flex items-center gap-2 flex-1 min-w-0">
                                        <input
                                            type="checkbox"
                                            name="enabled[]"
                                            :value="module.id"
                                            class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                                            :checked="module.enabled"
                                            @change="module.enabled = $event.target.checked"
                                        >
                                        <span class="truncate" x-text="module.label"></span>
                                    </label>
                                    <div class="flex shrink-0 gap-1">
                                        <button
                                            type="button"
                                            class="px-1.5 py-0.5 border border-brand-charcoal/30 dark:border-brand-silver text-xs disabled:opacity-40"
                                            @click="moveUp(index)"
                                            :disabled="index === 0"
                                            :aria-label="'Move ' + module.label + ' up'"
                                        >↑</button>
                                        <button
                                            type="button"
                                            class="px-1.5 py-0.5 border border-brand-charcoal/30 dark:border-brand-silver text-xs disabled:opacity-40"
                                            @click="moveDown(index)"
                                            :disabled="index === modules.length - 1"
                                            :aria-label="'Move ' + module.label + ' down'"
                                        >↓</button>
                                    </div>
                                </li>
                            </template>
                        </ul>
                        <button type="submit" class="w-full px-3 py-2 bg-black text-white dark:bg-brand text-sm">
                            Save modules
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="text-sm text-brand">{{ session('status') }}</div>
        @endif

        @php
            $enabledModuleIds = collect($modules)->where('enabled', true)->pluck('id')->all();
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($modules as $module)
                @continue(! ($module['enabled'] ?? false))

                @switch($module['id'])
                    @case('quick_links')
                        <div class="lg:col-span-2 xl:col-span-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($quickLinks as $link)
                                <a
                                    href="{{ $link['url'] }}"
                                    @class([
                                        'block p-5 border transition',
                                        'bg-black text-white border-brand-charcoal hover:bg-brand-slate hover:border-brand' => $link['primary'],
                                        'bg-white dark:bg-brand-slate border-brand-silver dark:border-brand-charcoal hover:border-brand dark:hover:bg-brand-charcoal' => ! $link['primary'],
                                    ])
                                >
                                    <div @class([
                                        'text-sm uppercase tracking-wide',
                                        'text-brand' => $link['primary'],
                                        'text-brand' => ! $link['primary'],
                                    ])>{{ $link['eyebrow'] }}</div>
                                    <div class="mt-2 text-lg font-semibold">{{ $link['label'] }}</div>
                                    <p @class([
                                        'mt-1 text-sm',
                                        'text-brand-silver' => $link['primary'],
                                        'text-brand-charcoal dark:text-brand-silver' => ! $link['primary'],
                                    ])>{{ $link['description'] }}</p>
                                </a>
                            @endforeach
                        </div>
                        @break

                    @case('stocktake_pulse')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Stocktake pulse</h3>
                                @if ($stocktakePulse['resumeUrl'])
                                    <a href="{{ $stocktakePulse['resumeUrl'] }}" class="text-xs text-brand hover:underline">Resume session</a>
                                @endif
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($stocktakePulse['buckets'] as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span @class(['text-red-700 dark:text-red-400' => $row['key'] === 'open_unknowns' && $row['count'] > 0])>
                                                {{ $row['label'] }}
                                            </span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('assets_needing_attention')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets needing attention</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($assetsNeedingAttention as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span @class(['text-red-700 dark:text-red-400' => $row['count'] > 0])>{{ $row['label'] }}</span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('assets_by_location')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets by location</h3>
                            </div>
                            @if ($assetsByLocation === [])
                                <p class="px-4 py-6 text-sm text-center text-brand-charcoal dark:text-brand-silver">No assets yet.</p>
                            @else
                                <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm max-h-80 overflow-y-auto">
                                    @foreach ($assetsByLocation as $row)
                                        <li>
                                            <a
                                                href="{{ $row['url'] }}"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span>{{ $row['label'] }}</span>
                                                <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        @break

                    @case('data_quality')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Catalogue completeness</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($dataQuality as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span @class(['text-red-700 dark:text-red-400' => $row['count'] > 0])>{{ $row['label'] }}</span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('list_activity')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Lists snapshot</h3>
                                <a href="{{ route('lists.index') }}" class="text-xs text-brand hover:underline">All lists</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($listActivity['counts'] as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span>{{ $row['label'] }}</span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="border-t border-brand-silver dark:border-brand-charcoal">
                                <p class="px-4 pt-3 pb-1 text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Recent activity</p>
                                @if ($listActivity['recent'] === [])
                                    <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No lists yet.</p>
                                @else
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        @foreach ($listActivity['recent'] as $list)
                                            <li>
                                                <a
                                                    href="{{ $list['url'] }}"
                                                    class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                                >
                                                    <span class="font-medium">{{ $list['name'] }}</span>
                                                    <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                        {{ ucfirst($list['type']) }} — {{ $list['items_count'] }} items
                                                        @if ($list['last_activity'])
                                                            — {{ $list['last_activity'] }}
                                                        @endif
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                        @break

                    @case('my_repairs')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">My repairs</h3>
                                <a href="{{ route('repairs.index', ['assignee' => 'me']) }}" class="text-xs text-brand hover:underline">View all</a>
                            </div>
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal text-sm flex items-center justify-between">
                                <span>Open / in progress</span>
                                <span class="font-mono tabular-nums">{{ $myRepairs['count'] }}</span>
                            </div>
                            @if ($myRepairs['recent']->isEmpty())
                                <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No active repairs assigned to you.</p>
                            @else
                                <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                    @foreach ($myRepairs['recent'] as $repair)
                                        <li>
                                            <a
                                                href="{{ route('repairs.show', $repair) }}"
                                                class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span class="font-medium">{{ $repair->summary }}</span>
                                                <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                    {{ $repair->asset?->name ?? 'Unknown asset' }}
                                                    — {{ $repair->status?->label() }}
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        @break

                    @case('spare_movements_recent')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Recent spare usage</h3>
                                <a href="{{ route('spares.index') }}" class="text-xs text-brand hover:underline">Register</a>
                            </div>
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal text-sm flex items-center justify-between">
                                <span>Usage events (7 days)</span>
                                <span class="font-mono tabular-nums">{{ $spareMovementsRecent['usage7Days'] }}</span>
                            </div>
                            @if ($spareMovementsRecent['recent']->isEmpty())
                                <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No spare movements yet.</p>
                            @else
                                <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                    @foreach ($spareMovementsRecent['recent'] as $movement)
                                        <li class="px-4 py-2.5">
                                            <span class="font-medium">{{ $movement->sparePart?->component ?? 'Spare part' }}</span>
                                            <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                {{ $movement->recorded_at?->format('Y-m-d H:i') }}
                                                — Δ {{ $movement->quantity_delta }}
                                                @if ($movement->againstAsset)
                                                    — {{ $movement->againstAsset->name }}
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        @break

                    @case('consumables_stats')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Consumables statistics</h3>
                                <a href="{{ route('assets.index', ['stock_tracked' => 1]) }}" class="text-xs text-brand hover:underline">Browse</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($consumablesStats['buckets'] as $row)
                                    <li>
                                        @if ($row['url'])
                                            <a
                                                href="{{ $row['url'] }}"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span @class(['text-red-700 dark:text-red-400' => in_array($row['key'], ['below_min', 'zero_on_hand', 'breakages_30'], true) && $row['count'] > 0])>
                                                    {{ $row['label'] }}
                                                </span>
                                                <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                            </a>
                                        @else
                                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                                <span>{{ $row['label'] }}</span>
                                                <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            <div class="border-t border-brand-silver dark:border-brand-charcoal">
                                <p class="px-4 pt-3 pb-1 text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Recent restocks</p>
                                @if ($consumablesStats['recentRestocks']->isEmpty())
                                    <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No restocks recorded.</p>
                                @else
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        @foreach ($consumablesStats['recentRestocks'] as $movement)
                                            <li>
                                                <a
                                                    href="{{ $movement->asset ? route('assets.show', [$movement->asset, 'tab' => 'stock']) : route('assets.index', ['stock_tracked' => 1]) }}"
                                                    class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                                >
                                                    <span class="font-medium">{{ $movement->asset?->name ?? 'Asset' }}</span>
                                                    <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                        {{ $movement->recorded_at?->format('Y-m-d H:i') }}
                                                        — +{{ $movement->quantity_delta }}
                                                        @if ($movement->external_po_number)
                                                            — PO {{ $movement->external_po_number }}
                                                        @endif
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                        @break

                    @case('connection_coverage')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Connection coverage</h3>
                                <a href="{{ route('reports.connection-map') }}" class="text-xs text-brand hover:underline">Map</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($connectionCoverage as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span @class(['text-red-700 dark:text-red-400' => $row['count'] > 0])>{{ $row['label'] }}</span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('rigging_upcoming')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Rigging events</h3>
                                <a href="{{ route('rigging.events.index') }}" class="text-xs text-brand hover:underline">All events</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($riggingUpcoming as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span>{{ $row['label'] }}</span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('warranty_expiry')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Warranty expiry</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($warrantyExpiry as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span @class(['text-red-700 dark:text-red-400' => $row['key'] === 'overdue' && $row['count'] > 0])>
                                                {{ $row['label'] }}
                                            </span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('test_tag_expiry')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Test &amp; Tag expiry</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($testTagExpiry as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span @class(['text-red-700 dark:text-red-400' => $row['key'] === 'overdue' && $row['count'] > 0])>
                                                {{ $row['label'] }}
                                            </span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('assets_by_status')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets by status</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($byStatus as $row)
                                    <li>
                                        <a
                                            href="{{ route('assets.index', ['status' => $row['value']]) }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span>{{ $row['label'] }}</span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('items_users_by_department')
                        @role('admin')
                            <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal h-full flex flex-col">
                                <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Items &amp; users by department</h3>
                                </div>
                                @if (($itemsUsersByDepartment ?? []) === [])
                                    <p class="px-4 py-6 text-sm text-center text-brand-charcoal dark:text-brand-silver min-h-80 flex items-center justify-center">No departments yet.</p>
                                @else
                                    <div class="overflow-x-auto h-80 overflow-y-auto flex-1">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left sticky top-0">
                                                <tr>
                                                    <th class="px-3 py-2 w-8"></th>
                                                    <th class="px-3 py-2">Department</th>
                                                    <th class="px-3 py-2 text-right">Items</th>
                                                    <th class="px-3 py-2 text-right">Users</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($itemsUsersByDepartment as $row)
                                                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                                                        <td class="px-3 py-2">
                                                            @if ($row['color'])
                                                                <span
                                                                    class="inline-block h-3.5 w-3.5 rounded border border-brand-charcoal/30 dark:border-brand-silver/40"
                                                                    style="background-color: {{ $row['color'] }}"
                                                                    title="{{ $row['color'] }}"
                                                                ></span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <a href="{{ $row['url'] }}" class="hover:text-brand @if(! $row['is_active']) text-brand-charcoal dark:text-brand-silver @endif">
                                                                {{ $row['name'] }}
                                                                @unless($row['is_active'])
                                                                    <span class="text-xs">(inactive)</span>
                                                                @endunless
                                                            </a>
                                                        </td>
                                                        <td class="px-3 py-2 text-right font-mono tabular-nums">{{ $row['item_count'] }}</td>
                                                        <td class="px-3 py-2 text-right font-mono tabular-nums">{{ $row['user_count'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endrole
                        @break

                    @case('assets_by_type')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets by type</h3>
                            </div>
                            @if ($byType === [])
                                <p class="px-4 py-6 text-sm text-center text-brand-charcoal dark:text-brand-silver">No assets yet.</p>
                            @else
                                <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm max-h-80 overflow-y-auto">
                                    @foreach ($byType as $row)
                                        <li>
                                            <a
                                                href="{{ route('assets.index', ['item_type_id' => $row['id']]) }}"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span>{{ $row['name'] }}</span>
                                                <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        @break

                    @case('maintenance_activities')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Maintenance activities</h3>
                                <a href="{{ route('maintenance.index') }}" class="text-xs text-brand hover:underline">Hub</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($maintenanceActivities['buckets'] as $row)
                                    <li>
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span @class(['text-red-700 dark:text-red-400' => in_array($row['key'], ['open', 'in_progress', 'assets_maintenance', 'unrepaired_faults', 'damaged'], true) && $row['count'] > 0])>
                                                {{ $row['label'] }}
                                            </span>
                                            <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="border-t border-brand-silver dark:border-brand-charcoal">
                                <p class="px-4 pt-3 pb-1 text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Faults without an open repair</p>
                                @if ($maintenanceActivities['unrepairedFaults']->isEmpty())
                                    <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No faults waiting for a repair.</p>
                                @else
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        @foreach ($maintenanceActivities['unrepairedFaults'] as $faultAsset)
                                            <li>
                                                <a
                                                    href="{{ route('assets.show', [$faultAsset, 'tab' => 'maintenance']) }}"
                                                    class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                                >
                                                    <span class="font-medium">{{ $faultAsset->name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="border-t border-brand-silver dark:border-brand-charcoal">
                                <p class="px-4 pt-3 pb-1 text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Damaged</p>
                                @if ($maintenanceActivities['damagedAssets']->isEmpty())
                                    <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No damaged assets.</p>
                                @else
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        @foreach ($maintenanceActivities['damagedAssets'] as $damagedAsset)
                                            <li>
                                                <a
                                                    href="{{ route('assets.show', $damagedAsset) }}"
                                                    class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                                >
                                                    <span class="font-medium">{{ $damagedAsset->name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="border-t border-brand-silver dark:border-brand-charcoal">
                                <p class="px-4 pt-3 pb-1 text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Active repairs</p>
                                @if ($maintenanceActivities['recent']->isEmpty())
                                    <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No open repairs.</p>
                                @else
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        @foreach ($maintenanceActivities['recent'] as $repair)
                                            <li>
                                                <a
                                                    href="{{ route('repairs.show', $repair) }}"
                                                    class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                                >
                                                    <span class="font-medium">{{ $repair->summary }}</span>
                                                    <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                        {{ $repair->asset?->name ?? 'Unknown asset' }}
                                                        — {{ $repair->status?->label() }}
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                        @break

                    @case('spares_stats')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Spares statistics</h3>
                                <a href="{{ route('spares.index') }}" class="text-xs text-brand hover:underline">Register</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                @foreach ($sparesStats as $row)
                                    <li>
                                        @if ($row['url'])
                                            <a
                                                href="{{ $row['url'] }}"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span @class(['text-red-700 dark:text-red-400' => in_array($row['key'], ['below_min', 'quarantine', 'critical'], true) && $row['count'] > 0])>
                                                    {{ $row['label'] }}
                                                </span>
                                                <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                            </a>
                                        @else
                                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                                <span>{{ $row['label'] }}</span>
                                                <span class="font-mono tabular-nums">{{ $row['count'] }}</span>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @break

                    @case('inbound_emails')
                        @feature('email_integration')
                            @role('admin|inventory_manager')
                                <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                                    <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Inbound emails</h3>
                                    </div>
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        <li>
                                            <a
                                                href="{{ route('admin.email.inbound.index', ['status' => 'pending']) }}"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span>Pending review</span>
                                                <span class="font-mono tabular-nums">{{ $pendingInboundEmails ?? 0 }}</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            @endrole
                        @endfeature
                        @break

                    @case('latest_changes')
                        <div class="lg:col-span-2 xl:col-span-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Latest changes</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                                        <tr>
                                            <th class="px-3 py-2">When</th>
                                            <th class="px-3 py-2">Action</th>
                                            <th class="px-3 py-2">Asset</th>
                                            <th class="px-3 py-2">User</th>
                                            <th class="px-3 py-2">Changes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($latestChanges as $log)
                                            <tr class="border-t border-brand-silver dark:border-brand-charcoal align-top">
                                                <td class="px-3 py-2 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                                <td class="px-3 py-2 capitalize">{{ $log->action }}</td>
                                                <td class="px-3 py-2">
                                                    @if ($log->auditable)
                                                        <a
                                                            href="{{ route('assets.show', [$log->auditable, 'tab' => 'history']) }}"
                                                            class="text-brand hover:underline"
                                                        >{{ $log->auditable->name }}</a>
                                                    @else
                                                        <span class="text-brand-charcoal dark:text-brand-silver">Deleted asset #{{ $log->auditable_id }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2">{{ $log->user?->name ?? 'System' }}</td>
                                                <td class="px-3 py-2">
                                                    @if ($log->action === 'created')
                                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset created</span>
                                                    @elseif ($log->action === 'deleted')
                                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset deleted</span>
                                                    @else
                                                        @php $keys = $log->changedAttributeKeys(); @endphp
                                                        @if ($keys === [])
                                                            <span class="text-brand-charcoal dark:text-brand-silver">—</span>
                                                        @else
                                                            <ul class="space-y-1">
                                                                @foreach (array_slice($keys, 0, 3) as $key)
                                                                    <li>
                                                                        <span class="text-brand-charcoal dark:text-brand-silver">{{ str_replace('_', ' ', $key) }}:</span>
                                                                        <span class="font-mono">{{ $log->formatValue($log->old_values[$key] ?? null, 40) }}</span>
                                                                        →
                                                                        <span class="font-mono">{{ $log->formatValue($log->new_values[$key] ?? null, 40) }}</span>
                                                                    </li>
                                                                @endforeach
                                                                @if (count($keys) > 3)
                                                                    <li class="text-brand-charcoal dark:text-brand-silver">+{{ count($keys) - 3 }} more</li>
                                                                @endif
                                                            </ul>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No recent changes.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @break
                @endswitch
            @endforeach
        </div>

        @if ($enabledModuleIds === [])
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                No dashboard modules enabled. Use Customize modules to show widgets.
            </p>
        @endif
    </div>
</x-app-layout>

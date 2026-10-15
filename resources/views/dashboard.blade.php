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
        <p class="text-brand-charcoal dark:text-brand-silver">AV equipment stocktake and asset management.</p>

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

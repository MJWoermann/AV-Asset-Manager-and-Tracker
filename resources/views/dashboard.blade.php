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

        <section class="space-y-4" aria-labelledby="dashboard-overview-heading">
            <h3 id="dashboard-overview-heading" class="font-semibold text-lg text-brand-black dark:text-white">Overview</h3>

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                    <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets by status</h4>
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

                <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                    <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets by type</h4>
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

                @feature('email_integration')
                    @role('admin|inventory_manager')
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-brand">Inbound emails</h4>
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
            </div>

            <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                    <h4 class="text-sm font-semibold uppercase tracking-wide text-brand">Latest changes</h4>
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
        </section>
    </div>
</x-app-layout>

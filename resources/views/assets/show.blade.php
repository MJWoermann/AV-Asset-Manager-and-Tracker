<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">{{ $asset->name }}</h2>
            <div class="flex flex-wrap gap-2">
                @if($asset->ip_address)
                    <a href="http://{{ $asset->ip_address }}" target="_blank" rel="noopener" class="px-3 py-2 bg-brand text-white text-sm">Open IP</a>
                @endif
                <a href="{{ route('assets.edit', $asset) }}" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex gap-1 border-b border-brand-silver dark:border-brand-charcoal" role="tablist">
            <a
                href="{{ route('assets.show', $asset) }}"
                role="tab"
                aria-selected="{{ $tab === 'details' ? 'true' : 'false' }}"
                class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'details' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
            >
                Details
            </a>
            <a
                href="{{ route('assets.show', [$asset, 'tab' => 'history']) }}"
                role="tab"
                aria-selected="{{ $tab === 'history' ? 'true' : 'false' }}"
                class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'history' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
            >
                History
            </a>
        </div>

        @if($tab === 'history')
            <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                <table class="min-w-full text-sm">
                    <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                        <tr>
                            <th class="px-3 py-2">When</th>
                            <th class="px-3 py-2">Action</th>
                            <th class="px-3 py-2">User</th>
                            <th class="px-3 py-2">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr class="border-t border-brand-silver dark:border-brand-charcoal align-top">
                                <td class="px-3 py-2 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-2 capitalize">{{ $log->action }}</td>
                                <td class="px-3 py-2">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="px-3 py-2">
                                    @if($log->action === 'created')
                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset created</span>
                                    @elseif($log->action === 'deleted')
                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset deleted</span>
                                    @else
                                        @php $keys = $log->changedAttributeKeys(); @endphp
                                        @if($keys === [])
                                            <span class="text-brand-charcoal dark:text-brand-silver">—</span>
                                        @else
                                            <ul class="space-y-1">
                                                @foreach($keys as $key)
                                                    <li>
                                                        <span class="text-brand-charcoal dark:text-brand-silver">{{ str_replace('_', ' ', $key) }}:</span>
                                                        <span class="font-mono">{{ $log->formatValue($log->old_values[$key] ?? null, 40) }}</span>
                                                        →
                                                        <span class="font-mono">{{ $log->formatValue($log->new_values[$key] ?? null, 40) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No history recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $auditLogs->links() }}</div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 text-sm">
                <div class="md:col-span-2"><span class="text-brand-charcoal dark:text-brand-silver">Description</span><div>{{ $asset->description ?: '—' }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">Type</span><div>{{ $asset->itemType?->name }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">Status</span><div>{{ $asset->status?->label() }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">Manufacturer / Model</span><div>{{ $asset->manufacturer }} {{ $asset->model }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">Location</span><div>{{ $asset->location?->breadcrumb() ?? '—' }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">FMI AST#</span><div class="font-mono">{{ $asset->fmi_ast ?: '—' }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">TP Barcode</span><div class="font-mono">{{ $asset->tp_barcode ?: '—' }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">RIG Tag #</span><div class="font-mono">{{ $asset->rig_tag ?: '—' }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">Serial Number</span><div class="font-mono">{{ $asset->serial_number ?: '—' }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">IP / MAC</span><div class="font-mono">{{ $asset->ip_address ?: '—' }} / {{ $asset->mac_address ?: '—' }}</div></div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">Test &amp; Tag</span><div>{{ $asset->test_tag_expiry?->format('Y-m-d') ?: '—' }}</div></div>
                @if($asset->parent)
                    <div class="md:col-span-2"><span class="text-brand-charcoal dark:text-brand-silver">Parent</span><div><a class="text-brand" href="{{ route('assets.show', $asset->parent) }}">{{ $asset->parent->name }}</a></div></div>
                @endif
            </div>

            @if($asset->customFieldValues->isNotEmpty())
                <div>
                    <h3 class="font-semibold mb-2">Custom fields</h3>
                    <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 text-sm space-y-2">
                        @foreach($asset->customFieldValues as $value)
                            <div class="flex justify-between gap-4 border-b border-brand-silver/50 dark:border-brand-charcoal pb-1">
                                <span>{{ $value->definition?->name }}</span>
                                <span class="font-mono">{{ $value->value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($asset->children->isNotEmpty())
                <div>
                    <h3 class="font-semibold mb-2">Child assets</h3>
                    <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal divide-y divide-brand-silver dark:divide-brand-charcoal">
                        @foreach($asset->children as $child)
                            <li class="px-4 py-2 pl-8 text-sm">
                                <a class="text-brand" href="{{ route('assets.show', $child) }}">{{ $child->name }}</a>
                                <span class="text-brand-charcoal dark:text-brand-silver"> — {{ $child->tp_barcode }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>

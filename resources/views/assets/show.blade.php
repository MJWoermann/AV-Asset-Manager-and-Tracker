<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">{{ $asset->name }}</h2>
            <div class="flex flex-wrap gap-2">
                @if(! ($isExternalRepairer ?? false) && $asset->ip_address)
                    <a href="http://{{ $asset->ip_address }}" target="_blank" rel="noopener" class="px-3 py-2 bg-brand text-white text-sm">Open IP</a>
                @endif
                @unless($isExternalRepairer ?? false)
                    @php
                        $openableCustomUrls = collect();
                        $customValuesByDefinition = $asset->customFieldValues->keyBy('custom_field_definition_id');
                        foreach ($asset->itemType?->fieldSets ?? [] as $urlFieldSet) {
                            foreach ($urlFieldSet->definitions as $urlDef) {
                                if ($urlDef->type !== 'url') {
                                    continue;
                                }
                                $href = \App\Models\CustomFieldDefinition::openableHttpUrl(
                                    $customValuesByDefinition[$urlDef->id]->value ?? null
                                );
                                if ($href !== null) {
                                    $openableCustomUrls->push([
                                        'url' => $href,
                                        'name' => $urlDef->name,
                                    ]);
                                }
                            }
                        }
                    @endphp
                    @foreach($openableCustomUrls as $openableCustomUrl)
                        <a
                            href="{{ $openableCustomUrl['url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            title="{{ $openableCustomUrl['name'] }}"
                            class="px-3 py-2 bg-brand text-white text-sm"
                        >Open URL</a>
                    @endforeach
                    <form method="POST" action="{{ route('scan.prepare-parent', $asset) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Scan children</button>
                    </form>
                    @can('spares:manage')
                        @if($listedSpare ?? null)
                            <a href="{{ route('spares.show', $listedSpare) }}" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">In spares register</a>
                        @else
                            <form method="POST" action="{{ route('assets.spares.store', $asset) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">List in spares</button>
                            </form>
                        @endif
                    @endcan
                    <a href="{{ route('assets.edit', $asset) }}" data-history-roundtrip class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Edit</a>
                    @can('labels:read')
                        @feature('shelf_tag_generator')
                            <a href="{{ route('assets.label', $asset) }}" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Print</a>
                        @endfeature
                    @endcan
                @endunless
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
            @unless($isExternalRepairer ?? false)
            <a
                href="{{ route('assets.show', [$asset, 'tab' => 'files']) }}"
                role="tab"
                aria-selected="{{ $tab === 'files' ? 'true' : 'false' }}"
                class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'files' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
            >
                Files
            </a>
            @endunless
            @if($connectionsEnabled ?? false)
                <a
                    href="{{ route('assets.show', [$asset, 'tab' => 'connections']) }}"
                    role="tab"
                    aria-selected="{{ $tab === 'connections' ? 'true' : 'false' }}"
                    class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'connections' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
                >
                    Connections
                </a>
            @endif
            @if($ruVisualiserEnabled ?? false)
                <a
                    href="{{ route('assets.show', [$asset, 'tab' => 'rack']) }}"
                    role="tab"
                    aria-selected="{{ $tab === 'rack' ? 'true' : 'false' }}"
                    class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'rack' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
                >
                    Rack
                </a>
            @endif
            @if($stockEnabled ?? false)
                <a
                    href="{{ route('assets.show', [$asset, 'tab' => 'stock']) }}"
                    role="tab"
                    aria-selected="{{ $tab === 'stock' ? 'true' : 'false' }}"
                    class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'stock' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
                >
                    Stock
                </a>
            @endif
            @unless($isExternalRepairer ?? false)
            <a
                href="{{ route('assets.show', [$asset, 'tab' => 'checkout']) }}"
                role="tab"
                aria-selected="{{ $tab === 'checkout' ? 'true' : 'false' }}"
                class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'checkout' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
            >
                Checkout
            </a>
            @endunless
            <a
                href="{{ route('assets.show', [$asset, 'tab' => 'maintenance']) }}"
                role="tab"
                aria-selected="{{ $tab === 'maintenance' ? 'true' : 'false' }}"
                class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'maintenance' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
            >
                Maintenance
            </a>
            @unless($isExternalRepairer ?? false)
            <a
                href="{{ route('assets.show', [$asset, 'tab' => 'spares']) }}"
                role="tab"
                aria-selected="{{ $tab === 'spares' ? 'true' : 'false' }}"
                class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'spares' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
            >
                Spares
            </a>
            <a
                href="{{ route('assets.show', [$asset, 'tab' => 'history']) }}"
                role="tab"
                aria-selected="{{ $tab === 'history' ? 'true' : 'false' }}"
                class="px-4 py-2 text-sm border-b-2 -mb-px {{ $tab === 'history' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white' }}"
            >
                History
            </a>
            @endunless
        </div>

        @if($tab === 'rack')
            @include('assets.partials.rack', [
                'asset' => $asset,
                'ruPlacementsPayload' => $ruPlacementsPayload ?? [],
                'ruChildAssetOptions' => $ruChildAssetOptions ?? [],
                'ruUnplacedChildren' => $ruUnplacedChildren ?? collect(),
            ])
        @elseif($tab === 'stock')
            @include('assets.partials.stock', [
                'asset' => $asset,
                'stockChartPayload' => $stockChartPayload ?? [],
                'stockTokens' => $stockTokens ?? collect(),
                'stockReorderMeta' => $stockReorderMeta ?? [],
                'stockAction' => $stockAction ?? null,
            ])
        @elseif($tab === 'spares')
            @include('assets.partials.spares', ['spareMovements' => $spareMovements ?? collect()])
        @elseif($tab === 'connections')
            @include('assets.partials.connections', [
                'asset' => $asset,
                'connectionPeers' => $connectionPeers ?? collect(),
                'busMemberships' => $busMemberships ?? collect(),
                'connectionBusesByType' => $connectionBusesByType ?? [],
                'connectionTypes' => $connectionTypes ?? collect(),
                'peerAssetOptions' => $peerAssetOptions ?? [],
                'canApplyInterfaceTemplates' => $canApplyInterfaceTemplates ?? false,
            ])
        @elseif($tab === 'checkout')
            @include('assets.partials.checkout', [
                'asset' => $asset,
                'openCheckout' => $openCheckout ?? null,
                'checkoutHistory' => $checkoutHistory ?? null,
                'canManageCheckouts' => $canManageCheckouts ?? false,
                'checkoutPurposes' => $checkoutPurposes ?? [],
                'checkoutAssigneeOptions' => $checkoutAssigneeOptions ?? collect(),
                'checkoutOpenRepairs' => $checkoutOpenRepairs ?? collect(),
            ])
        @elseif($tab === 'files')
            <x-file-attachments
                linkable-type="asset"
                :linkable-id="$asset->id"
                :rows="$fileRows"
                :can-mutate="true"
            />
        @elseif($tab === 'maintenance')
            @include('assets.partials.maintenance', [
                'asset' => $asset,
                'maintenanceLogs' => $maintenanceLogs ?? collect(),
                'maintenanceUserOptions' => $maintenanceUserOptions ?? collect(),
                'maintenanceLogTypes' => $maintenanceLogTypes ?? [],
                'maintenanceStatuses' => $maintenanceStatuses ?? [],
                'canDeleteMaintenanceLogs' => $canDeleteMaintenanceLogs ?? false,
                'repairAssigneeOptions' => $repairAssigneeOptions ?? collect(),
            ])
        @elseif($tab === 'history')
            @php
                $historyFiltersActive = \App\Support\AssetHistoryFilters::hasActiveFilters(request());
            @endphp
            <form
                method="GET"
                action="{{ route('assets.show', $asset) }}"
                class="flex flex-col sm:flex-row flex-wrap gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4"
            >
                <input type="hidden" name="tab" value="history">
                <div>
                    <x-input-label for="date_from" value="From" />
                    <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="request('date_from')" onchange="this.form.requestSubmit()" />
                </div>
                <div>
                    <x-input-label for="date_to" value="To" />
                    <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="request('date_to')" onchange="this.form.requestSubmit()" />
                </div>
                <div class="min-w-[10rem]">
                    <x-input-label for="history_action" value="Action" />
                    <x-searchable-select
                        name="action"
                        id="history_action"
                        :options="collect($historyActionOptions)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()"
                        :selected="request('action')"
                        placeholder="All"
                        nullable
                        auto-submit
                        aria-label="Filter by action"
                    />
                </div>
                <div class="min-w-[10rem]">
                    <x-input-label for="history_user_id" value="User" />
                    <x-searchable-select
                        name="user_id"
                        id="history_user_id"
                        :options="$historyUserOptions"
                        :selected="request('user_id')"
                        placeholder="All"
                        nullable
                        auto-submit
                        aria-label="Filter by user"
                    />
                </div>
                <div class="flex items-end gap-2 flex-wrap">
                    <x-clear-filters
                        :href="route('assets.show', [$asset, 'tab' => 'history'])"
                        :active="$historyFiltersActive"
                    />
                </div>
            </form>

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
                                <td class="px-3 py-2 capitalize">{{ str_replace('_', ' ', $log->action) }}</td>
                                <td class="px-3 py-2">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="px-3 py-2">
                                    @if($log->action === 'created')
                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset created</span>
                                    @elseif($log->action === 'deleted')
                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset deleted</span>
                                    @elseif($log->action === 'file_linked')
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Linked file: {{ $log->new_values['title'] ?? '—' }}
                                            @if(! empty($log->new_values['category']))
                                                ({{ $log->new_values['category'] }})
                                            @endif
                                        </span>
                                    @elseif($log->action === 'file_unlinked')
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Unlinked file: {{ $log->old_values['title'] ?? '—' }}
                                        </span>
                                    @elseif($log->action === 'connection_created')
                                        @php $vals = $log->new_values ?? []; @endphp
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Connected to {{ $vals['peer_asset'] ?? '—' }}:
                                            {{ $vals['local_interface'] ?? '—' }} ↔ {{ $vals['peer_interface'] ?? '—' }}
                                            @if(! empty($vals['connection_type']))
                                                ({{ $vals['connection_type'] }}@if(! empty($vals['protocol'])) · {{ $vals['protocol'] }}@endif)
                                            @endif
                                            @if(! empty($vals['label']))
                                                — {{ $vals['label'] }}
                                            @endif
                                        </span>
                                    @elseif($log->action === 'connection_removed')
                                        @php $vals = $log->old_values ?? []; @endphp
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Disconnected from {{ $vals['peer_asset'] ?? '—' }}:
                                            {{ $vals['local_interface'] ?? '—' }} ↔ {{ $vals['peer_interface'] ?? '—' }}
                                            @if(! empty($vals['connection_type']))
                                                ({{ $vals['connection_type'] }}@if(! empty($vals['protocol'])) · {{ $vals['protocol'] }}@endif)
                                            @endif
                                            @if(! empty($vals['label']))
                                                — {{ $vals['label'] }}
                                            @endif
                                        </span>
                                    @elseif($log->action === 'bus_joined')
                                        @php $vals = $log->new_values ?? []; @endphp
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Joined bus {{ $vals['bus_name'] ?? '—' }}
                                            via {{ $vals['local_interface'] ?? '—' }}
                                            @if(! empty($vals['connection_type']))
                                                ({{ $vals['connection_type'] }})
                                            @endif
                                        </span>
                                    @elseif($log->action === 'bus_left')
                                        @php $vals = $log->old_values ?? []; @endphp
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Left bus {{ $vals['bus_name'] ?? '—' }}
                                            via {{ $vals['local_interface'] ?? '—' }}
                                            @if(! empty($vals['connection_type']))
                                                ({{ $vals['connection_type'] }})
                                            @endif
                                        </span>
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
                                <td colspan="4" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">
                                    {{ $historyFiltersActive ? 'No history matches these filters.' : 'No history recorded yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $auditLogs->links() }}</div>
        @else
            @php $ruDetailsPreview = $ruDetailsPreview ?? null; @endphp
            <div @class([
                'grid gap-4 items-start',
                'grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(12rem,16rem)]' => $ruDetailsPreview,
            ])>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 text-sm">
                    <div class="md:col-span-2"><span class="text-brand-charcoal dark:text-brand-silver">Description</span><div>{{ $asset->description ?: '—' }}</div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Type</span><div>{{ $asset->itemType?->name }}</div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Status</span><div>{{ $asset->status?->label() }}</div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Manufacturer / Model</span><div>{{ trim(($asset->manufacturer?->name ?? '').' '.($asset->manufacturerModel?->name ?? '')) ?: '—' }}</div></div>
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

                @if($ruDetailsPreview)
                    <div
                        class="space-y-2"
                        x-data="ruRackPreview(@js($ruDetailsPreview))"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold">Rack front</h3>
                            <a href="{{ route('assets.show', [$asset, 'tab' => 'rack']) }}" class="text-xs text-brand">Open Rack</a>
                        </div>
                        @include('assets.partials.ru-diagram', ['interactive' => false, 'compact' => true])
                    </div>
                @endif
            </div>

            @if($asset->itemType?->fieldSets->isNotEmpty())
                @php
                    $customValues = $asset->customFieldValues->keyBy('custom_field_definition_id');
                @endphp
                @foreach($asset->itemType->fieldSets as $set)
                    @continue($set->definitions->isEmpty())
                    <div>
                        <h3 class="font-semibold mb-2">{{ $set->name }}</h3>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 text-sm grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($set->definitions as $def)
                                @if($def->type === 'divider')
                                    <div class="sm:col-span-2 py-1">
                                        @if($def->name !== '' && strcasecmp($def->name, 'Divider') !== 0)
                                            <div class="font-medium text-brand-charcoal dark:text-brand-silver mb-2">{{ $def->name }}</div>
                                        @endif
                                        <hr class="border-brand-silver dark:border-brand-charcoal">
                                    </div>
                                @elseif($def->type === 'spacer')
                                    <div class="min-h-[1.5rem]" aria-hidden="true"></div>
                                @else
                                    @php
                                        $customValue = $customValues[$def->id]->value ?? null;
                                        $openableUrl = $def->type === 'url'
                                            ? \App\Models\CustomFieldDefinition::openableHttpUrl($customValue)
                                            : null;
                                    @endphp
                                    <div class="flex justify-between gap-4 border-b border-brand-silver/50 dark:border-brand-charcoal pb-1">
                                        <span>{{ $def->name }}</span>
                                        <span class="font-mono text-right inline-flex flex-col items-end gap-1 sm:flex-row sm:items-center">
                                            <span>{{ $customValue ?: '—' }}</span>
                                            @if($openableUrl)
                                                <a
                                                    href="{{ $openableUrl }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex items-center px-2 py-0.5 text-xs bg-brand text-white no-underline font-sans"
                                                >Open URL</a>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @elseif($asset->customFieldValues->isNotEmpty())
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

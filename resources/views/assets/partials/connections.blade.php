@php
    $allowsProtocolByType = $connectionTypes->mapWithKeys(fn ($type) => [(string) $type->id => (bool) $type->allows_protocol])->all();
    $localInterfacesPayload = $asset->interfaces->map(fn ($interface) => [
        'id' => (string) $interface->id,
        'name' => $interface->name,
        'connection_type_id' => $interface->connection_type_id ? (string) $interface->connection_type_id : null,
    ])->values()->all();
@endphp

<div class="space-y-8" x-data="assetConnectionsForm(@js([
    'peerInterfacesUrl' => route('assets.peer-interfaces', [$asset, 'peer' => '__PEER__']),
    'allowsProtocolByType' => $allowsProtocolByType,
    'localInterfaces' => $localInterfacesPayload,
    'peerAssets' => $peerAssetOptions,
    'old' => [
        'local_interface_id' => old('local_interface_id') ? (string) old('local_interface_id') : '',
        'peer_asset_id' => old('peer_asset_id') ? (string) old('peer_asset_id') : '',
        'peer_interface_id' => old('peer_interface_id') ? (string) old('peer_interface_id') : '',
        'new_peer_interface_name' => old('new_peer_interface_name', ''),
        'create_peer_interface' => old('new_peer_interface_name') ? true : false,
    ],
]))">
    @if (session('status'))
        <p class="text-sm text-brand">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="text-sm text-red-600 space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-lg">Interfaces</h3>
        </div>

        <div class="space-y-2">
            @forelse($asset->interfaces as $interface)
                <div class="bg-white dark:bg-brand-slate border px-3 py-2">
                    <form method="POST" action="{{ route('assets.interfaces.update', [$asset, $interface]) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @method('PUT')
                        <div class="min-w-[8rem] flex-1">
                            <x-text-input name="name" class="block w-full" :value="old('name', $interface->name)" required placeholder="Name" aria-label="Name" title="Name" />
                        </div>
                        <div class="min-w-[9rem] w-44">
                            <select name="connection_type_id" class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required aria-label="Connection type" title="Connection type">
                                <option value="">Type…</option>
                                @foreach($connectionTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('connection_type_id', $interface->connection_type_id) === (string) $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-20">
                            <x-text-input name="sort_order" type="number" class="block w-full" :value="old('sort_order', $interface->sort_order)" placeholder="Sort" aria-label="Sort" title="Sort" />
                        </div>
                        <div class="min-w-[8rem] flex-1">
                            <x-text-input name="notes" class="block w-full" :value="old('notes', $interface->notes)" placeholder="Notes" aria-label="Notes" title="Notes" />
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($interface->isFromTemplate())
                                <span class="text-xs text-brand whitespace-nowrap" title="From item type template">Template</span>
                            @endif
                            <x-secondary-button type="submit">Save</x-secondary-button>
                            <button
                                type="submit"
                                form="destroy-interface-{{ $interface->id }}"
                                class="text-sm text-red-600 whitespace-nowrap"
                                onclick="return confirm('Remove this interface and its connections?')"
                            >Remove</button>
                        </div>
                    </form>
                    <form id="destroy-interface-{{ $interface->id }}" method="POST" action="{{ route('assets.interfaces.destroy', [$asset, $interface]) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            @empty
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">No interfaces yet. Add one below or sync from the item type templates.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('assets.interfaces.store', $asset) }}" class="bg-white dark:bg-brand-slate border px-3 py-2 flex flex-wrap items-center gap-2">
            @csrf
            <div class="min-w-[8rem] flex-1">
                <x-text-input id="new_interface_name" name="name" class="block w-full" required placeholder="Name" aria-label="Interface name" title="Name" />
            </div>
            <div class="min-w-[9rem] w-44">
                <select id="new_interface_type" name="connection_type_id" class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required aria-label="Connection type" title="Connection type">
                    <option value="">Type…</option>
                    @foreach($connectionTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[8rem] flex-1">
                <x-text-input name="notes" class="block w-full" placeholder="Notes" aria-label="Notes" title="Notes" />
            </div>
            <div class="shrink-0">
                <x-primary-button>Add</x-primary-button>
            </div>
        </form>
    </section>

    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-lg">Connections</h3>
            @if($connectionPeers->isNotEmpty())
                <a
                    href="{{ route('reports.connection-map', ['asset_id' => $asset->id]) }}"
                    class="text-sm text-brand font-medium"
                >View connection map</a>
            @endif
        </div>

        <div class="space-y-2">
            @forelse($connectionPeers as $row)
                @php
                    /** @var \App\Models\AssetConnection $connection */
                    $connection = $row['connection'];
                    $local = $row['local_interface'];
                    $peerInterface = $row['peer_interface'];
                    $peerAsset = $row['peer_asset'];
                    $type = $connection->connectionType;
                @endphp
                <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-start">
                    <div class="min-w-0 space-y-1">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="inline-block h-3 w-3 rounded-sm" style="background-color: {{ $type?->color ?? '#64748b' }}"></span>
                            <x-connection-type-icon :icon="$type?->icon ?? 'generic'" class="h-4 w-4" />
                            <span class="font-medium">{{ $type?->name ?? 'Connection' }}</span>
                            @if($connection->protocol)
                                <span class="text-brand-charcoal dark:text-brand-silver">({{ $connection->protocol }})</span>
                            @endif
                        </div>
                        <p class="text-sm">
                            {{ $local->name }}
                            →
                            <a href="{{ route('assets.show', [$peerAsset, 'tab' => 'connections']) }}" class="text-brand font-medium">{{ $peerAsset->name }}</a>
                            / {{ $peerInterface->name }}
                        </p>
                        @if($connection->label || $connection->notes)
                            <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                                {{ $connection->label }}
                                @if($connection->label && $connection->notes) · @endif
                                {{ $connection->notes }}
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('assets.connections.destroy', [$asset, $connection]) }}" onsubmit="return confirm('Remove this connection?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600">Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">No connections yet.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('assets.connections.store', $asset) }}" class="bg-white dark:bg-brand-slate border p-4 space-y-3">
            @csrf
            <h4 class="text-sm font-semibold">Add connection</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-input-label for="local_interface_id" value="Local interface" />
                    <select
                        id="local_interface_id"
                        name="local_interface_id"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        required
                        x-model="localInterfaceId"
                        @change="onLocalInterfaceChange()"
                    >
                        <option value="">Select…</option>
                        @foreach($asset->interfaces as $interface)
                            <option value="{{ $interface->id }}">
                                {{ $interface->name }}@if($interface->connectionType) ({{ $interface->connectionType->name }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="peer_asset_id" value="Peer asset" />
                    <div
                        class="relative mt-1"
                        x-ref="peerSelect"
                        @keydown="onPeerKeydown($event)"
                        @click.outside="closePeerList()"
                    >
                        <input type="hidden" name="peer_asset_id" id="peer_asset_id" x-model="peerAssetId" required>

                        <div class="flex gap-1">
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 items-center justify-between gap-2 rounded border border-gray-300 bg-white px-3 py-2 text-left text-sm dark:border-brand-charcoal dark:bg-black dark:text-white"
                                @click="peerOpen ? closePeerList() : openPeerList()"
                                :aria-expanded="peerOpen.toString()"
                                aria-haspopup="listbox"
                                aria-controls="peer_asset_id-listbox"
                            >
                                <span class="truncate" :class="peerAssetId ? '' : 'text-brand-charcoal dark:text-brand-silver'" x-text="peerSelectedLabel"></span>
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div
                            x-show="peerOpen"
                            x-cloak
                            class="absolute z-20 mt-1 w-full border border-brand-silver bg-white shadow-sm dark:border-brand-charcoal dark:bg-brand-slate"
                            id="peer_asset_id-listbox"
                            role="listbox"
                        >
                            <div class="border-b border-brand-silver p-2 dark:border-brand-charcoal">
                                <input
                                    type="search"
                                    x-ref="peerSearch"
                                    x-model="peerQuery"
                                    placeholder="Search…"
                                    class="block w-full rounded border-gray-300 text-sm dark:border-brand-charcoal dark:bg-black dark:text-white"
                                    autocomplete="off"
                                >
                            </div>
                            <ul class="max-h-60 overflow-y-auto py-1 text-sm">
                                <template x-if="filteredPeerAssets.length === 0">
                                    <li class="px-3 py-2 text-brand-charcoal dark:text-brand-silver">No matches</li>
                                </template>
                                <template x-for="(option, index) in filteredPeerAssets" :key="option.value">
                                    <li>
                                        <button
                                            type="button"
                                            class="block w-full px-3 py-2 text-left hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal"
                                            :class="{
                                                'bg-brand-silver/40 dark:bg-brand-charcoal': index === peerHighlight || option.value === peerAssetId,
                                            }"
                                            role="option"
                                            :aria-selected="(option.value === peerAssetId).toString()"
                                            @click="selectPeerAsset(option)"
                                            @mouseenter="peerHighlight = index"
                                        >
                                            <span x-html="highlightedPeerLabel(option.label)"></span>
                                            <span
                                                class="ml-1 text-xs text-brand"
                                                x-show="option.matches_type"
                                                x-cloak
                                            >matching type</span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <x-input-label for="peer_interface_id" value="Peer interface" />
                    <select
                        id="peer_interface_id"
                        name="peer_interface_id"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        x-model="peerInterfaceId"
                        :disabled="!peerAssetId || createPeerInterface"
                        :required="Boolean(peerAssetId) && !createPeerInterface"
                    >
                        <option value="">Select peer asset first…</option>
                        <template x-for="iface in peerInterfaces" :key="iface.id">
                            <option :value="iface.id" x-text="iface.matches_type ? iface.name + ' ★' : iface.name"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-show="peerLoading">Loading interfaces…</p>
                    <label class="mt-2 flex items-center gap-2 text-sm" x-show="peerAssetId" x-cloak>
                        <input type="checkbox" x-model="createPeerInterface" @change="onCreatePeerToggle()">
                        <span>Create new peer interface</span>
                    </label>
                    <div class="mt-2" x-show="createPeerInterface" x-cloak>
                        <x-input-label for="new_peer_interface_name" value="New peer interface name" />
                        <input
                            type="text"
                            id="new_peer_interface_name"
                            name="new_peer_interface_name"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            x-model="newPeerInterfaceName"
                            :required="createPeerInterface"
                            :disabled="!createPeerInterface"
                        >
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                            Uses the local interface connection type.
                        </p>
                    </div>
                </div>
                <div x-show="needsProtocol" x-cloak>
                    <x-input-label for="protocol" value="Protocol" />
                    <x-text-input id="protocol" name="protocol" class="mt-1 block w-full" :value="old('protocol')" />
                </div>
                <div>
                    <x-input-label for="label" value="Label" />
                    <x-text-input id="label" name="label" class="mt-1 block w-full" :value="old('label')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('notes') }}</textarea>
                </div>
            </div>

            <x-primary-button>Create connection</x-primary-button>
        </form>
    </section>
</div>

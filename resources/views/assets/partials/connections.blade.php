@php
    $allowsProtocolByType = $connectionTypes->mapWithKeys(fn ($type) => [(string) $type->id => (bool) $type->allows_protocol])->all();
@endphp

<div class="space-y-8" x-data="assetConnectionsForm(@js([
    'peerInterfacesUrl' => route('assets.peer-interfaces', [$asset, 'peer' => '__PEER__']),
    'allowsProtocolByType' => $allowsProtocolByType,
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

        <div class="space-y-3">
            @forelse($asset->interfaces as $interface)
                <div class="bg-white dark:bg-brand-slate border p-4 space-y-3">
                    <form method="POST" action="{{ route('assets.interfaces.update', [$asset, $interface]) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                        @csrf
                        @method('PUT')
                        <div class="sm:col-span-2">
                            <x-input-label value="Name" />
                            <x-text-input name="name" class="mt-1 block w-full" :value="old('name', $interface->name)" required />
                        </div>
                        <div>
                            <x-input-label value="Sort" />
                            <x-text-input name="sort_order" type="number" class="mt-1 block w-full" :value="old('sort_order', $interface->sort_order)" />
                        </div>
                        <div class="flex gap-2">
                            <x-secondary-button type="submit">Save</x-secondary-button>
                        </div>
                        <div class="sm:col-span-4">
                            <x-input-label value="Notes" />
                            <x-text-input name="notes" class="mt-1 block w-full" :value="old('notes', $interface->notes)" />
                            @if($interface->isFromTemplate())
                                <p class="mt-1 text-xs text-brand">From item type template</p>
                            @endif
                        </div>
                    </form>
                    <form method="POST" action="{{ route('assets.interfaces.destroy', [$asset, $interface]) }}" onsubmit="return confirm('Remove this interface and its connections?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600">Remove interface</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">No interfaces yet. Add one below or sync from the item type templates.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('assets.interfaces.store', $asset) }}" class="bg-white dark:bg-brand-slate border p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            @csrf
            <div class="sm:col-span-2">
                <x-input-label for="new_interface_name" value="Add interface" />
                <x-text-input id="new_interface_name" name="name" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-primary-button>Add</x-primary-button>
            </div>
            <div class="sm:col-span-3">
                <x-input-label value="Notes" />
                <x-text-input name="notes" class="mt-1 block w-full" />
            </div>
        </form>
    </section>

    <section class="space-y-3">
        <h3 class="font-semibold text-lg">Connections</h3>

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
                    <select id="local_interface_id" name="local_interface_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                        <option value="">Select…</option>
                        @foreach($asset->interfaces as $interface)
                            <option value="{{ $interface->id }}" @selected((string) old('local_interface_id') === (string) $interface->id)>{{ $interface->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="peer_asset_id" value="Peer asset" />
                    <select
                        id="peer_asset_id"
                        name="peer_asset_id"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        x-model="peerAssetId"
                        @change="loadPeerInterfaces()"
                        required
                    >
                        <option value="">Select…</option>
                        @foreach($peerAssetOptions as $option)
                            <option value="{{ $option['value'] }}" @selected((string) old('peer_asset_id') === (string) $option['value'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="peer_interface_id" value="Peer interface" />
                    <select id="peer_interface_id" name="peer_interface_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required x-model="peerInterfaceId">
                        <option value="">Select peer asset first…</option>
                        <template x-for="iface in peerInterfaces" :key="iface.id">
                            <option :value="iface.id" x-text="iface.name"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-show="peerLoading">Loading interfaces…</p>
                </div>
                <div>
                    <x-input-label for="connection_type_id" value="Connection type" />
                    <select id="connection_type_id" name="connection_type_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required x-model="connectionTypeId">
                        <option value="">Select…</option>
                        @foreach($connectionTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('connection_type_id') === (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
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

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Connection map</h2>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
        x-data="connectionMap(@js($graph))"
    >
        <form method="GET" action="{{ route('reports.connection-map') }}" class="bg-white dark:bg-brand-slate border p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <x-input-label for="search" value="Search" />
                <x-text-input id="search" name="search" class="mt-1 block w-full" :value="$filters['search'] ?? ''" placeholder="Asset name / barcode" />
            </div>
            <div>
                <x-input-label for="location_id" value="Location" />
                <select id="location_id" name="location_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="">All</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="item_type_id" value="Item type" />
                <select id="item_type_id" name="item_type_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="">All</option>
                    @foreach($itemTypes as $itemType)
                        <option value="{{ $itemType->id }}" @selected((string) ($filters['item_type_id'] ?? '') === (string) $itemType->id)>{{ $itemType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-primary-button>Apply filters</x-primary-button>
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <x-input-label value="Connection types" />
                <div class="mt-2 flex flex-wrap gap-3">
                    @foreach($connectionTypes as $type)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                name="connection_type_ids[]"
                                value="{{ $type->id }}"
                                class="rounded text-brand"
                                @checked(in_array((string) $type->id, array_map('strval', $filters['connection_type_ids'] ?? []), true))
                            >
                            <span class="inline-block h-3 w-3 rounded-sm" style="background-color: {{ $type->color }}"></span>
                            {{ $type->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-white dark:bg-brand-slate border min-h-[28rem]">
                <div x-ref="cy" class="w-full h-[28rem]"></div>
                <p class="px-3 py-2 text-xs text-brand-charcoal dark:text-brand-silver border-t border-brand-silver dark:border-brand-charcoal">
                    {{ count($graph['nodes']) }} assets · {{ count($graph['edges']) }} connections
                </p>
            </div>

            <aside class="bg-white dark:bg-brand-slate border p-4 space-y-3 min-h-[28rem]">
                <h3 class="font-semibold">Details</h3>
                <template x-if="!selection">
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">Click a node or edge to inspect it.</p>
                </template>
                <template x-if="selection && selection.kind === 'node'">
                    <div class="space-y-2 text-sm">
                        <p class="font-medium" x-text="selection.label"></p>
                        <p class="text-brand-charcoal dark:text-brand-silver">
                            <span x-text="selection.item_type || '—'"></span>
                            ·
                            <span x-text="selection.location || 'No location'"></span>
                        </p>
                        <a :href="selection.url" class="inline-block text-brand">Open Connections tab</a>
                    </div>
                </template>
                <template x-if="selection && selection.kind === 'edge'">
                    <div class="space-y-2 text-sm">
                        <p class="font-medium" x-text="selection.label"></p>
                        <p x-show="selection.protocol" class="text-brand-charcoal dark:text-brand-silver">
                            Protocol: <span x-text="selection.protocol"></span>
                        </p>
                        <p x-show="selection.connection_label" class="text-brand-charcoal dark:text-brand-silver" x-text="selection.connection_label"></p>
                        <p>
                            <a :href="selection.asset_a_url" class="text-brand" x-text="selection.asset_a_name"></a>
                            / <span x-text="selection.interface_a"></span>
                        </p>
                        <p>
                            <a :href="selection.asset_b_url" class="text-brand" x-text="selection.asset_b_name"></a>
                            / <span x-text="selection.interface_b"></span>
                        </p>
                        <p x-show="selection.notes" class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="selection.notes"></p>
                    </div>
                </template>
            </aside>
        </div>
    </div>
</x-app-layout>

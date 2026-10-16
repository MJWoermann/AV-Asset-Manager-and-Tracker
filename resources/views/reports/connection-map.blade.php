<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Connection map</h2>
    </x-slot>

    <div
        class="px-4 sm:px-6 lg:px-8"
        x-data="connectionMap(@js($graph))"
    >
        <form method="GET" action="{{ route('reports.connection-map') }}" class="space-y-2">
            @if($filters['asset_id'] ?? null)
                <input type="hidden" name="asset_id" value="{{ $filters['asset_id'] }}">
            @endif

            @if($focusAsset)
                <div class="flex flex-wrap items-center gap-2 text-sm bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal px-3 py-2">
                    <span>
                        Focused on
                        <a href="{{ route('assets.show', [$focusAsset, 'tab' => 'connections']) }}" class="text-brand font-medium">{{ $focusAsset->name }}</a>
                        — connected assets fan out from the centre.
                    </span>
                    <a
                        href="{{ route('reports.connection-map', $clearFocusQuery) }}"
                        class="text-brand-charcoal dark:text-brand-silver underline"
                    >Show all</a>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 items-end">
                <div class="min-w-[10rem] flex-1 sm:flex-none sm:w-52">
                    <x-input-label for="search" value="Search" class="sr-only" />
                    <x-text-input id="search" name="search" class="block w-full text-sm" :value="$filters['search'] ?? ''" placeholder="Search asset / barcode" />
                </div>
                <div class="min-w-[8rem] flex-1 sm:flex-none sm:w-44">
                    <x-input-label for="location_id" value="Location" class="sr-only" />
                    <select id="location_id" name="location_id" class="block w-full rounded border-gray-300 text-sm dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">All locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[8rem] flex-1 sm:flex-none sm:w-44">
                    <x-input-label for="item_type_id" value="Item type" class="sr-only" />
                    <select id="item_type_id" name="item_type_id" class="block w-full rounded border-gray-300 text-sm dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">All item types</option>
                        @foreach($itemTypes as $itemType)
                            <option value="{{ $itemType->id }}" @selected((string) ($filters['item_type_id'] ?? '') === (string) $itemType->id)>{{ $itemType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button class="text-sm">Apply</x-primary-button>
                <p class="ms-auto text-xs text-brand-charcoal dark:text-brand-silver self-center">
                    {{ $graph['asset_count'] ?? count($graph['nodes']) }} assets · {{ count($graph['edges']) }} connections
                </p>
            </div>

            <div class="relative border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate h-[calc(100vh-11.5rem)] min-h-[22rem]">
                <div class="absolute inset-x-0 top-0 z-20 pointer-events-none">
                    <div class="pointer-events-auto flex flex-wrap items-start gap-x-3 gap-y-1.5 px-2 py-1.5 bg-white/90 dark:bg-brand-slate/90 border-b border-brand-silver/80 dark:border-brand-charcoal/80 backdrop-blur-sm">
                        @foreach($connectionTypes as $type)
                            <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    name="connection_type_ids[]"
                                    value="{{ $type->id }}"
                                    class="rounded text-brand h-3.5 w-3.5"
                                    @checked(in_array((string) $type->id, array_map('strval', $filters['connection_type_ids'] ?? []), true))
                                    onchange="this.form.requestSubmit()"
                                >
                                <span class="inline-block h-2.5 w-2.5 rounded-sm shrink-0" style="background-color: {{ $type->color }}"></span>
                                <span>{{ $type->name }}</span>
                            </label>
                        @endforeach

                        <div class="ms-auto relative" @click.outside="displayOpen = false">
                            <button
                                type="button"
                                class="text-xs px-2 py-1 border border-brand-charcoal/40 dark:border-brand-silver/40 bg-white dark:bg-brand-slate"
                                @click="displayOpen = ! displayOpen"
                                :aria-expanded="displayOpen.toString()"
                            >
                                Display
                            </button>
                            <div
                                x-show="displayOpen"
                                x-cloak
                                class="absolute end-0 mt-1 w-64 max-h-[min(70vh,28rem)] overflow-y-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal shadow-lg p-3 space-y-3 text-xs z-40"
                                style="display: none;"
                            >
                                <div>
                                    <p class="font-semibold text-sm mb-1.5">On map</p>
                                    <div class="space-y-1">
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.mapName"> Asset name</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.mapItemType"> Item type</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.mapLocation"> Location</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.mapTypeName"> Type name on nodes</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.showEdgeLabels"> Connection type names on links</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.showTypeIcons"> Connection type icons on nodes</label>
                                    </div>
                                </div>
                                <div class="border-t border-brand-silver dark:border-brand-charcoal pt-2">
                                    <p class="font-semibold text-sm mb-1.5">In details popup</p>
                                    <div class="space-y-1">
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupItemType"> Item type</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupLocation"> Location</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupTypeName"> Connection type</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupProtocol"> Protocol</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupConnectionLabel"> Connection label</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupInterfaces"> Interfaces</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupNotes"> Notes</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupLink"> Open asset link</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-ref="cy" class="absolute inset-0"></div>

                <div
                    x-show="selection"
                    x-cloak
                    x-transition.opacity.duration.150ms
                    class="absolute z-30 bottom-3 right-3 left-3 sm:left-auto sm:w-80 max-h-[50%] overflow-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal shadow-lg p-3 space-y-2"
                >
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-sm">Details</h3>
                        <button
                            type="button"
                            class="text-brand-charcoal dark:text-brand-silver hover:text-brand-black dark:hover:text-white text-lg leading-none px-1"
                            @click="selection = null"
                            aria-label="Close details"
                        >&times;</button>
                    </div>

                    <template x-if="selection && selection.kind === 'node'">
                        <div class="space-y-2 text-sm">
                            <p class="font-medium" x-text="selection.name || selection.label"></p>
                            <p
                                x-show="selection.node_kind === 'bus'"
                                class="text-xs text-brand-charcoal dark:text-brand-silver"
                            >Device bus</p>
                            <p
                                x-show="selection.node_kind === 'bus' && selection.member_count != null"
                                class="text-brand-charcoal dark:text-brand-silver"
                            >
                                Members: <span x-text="selection.member_count"></span>
                            </p>
                            <p
                                x-show="display.popupTypeName && selection.type_name"
                                class="text-brand-charcoal dark:text-brand-silver"
                            >
                                Type: <span x-text="selection.type_name"></span>
                            </p>
                            <p
                                x-show="selection.node_kind !== 'bus' && (display.popupItemType || display.popupLocation)"
                                class="text-brand-charcoal dark:text-brand-silver"
                            >
                                <span x-show="display.popupItemType" x-text="selection.item_type || '—'"></span>
                                <span x-show="display.popupItemType && display.popupLocation"> · </span>
                                <span x-show="display.popupLocation" x-text="selection.location || 'No location'"></span>
                            </p>
                            <a
                                x-show="display.popupLink && selection.url"
                                :href="selection.url"
                                class="inline-block text-brand"
                            >Open Connections tab</a>
                        </div>
                    </template>

                    <template x-if="selection && selection.kind === 'edge'">
                        <div class="space-y-2 text-sm">
                            <p
                                x-show="display.popupTypeName"
                                class="font-medium"
                                x-text="selection.label"
                            ></p>
                            <p x-show="display.popupProtocol && selection.protocol" class="text-brand-charcoal dark:text-brand-silver">
                                Protocol: <span x-text="selection.protocol"></span>
                            </p>
                            <p
                                x-show="display.popupConnectionLabel && selection.connection_label"
                                class="text-brand-charcoal dark:text-brand-silver"
                                x-text="selection.connection_label"
                            ></p>
                            <template x-if="display.popupInterfaces">
                                <div class="space-y-1">
                                    <p>
                                        <a :href="selection.asset_a_url" class="text-brand" x-text="selection.asset_a_name"></a>
                                        / <span x-text="selection.interface_a"></span>
                                    </p>
                                    <p x-show="selection.is_bus">
                                        Bus: <span x-text="selection.interface_b"></span>
                                    </p>
                                    <p x-show="!selection.is_bus">
                                        <a :href="selection.asset_b_url" class="text-brand" x-text="selection.asset_b_name"></a>
                                        / <span x-text="selection.interface_b"></span>
                                    </p>
                                </div>
                            </template>
                            <p x-show="display.popupNotes && selection.notes" class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="selection.notes"></p>
                        </div>
                    </template>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Scan to event</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @unless($session)
            <form method="POST" action="{{ route('scan.start') }}" class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                @csrf
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Choose the event list to add scanned items to, and optionally an inventory list to check against.
                </p>
                <div>
                    <x-input-label for="event_list_id" value="Event list (add items to)" />
                    <select id="event_list_id" name="event_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                        <option value="" disabled selected>Select event list…</option>
                        @foreach($eventLists as $list)
                            <option value="{{ $list->id }}">{{ $list->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="inventory_list_id" value="Inventory list (search against)" />
                    <select id="inventory_list_id" name="inventory_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">None</option>
                        @foreach($inventoryLists as $list)
                            <option value="{{ $list->id }}">{{ $list->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="location_id" value="Location for next items" />
                    <select id="location_id" name="location_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">—</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Start scanning</x-primary-button>
            </form>
        @else
            <form method="POST" action="{{ route('scan.lists', $session) }}" class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                @csrf
                @method('PATCH')
                <div>
                    <x-input-label for="event_list_id" value="Event list (add items to)" />
                    <select id="event_list_id" name="event_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                        @foreach($eventLists as $list)
                            <option value="{{ $list->id }}" @selected($session->event_list_id == $list->id)>{{ $list->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="inventory_list_id" value="Inventory list (search against)" />
                    <select id="inventory_list_id" name="inventory_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">None</option>
                        @foreach($inventoryLists as $list)
                            <option value="{{ $list->id }}" @selected($session->inventory_list_id == $list->id)>{{ $list->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="location_id" value="Location for next items" />
                    <select id="location_id" name="location_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">—</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected($session->location_id == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Update lists</x-primary-button>
            </form>

            @php
                $initialRecent = $recentItems->map(fn ($item) => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'is_child_expand' => (bool) $item->is_child_expand,
                    'duplicate' => false,
                    'inventory_status' => 'unknown',
                    'warning' => null,
                    'time' => $item->created_at?->timezone(config('app.timezone'))->format('H:i:s') ?? '',
                    'asset' => [
                        'id' => $item->asset?->id,
                        'name' => $item->asset?->name,
                        'tp_barcode' => $item->asset?->tp_barcode,
                        'fmi_ast' => $item->asset?->fmi_ast,
                        'rig_tag' => $item->asset?->rig_tag,
                        'serial_number' => $item->asset?->serial_number,
                        'item_type' => $item->asset?->itemType?->name,
                    ],
                ])->values();
            @endphp

            <div
                x-data="barcodeScanner(@js(route('scan.scan', $session)), @js($initialRecent))"
                class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4"
            >
                <div class="flex gap-2">
                    <button type="button" @click="startCamera" x-show="!scanning" class="px-4 py-2 bg-brand text-white text-sm">Start camera</button>
                    <button type="button" @click="stopCamera" x-show="scanning" x-cloak class="px-4 py-2 bg-black text-white text-sm">Stop camera</button>
                </div>
                <div id="qr-reader" class="w-full max-w-sm mx-auto" x-show="scanning" x-cloak></div>

                <form @submit.prevent="submitCode" class="flex flex-col sm:flex-row gap-2">
                    <input
                        type="text"
                        x-ref="codeInput"
                        x-model="code"
                        @keydown.enter.prevent="submitCode"
                        placeholder="Scan or type barcode / ID"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        class="flex-1 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                    <input type="number" min="1" x-model="quantity" class="w-24 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" aria-label="Quantity">
                    <button type="submit" class="px-4 py-2 bg-black text-white dark:bg-brand text-sm" :disabled="busy && queue.length === 0">Add</button>
                </form>

                <p class="text-sm text-brand" x-text="message" x-show="message" x-cloak></p>
                <p class="text-sm text-red-600" x-text="error" x-show="error" x-cloak></p>

                <div
                    x-show="warning"
                    x-cloak
                    class="border border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100 text-sm px-3 py-2"
                    role="alert"
                >
                    <p class="font-medium">Not on inventory list</p>
                    <p x-text="warning"></p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-semibold">Added this session</h3>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="recent.length === 0" x-cloak>No items added yet.</p>
                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal border border-brand-silver dark:border-brand-charcoal" x-show="recent.length > 0">
                        <template x-for="entry in recent" :key="entry.key">
                            <li class="px-3 py-2 text-sm flex flex-col gap-0.5" :class="entry.inventory_status === 'not_on_inventory' ? 'bg-amber-50 dark:bg-amber-950/30' : ''">
                                <div class="flex justify-between gap-2">
                                    <span class="font-medium" x-text="entry.asset?.name || 'Unknown asset'"></span>
                                    <span class="text-xs text-brand-charcoal dark:text-brand-silver whitespace-nowrap" x-text="entry.time"></span>
                                </div>
                                <div class="text-xs text-brand-charcoal dark:text-brand-silver flex flex-wrap gap-x-3 gap-y-0.5">
                                    <span x-show="entry.asset?.tp_barcode" x-text="'TP: ' + entry.asset.tp_barcode"></span>
                                    <span x-show="entry.asset?.fmi_ast" x-text="'FMI: ' + entry.asset.fmi_ast"></span>
                                    <span x-show="entry.asset?.item_type" x-text="entry.asset.item_type"></span>
                                    <span x-show="entry.is_child_expand">Child</span>
                                    <span x-show="entry.duplicate" class="text-amber-700 dark:text-amber-300">Already on list</span>
                                    <span x-show="entry.inventory_status === 'not_on_inventory'" class="text-amber-700 dark:text-amber-300">Not on inventory</span>
                                    <span x-show="entry.inventory_status === 'matched'" class="text-brand">On inventory</span>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="flex gap-2">
                <form method="POST" action="{{ route('scan.close', $session) }}">
                    @csrf
                    <x-primary-button>Save &amp; close</x-primary-button>
                </form>
                <a href="{{ route('reports.compare') }}?scanned_list_id={{ $session->event_list_id }}&inventory_list_id={{ $session->inventory_list_id }}" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Compare report</a>
            </div>
        @endunless
    </div>
</x-app-layout>

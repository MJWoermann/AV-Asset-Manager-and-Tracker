<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Scan to event</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @unless($session)
            <form method="POST" action="{{ route('scan.start') }}" class="space-y-4 bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal p-4">
                @csrf
                <div>
                    <x-input-label for="event_list_id" value="Event list" />
                    <select id="event_list_id" name="event_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                        @foreach($eventLists as $list)
                            <option value="{{ $list->id }}">{{ $list->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="inventory_list_id" value="Compare to inventory list" />
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
            <div class="bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal p-4 text-sm space-y-1">
                <div><span class="text-brand-charcoal dark:text-brand-silver">Event:</span> {{ $session->eventList->name }}</div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">Inventory:</span> {{ $session->inventoryList?->name ?? '—' }}</div>
                <div><span class="text-brand-charcoal dark:text-brand-silver">Location:</span> {{ $session->location?->name ?? '—' }}</div>
            </div>

            <form method="POST" action="{{ route('scan.location', $session) }}" class="flex gap-2 items-end">
                @csrf
                @method('PATCH')
                <div class="flex-1">
                    <x-input-label for="location_id" value="Change location" />
                    <select id="location_id" name="location_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">—</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected($session->location_id == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Update</x-primary-button>
            </form>

            <div
                x-data="barcodeScanner(@js(route('scan.scan', $session)))"
                class="space-y-4 bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal p-4"
            >
                <div class="flex gap-2">
                    <button type="button" @click="startCamera" x-show="!scanning" class="px-4 py-2 bg-brand text-white text-sm">Start camera</button>
                    <button type="button" @click="stopCamera" x-show="scanning" class="px-4 py-2 bg-black text-white text-sm">Stop camera</button>
                </div>
                <div id="qr-reader" class="w-full max-w-sm mx-auto" x-show="scanning"></div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="text" x-model="code" placeholder="Scan or type barcode / ID" class="flex-1 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <input type="number" min="1" x-model="quantity" class="w-24 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <button type="button" @click="submitCode" class="px-4 py-2 bg-black text-white dark:bg-brand text-sm">Add</button>
                </div>
                <p class="text-sm text-brand" x-text="message" x-show="message"></p>
                <p class="text-sm text-red-600" x-text="error" x-show="error"></p>
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

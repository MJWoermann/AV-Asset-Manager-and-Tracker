<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Resolve import duplicates</h2></x-slot>
    <div
        class="max-w-5xl mx-auto px-4"
        x-data="{
            bulk: 'update',
            listDestination: '{{ old('list_destination', 'none') }}',
            includeUpdated: {{ old('include_updated_in_list', false) ? 'true' : 'false' }},
        }"
    >
        <form method="POST" action="{{ route('import.process') }}" class="space-y-4">
            @csrf

            <div class="bg-white dark:bg-brand-slate border p-4 space-y-2 text-sm">
                <p>
                    Prepared <span class="font-semibold">{{ $totalCount }}</span> rows:
                    <span class="font-semibold">{{ $newCount }}</span> new,
                    <span class="font-semibold">{{ $duplicateCount ?? count($duplicates) }}</span> matched existing assets
                    (Serial Number, FMI AST#, MAC Address, Rig Tag #, or TP Barcode).
                </p>
                @if(($duplicatesHiddenCount ?? 0) > 0)
                    <p class="text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 px-3 py-2 rounded">
                        Showing the first {{ count($duplicates) }} of {{ $duplicateCount }} matches for review.
                        The bulk action below still applies to <span class="font-semibold">all</span> matched rows
                        (including the {{ $duplicatesHiddenCount }} not listed).
                    </p>
                @endif
                <p class="text-gray-600 dark:text-brand-silver">
                    <span class="font-medium text-brand-black dark:text-white">Update</span> merges mapped fields into the existing asset.
                    <span class="font-medium text-brand-black dark:text-white">Replace</span> clears core fields then applies the import.
                    <span class="font-medium text-brand-black dark:text-white">Skip</span> leaves the existing asset unchanged.
                    <span class="font-medium text-brand-black dark:text-white">Create new (false match)</span> creates a new asset and leaves the matched asset unchanged — use when the compared fields are not unique.
                </p>
            </div>

            @if(($duplicateCount ?? count($duplicates)) > 0)
                <div class="bg-white dark:bg-brand-slate border p-4 space-y-3">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <x-input-label for="bulk_action" value="Bulk action for all duplicates" />
                            <select
                                id="bulk_action"
                                name="bulk_action"
                                x-model="bulk"
                                class="mt-1 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            >
                                <option value="update">Update all</option>
                                <option value="replace">Replace all</option>
                                <option value="skip">Skip all</option>
                                <option value="create">Create all as new (false match)</option>
                            </select>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-2 border text-xs uppercase tracking-widest"
                            @click="document.querySelectorAll('[data-dup-action]').forEach(el => el.value = bulk)"
                        >
                            Apply bulk to each row
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-2 pr-3">Incoming</th>
                                    <th class="py-2 pr-3">Existing</th>
                                    <th class="py-2 pr-3">Matched on</th>
                                    <th class="py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($duplicates as $dup)
                                    <tr class="border-b align-top">
                                        <td class="py-3 pr-3">
                                            <div class="font-medium">{{ $dup['payload']['name'] ?? '—' }}</div>
                                            <div class="text-xs text-gray-600 dark:text-brand-silver space-y-0.5 mt-1">
                                                @foreach(['serial_number','fmi_ast','mac_address','rig_tag','tp_barcode','ip_address'] as $key)
                                                    @if(!empty($dup['payload'][$key]))
                                                        <div>{{ $fieldLabels[$key] ?? $key }}: {{ $dup['payload'][$key] }}</div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <div class="font-medium">{{ $dup['existing_summary']['name'] ?? '—' }}</div>
                                            <div class="text-xs text-gray-600 dark:text-brand-silver space-y-0.5 mt-1">
                                                <div>ID: {{ $dup['existing_id'] }}</div>
                                                @foreach(['serial_number','fmi_ast','mac_address','rig_tag','tp_barcode','ip_address'] as $key)
                                                    @if(!empty($dup['existing_summary'][$key]))
                                                        <div>{{ $fieldLabels[$key] ?? $key }}: {{ $dup['existing_summary'][$key] }}</div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 pr-3">
                                            {{ collect($dup['matched_on'])->map(fn ($k) => $fieldLabels[$k] ?? $k)->implode(', ') }}
                                        </td>
                                        <td class="py-3">
                                            <select
                                                name="actions[{{ $dup['index'] }}]"
                                                data-dup-action
                                                class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                            >
                                                <option value="update" selected>Update</option>
                                                <option value="replace">Replace</option>
                                                <option value="skip">Skip</option>
                                                <option value="create">Create new (false match)</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-brand-slate border p-4 text-sm">
                    No duplicates found. All prepared rows will be created as new assets.
                </div>
            @endif

            <div class="bg-white dark:bg-brand-slate border p-4 space-y-3 text-sm">
                <h3 class="font-semibold text-base">Add imported assets to a list</h3>
                <p class="text-gray-600 dark:text-brand-silver">
                    Optionally add assets from this import to an inventory or event list. New assets are always included when a list is selected.
                </p>

                <div class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="list_destination" value="none" x-model="listDestination" class="text-brand">
                        <span>Do not add to a list</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="list_destination" value="existing" x-model="listDestination" class="text-brand">
                        <span>Add to an existing list</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="list_destination" value="new" x-model="listDestination" class="text-brand">
                        <span>Create a new list from this import</span>
                    </label>
                </div>

                <div x-show="listDestination === 'existing'" x-cloak class="space-y-2 pt-1">
                    <x-input-label for="asset_list_id" value="Existing list" />
                    <select
                        id="asset_list_id"
                        name="asset_list_id"
                        class="mt-1 w-full max-w-md rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                        <option value="">Select a list…</option>
                        @foreach($lists as $listOption)
                            <option value="{{ $listOption->id }}" @selected((string) old('asset_list_id') === (string) $listOption->id)>
                                {{ $listOption->name }} ({{ $listOption->type }})
                            </option>
                        @endforeach
                    </select>
                    @error('asset_list_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="listDestination === 'new'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 max-w-2xl">
                    <div>
                        <x-input-label for="new_list_name" value="New list name" />
                        <x-text-input
                            id="new_list_name"
                            name="new_list_name"
                            class="mt-1 block w-full"
                            :value="old('new_list_name')"
                            placeholder="e.g. Warehouse stocktake import"
                        />
                        @error('new_list_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="new_list_type" value="List type" />
                        <select
                            id="new_list_type"
                            name="new_list_type"
                            class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        >
                            <option value="inventory" @selected(old('new_list_type', 'inventory') === 'inventory')>Inventory</option>
                            <option value="event" @selected(old('new_list_type') === 'event')>Event</option>
                        </select>
                        @error('new_list_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label
                    class="inline-flex items-start gap-2 pt-1"
                    x-show="listDestination !== 'none'"
                    x-cloak
                >
                    <input
                        type="checkbox"
                        name="include_updated_in_list"
                        value="1"
                        class="mt-0.5 rounded text-brand"
                        x-model="includeUpdated"
                    >
                    <span>
                        Also include existing assets that are updated or replaced
                        <span class="block text-gray-600 dark:text-brand-silver">Skipped matches are never added.</span>
                    </span>
                </label>
                @error('list_destination')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-white dark:bg-brand-slate border p-4 space-y-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="dry_run" value="1" class="rounded text-brand" checked>
                    Dry run (no writes)
                </label>
                <div class="flex justify-end gap-3 items-center">
                    <a href="{{ route('import.create') }}" class="text-sm underline">Start over</a>
                    <x-primary-button>Process import</x-primary-button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>

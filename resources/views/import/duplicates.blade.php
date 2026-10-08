<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Resolve import duplicates</h2></x-slot>
    <div class="max-w-5xl mx-auto px-4" x-data="{ bulk: 'update' }">
        <form method="POST" action="{{ route('import.process') }}" class="space-y-4">
            @csrf

            <div class="bg-white dark:bg-black border p-4 space-y-2 text-sm">
                <p>
                    Prepared <span class="font-semibold">{{ $totalCount }}</span> rows:
                    <span class="font-semibold">{{ $newCount }}</span> new,
                    <span class="font-semibold">{{ count($duplicates) }}</span> matched existing assets
                    (Serial Number, FMI AST#, MAC Address, Rig Tag #, or TP Barcode).
                </p>
                <p class="text-gray-600 dark:text-brand-silver">
                    <span class="font-medium text-brand-black dark:text-white">Update</span> merges mapped fields into the existing asset.
                    <span class="font-medium text-brand-black dark:text-white">Replace</span> clears core fields then applies the import.
                    <span class="font-medium text-brand-black dark:text-white">Skip</span> leaves the existing asset unchanged.
                </p>
            </div>

            @if(count($duplicates))
                <div class="bg-white dark:bg-black border p-4 space-y-3">
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
                                                @foreach(['serial_number','fmi_ast','mac_address','rig_tag','tp_barcode'] as $key)
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
                                                @foreach(['serial_number','fmi_ast','mac_address','rig_tag','tp_barcode'] as $key)
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
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-black border p-4 text-sm">
                    No duplicates found. All prepared rows will be created as new assets.
                </div>
            @endif

            <div class="bg-white dark:bg-black border p-4 space-y-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="dry_run" value="1" class="rounded text-brand" checked>
                    Dry run (no writes)
                </label>
                <div>
                    <x-primary-button>Process import</x-primary-button>
                    <a href="{{ route('import.create') }}" class="ml-3 text-sm underline">Start over</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Unknown barcode scans</h2>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
        x-data="{
            searchUrl: @js(route('assets.search')),
            linkUrlTemplate: @js(route('unknown-scans.link', ['unknownScan' => '__ID__'])),
            scanId: null,
            scannedCode: '',
            hasEventList: false,
            hasLocation: false,
            identifierField: '',
            query: '',
            results: [],
            selectedId: null,
            selectedLabel: '',
            selectedAsset: null,
            searching: false,
            searchTimer: null,
            linkAction() {
                return this.linkUrlTemplate.replace('__ID__', String(this.scanId ?? ''));
            },
            openLink(id, code, hasEventList, hasLocation) {
                this.scanId = id;
                this.scannedCode = code;
                this.hasEventList = Boolean(hasEventList);
                this.hasLocation = Boolean(hasLocation);
                this.identifierField = '';
                this.query = '';
                this.results = [];
                this.selectedId = null;
                this.selectedLabel = '';
                this.selectedAsset = null;
                this.searching = false;
                this.$dispatch('open-modal', 'link-unknown-scan');
                this.$nextTick(() => this.$refs.linkSearch?.focus());
            },
            scheduleSearch() {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => this.search(), 250);
            },
            async search() {
                const term = this.query.trim();
                if (term.length < 1) {
                    this.results = [];
                    return;
                }
                this.searching = true;
                try {
                    const response = await fetch(this.searchUrl + '?q=' + encodeURIComponent(term), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const json = await response.json();
                    this.results = json.data || [];
                } finally {
                    this.searching = false;
                }
            },
            pick(asset) {
                this.selectedId = asset.id;
                this.selectedLabel = asset.label || asset.name;
                this.selectedAsset = asset;
                this.query = asset.label || asset.name;
                this.results = [];
            },
            selectedFieldCurrentValue() {
                if (!this.identifierField || !this.selectedAsset) {
                    return null;
                }
                const value = this.selectedAsset[this.identifierField];
                return value == null || value === '' ? null : String(value);
            },
        }"
    >
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Codes scanned during stocktake that were not found in the asset register. Export this report to identify and add missing assets.
        </p>

        <form method="GET" action="{{ route('reports.unknown-scans') }}" class="flex flex-col sm:flex-row flex-wrap gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="unknown-scans-q" value="Search code / note" />
                <x-scannable-search
                    id="unknown-scans-q"
                    name="q"
                    :value="$q"
                    placeholder="Barcode or note…"
                    class="mt-1"
                    scan-aria-label="Scan barcode into unknown-scan search"
                />
            </div>
            <div class="min-w-[10rem]">
                <x-input-label for="status" value="Status" />
                <x-searchable-select
                    name="status"
                    id="status"
                    :options="[
                        ['value' => 'open', 'label' => 'Open'],
                        ['value' => 'resolved', 'label' => 'Resolved'],
                        ['value' => 'all', 'label' => 'All'],
                    ]"
                    :selected="$status"
                    auto-submit
                    aria-label="Filter by status"
                />
            </div>
            <div class="flex items-end gap-2 flex-wrap">
                <x-primary-button>Search</x-primary-button>
                <x-clear-filters
                    :href="route('reports.unknown-scans')"
                    :active="filled($q) || $status !== 'open'"
                />
                <a href="{{ route('reports.unknown-scans.export', ['format' => 'xlsx', 'status' => $status, 'q' => $q]) }}" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Export XLSX</a>
                <a href="{{ route('reports.unknown-scans.export', ['format' => 'csv', 'status' => $status, 'q' => $q]) }}" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Export CSV</a>
            </div>
        </form>

        @can('reports:purge')
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Remove duplicate codes (keep the latest scan of each) or clear the entire unknown scans table.
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <form
                        method="POST"
                        action="{{ route('reports.unknown-scans.deduplicate') }}"
                        onsubmit="return confirm('Remove duplicate unknown scans, keeping only the latest scan of each code?')"
                    >
                        @csrf
                        <x-secondary-button type="submit">Remove duplicates</x-secondary-button>
                    </form>
                    <form
                        method="POST"
                        action="{{ route('reports.unknown-scans.destroy-all') }}"
                        onsubmit="return confirm('Clear the entire unknown scans table? This cannot be undone.')"
                    >
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Clear all</x-danger-button>
                    </form>
                </div>
            </div>
        @endcan

        <div data-debug-unknown-scans class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver/40 dark:bg-brand-charcoal/40 text-left">
                    <tr>
                        <th class="px-3 py-2 font-semibold">Scanned at</th>
                        <th class="px-3 py-2 font-semibold">Code</th>
                        <th class="px-3 py-2 font-semibold">User</th>
                        <th class="px-3 py-2 font-semibold">Location</th>
                        <th class="px-3 py-2 font-semibold">Event list</th>
                        <th class="px-3 py-2 font-semibold">Note</th>
                        <th class="px-3 py-2 font-semibold">Status</th>
                        <th class="px-3 py-2 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-silver dark:divide-brand-charcoal">
                    @forelse($scans as $scan)
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $scan->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-3 py-2 font-medium">
                                <div class="max-w-xs break-all">{{ $scan->scanned_code }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $scan->user?->name }}</td>
                            <td class="px-3 py-2">{{ $scan->location?->breadcrumb() ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $scan->eventList?->name ?? '—' }}</td>
                            <td class="px-3 py-2 max-w-xs truncate" title="{{ $scan->note }}">{{ $scan->note ?: '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if($scan->isResolved())
                                    <a href="{{ route('assets.show', $scan->resolvedAsset) }}" class="text-brand underline">
                                        {{ $scan->resolvedAsset?->name ?? 'Resolved' }}
                                    </a>
                                @else
                                    <span class="text-amber-700 dark:text-amber-300">Open</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                @unless($scan->isResolved())
                                    <div class="inline-flex items-center gap-3">
                                        <a
                                            href="{{ route('assets.create', [
                                                'tp_barcode' => $scan->scanned_code,
                                                'location_id' => $scan->location_id,
                                                'unknown_scan_id' => $scan->id,
                                                'status' => $scan->asset_status?->value,
                                                'return_to' => request()->getRequestUri(),
                                            ]) }}"
                                            class="text-brand underline text-xs"
                                        >Add asset</a>
                                        <button
                                            type="button"
                                            class="text-brand underline text-xs"
                                            @click="openLink({{ $scan->id }}, @js($scan->scanned_code), {{ $scan->event_list_id ? 'true' : 'false' }}, {{ $scan->location_id ? 'true' : 'false' }})"
                                        >Link existing</button>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">
                                No unknown scans match this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $scans->links() }}
        </div>

        {{-- #region agent log --}}
        <script>
            requestAnimationFrame(() => {
                const wrap = document.querySelector('[data-debug-unknown-scans]');
                const table = wrap?.querySelector('table');
                const rows = [...(wrap?.querySelectorAll('tbody tr') ?? [])];
                const codeCell = rows.reduce((best, row) => {
                    const cell = row.children[1];
                    if (!cell) return best;
                    return (cell.textContent?.trim().length ?? 0) > (best?.textContent?.trim().length ?? 0) ? cell : best;
                }, null);
                const row = codeCell?.parentElement;
                const actionCell = row?.children[7] ?? null;
                const noteCell = row?.children[5] ?? null;
                const locationCell = row?.children[3] ?? null;
                const actionRect = actionCell?.getBoundingClientRect();
                const codeStyle = codeCell ? getComputedStyle(codeCell) : null;
                const codeInner = codeCell?.querySelector('div') ?? null;
                const innerStyle = codeInner ? getComputedStyle(codeInner) : null;
                const payload = {
                    sessionId: '8b670d',
                    runId: 'post-fix',
                    hypothesisId: 'A-E',
                    location: 'unknown-scans.blade.php:layout',
                    message: 'unknown scan table overflow',
                    timestamp: Date.now(),
                    data: {
                        viewport: window.innerWidth,
                        wrapClient: wrap?.clientWidth ?? null,
                        wrapScroll: wrap?.scrollWidth ?? null,
                        tableScroll: table?.scrollWidth ?? null,
                        codeLen: codeCell?.textContent?.trim().length ?? 0,
                        codeHasSpace: /\s/.test(codeCell?.textContent ?? ''),
                        codeClient: codeCell?.clientWidth ?? null,
                        codeScroll: codeCell?.scrollWidth ?? null,
                        codeWhiteSpace: codeStyle?.whiteSpace ?? null,
                        codeWordBreak: codeStyle?.wordBreak ?? null,
                        codeOverflowWrap: codeStyle?.overflowWrap ?? null,
                        noteClient: noteCell?.clientWidth ?? null,
                        noteScroll: noteCell?.scrollWidth ?? null,
                        locationClient: locationCell?.clientWidth ?? null,
                        locationScroll: locationCell?.scrollWidth ?? null,
                        actionRight: actionRect ? Math.round(actionRect.right) : null,
                        actionOffscreen: actionRect ? actionRect.right > window.innerWidth + 1 : null,
                        wrapScrolls: wrap ? wrap.scrollWidth > wrap.clientWidth + 1 : null,
                    },
                };
                fetch('http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '8b670d' },
                    body: JSON.stringify(payload),
                }).catch(() => {});
            });
        </script>
        {{-- #endregion --}}

        <x-modal name="link-unknown-scan" :show="$errors->has('asset_id') || $errors->has('identifier_field')" focusable maxWidth="md">
            <form method="POST" :action="linkAction()" class="p-6 space-y-4" @submit="if (!selectedId) { $event.preventDefault(); }">
                @csrf

                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Link to existing asset
                </h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Resolve scanned code
                    <span class="font-medium text-gray-900 dark:text-white" x-text="scannedCode"></span>
                    by attaching it to an asset that already exists.
                </p>

                <div class="relative">
                    <x-input-label for="link-asset-search" value="Search assets" />
                    <input type="hidden" name="asset_id" :value="selectedId ?? ''" required>
                    <x-text-input
                        id="link-asset-search"
                        x-ref="linkSearch"
                        type="search"
                        class="mt-1 block w-full"
                        x-model="query"
                        @input="scheduleSearch()"
                        placeholder="Name, barcode, FMI, serial…"
                        autocomplete="off"
                    />
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-show="searching" x-cloak>Searching…</p>
                    <p class="mt-1 text-xs text-brand" x-show="selectedId" x-cloak>
                        Selected: <span x-text="selectedLabel"></span>
                    </p>
                    <ul
                        class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto border border-brand-silver bg-white shadow-sm dark:border-brand-charcoal dark:bg-brand-slate"
                        x-show="results.length > 0"
                        x-cloak
                    >
                        <template x-for="asset in results" :key="asset.id">
                            <li>
                                <button
                                    type="button"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal"
                                    @click="pick(asset)"
                                >
                                    <span x-text="asset.label || asset.name"></span>
                                    <span
                                        class="ml-1 text-xs text-brand-charcoal dark:text-brand-silver"
                                        x-show="asset.item_type"
                                        x-text="asset.item_type"
                                    ></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <x-input-error :messages="$errors->get('asset_id')" class="mt-2" />
                </div>

                <label class="flex items-start gap-2 text-sm" x-show="hasEventList" x-cloak>
                    <input type="checkbox" name="add_to_event_list" value="1" class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand dark:border-brand-charcoal dark:bg-black">
                    <span>Add asset to the event list from this scan (skip if already on the list)</span>
                </label>

                <div>
                    <x-input-label for="link-identifier-field" value="Write scanned code to" />
                    <select
                        id="link-identifier-field"
                        name="identifier_field"
                        x-model="identifierField"
                        class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-black dark:text-white focus:border-brand focus:ring-brand rounded-md shadow-sm text-sm"
                    >
                        <option value="">Don’t update an identifier</option>
                        @foreach(\App\Support\ScannableIdentifierFields::linkTargets() as $field)
                            <option value="{{ $field }}">{{ \App\Support\ScannableIdentifierFields::label($field) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                        Replaces the chosen field on the linked asset. Skipped if another asset already uses this code.
                    </p>
                    <p
                        class="mt-1 text-xs text-amber-700 dark:text-amber-300"
                        x-show="identifierField && selectedFieldCurrentValue()"
                        x-cloak
                    >
                        Current value will be overwritten:
                        <span class="font-medium" x-text="selectedFieldCurrentValue()"></span>
                    </p>
                    <x-input-error :messages="$errors->get('identifier_field')" class="mt-2" />
                </div>

                <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="hasLocation" x-cloak>
                    This scan has a location — the linked asset’s location will be updated (child assets keep their parent’s location).
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">
                        Cancel
                    </x-secondary-button>
                    <x-primary-button x-bind:disabled="!selectedId">
                        Link asset
                    </x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>

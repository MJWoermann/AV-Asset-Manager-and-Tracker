@php
    $comparisonSections = null;
    if ($result) {
        $mapRows = static function ($rows) {
            return $rows->map(fn ($row) => [
                'name' => (string) $row->name,
                'parent_id' => $row->parent_id,
                'code' => (string) ($row->tp_barcode ?? $row->fmi_ast ?? $row->rig_tag ?? $row->serial_number ?? ''),
                'haystack' => trim(implode(' ', array_filter([
                    $row->name,
                    $row->tp_barcode ?? null,
                    $row->fmi_ast ?? null,
                    $row->rig_tag ?? null,
                    $row->serial_number ?? null,
                    $row->mac_address ?? null,
                ]))),
            ])->values()->all();
        };

        $comparisonSections = [
            ['key' => 'matched', 'title' => 'Matched', 'rows' => $mapRows($result['matched'])],
            ['key' => 'inventory_only', 'title' => 'Inventory only', 'rows' => $mapRows($result['inventory_only'])],
            ['key' => 'scanned_only', 'title' => 'Scanned only (not on inventory)', 'rows' => $mapRows($result['scanned_only'])],
        ];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Compare lists</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <form method="POST" action="{{ route('reports.compare.run') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white dark:bg-brand-slate border p-4">
            @csrf
            <div>
                <x-input-label for="scanned_list_id" value="Scanned / event list" />
                <x-searchable-select
                    name="scanned_list_id"
                    id="scanned_list_id"
                    :options="$lists->map(fn ($list) => ['value' => $list->id, 'label' => $list->compareOptionLabel()])->values()"
                    :selected="old('scanned_list_id', request('scanned_list_id'))"
                    placeholder="Select scanned / event list…"
                    required
                    aria-label="Scanned / event list"
                />
            </div>
            <div>
                <x-input-label for="inventory_list_id" value="Inventory / source list" />
                <x-searchable-select
                    name="inventory_list_id"
                    id="inventory_list_id"
                    :options="$lists->map(fn ($list) => ['value' => $list->id, 'label' => $list->compareOptionLabel()])->values()"
                    :selected="old('inventory_list_id', request('inventory_list_id'))"
                    placeholder="Select inventory / source list…"
                    required
                    aria-label="Inventory / source list"
                />
            </div>
            <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3">
                <x-primary-button>Run comparison</x-primary-button>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-brand-charcoal dark:text-brand-silver">Export</span>
                    <button name="export" value="xlsx" class="px-4 py-2 border text-sm">XLSX</button>
                    <button name="export" value="csv" class="px-4 py-2 border text-sm">CSV</button>
                    <button name="export" value="pdf" class="px-4 py-2 border text-sm">PDF</button>
                </div>
            </div>
        </form>

        @if($comparisonSections)
            <div
                x-data="comparisonSearch(@js($comparisonSections))"
                @field-barcode-scanned="query = $event.detail.value"
                class="space-y-6"
            >
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver sm:pt-2">{{ $source->name }} vs {{ $target->name }}</p>
                    <x-scannable-search
                        id="comparison-filter-q"
                        :submit-on-scan="false"
                        placeholder="Filter results by name, barcode, FMI, SN, MAC…"
                        class="w-full sm:max-w-md"
                        scan-aria-label="Scan barcode into comparison filter"
                        x-model="query"
                    />
                </div>

                <template x-for="section in sections" :key="section.key">
                    <section>
                        <h3 class="font-semibold mb-2">
                            <span x-text="section.title"></span>
                            (<span x-text="visibleCount(section)"></span>)
                        </h3>
                        <ul class="bg-white dark:bg-brand-slate border divide-y text-sm max-h-80 overflow-y-auto">
                            <template x-for="(row, index) in section.rows" :key="section.key + '-' + index">
                                <li
                                    class="px-3 py-2"
                                    :class="row.parent_id ? 'pl-8' : ''"
                                    x-show="rowMatches(row.haystack)"
                                >
                                    <span x-html="highlight(row.name)"></span>
                                    <span
                                        class="text-brand-charcoal dark:text-brand-silver font-mono"
                                        x-show="row.code"
                                        x-html="row.code ? (' ' + highlight(row.code)) : ''"
                                    ></span>
                                </li>
                            </template>
                            <li
                                class="px-3 py-4 text-brand-charcoal dark:text-brand-silver"
                                x-show="visibleCount(section) === 0"
                            >None</li>
                        </ul>
                    </section>
                </template>
            </div>
        @endif
    </div>
</x-app-layout>

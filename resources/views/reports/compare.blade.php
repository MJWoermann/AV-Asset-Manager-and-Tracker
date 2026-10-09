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
        <form method="POST" action="{{ route('reports.compare.run') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white dark:bg-black border p-4">
            @csrf
            <div>
                <x-input-label for="scanned_list_id" value="Scanned / event list" />
                <select id="scanned_list_id" name="scanned_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    @foreach($lists as $list)
                        <option value="{{ $list->id }}" @selected(old('scanned_list_id', request('scanned_list_id')) == $list->id)>{{ $list->name }} ({{ $list->type }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="inventory_list_id" value="Inventory / source list" />
                <select id="inventory_list_id" name="inventory_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    @foreach($lists as $list)
                        <option value="{{ $list->id }}" @selected(old('inventory_list_id', request('inventory_list_id')) == $list->id)>{{ $list->name }} ({{ $list->type }})</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 flex flex-wrap gap-2">
                <x-primary-button>Run comparison</x-primary-button>
                <button name="export" value="xlsx" class="px-4 py-2 border text-sm">Export XLSX</button>
                <button name="export" value="csv" class="px-4 py-2 border text-sm">Export CSV</button>
                <button name="export" value="pdf" class="px-4 py-2 border text-sm">Export PDF</button>
            </div>
        </form>

        @if($comparisonSections)
            <div x-data="comparisonSearch(@js($comparisonSections))" class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $source->name }} vs {{ $target->name }}</p>
                    <input
                        type="search"
                        x-model="query"
                        placeholder="Filter results by name, barcode, FMI, SN…"
                        class="w-full sm:max-w-md rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                </div>

                <template x-for="section in sections" :key="section.key">
                    <section>
                        <h3 class="font-semibold mb-2">
                            <span x-text="section.title"></span>
                            (<span x-text="visibleCount(section)"></span>)
                        </h3>
                        <ul class="bg-white dark:bg-black border divide-y text-sm max-h-80 overflow-y-auto">
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
                                class="px-3 py-4 text-brand-charcoal"
                                x-show="visibleCount(section) === 0"
                            >None</li>
                        </ul>
                    </section>
                </template>
            </div>
        @endif
    </div>
</x-app-layout>

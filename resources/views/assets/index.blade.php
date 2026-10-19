<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Assets</h2>
            <div class="flex gap-2 items-center">
                <x-asset-column-picker :available-columns="$availableColumns" :selected-columns="$selectedColumns" />
                @role('admin|inventory_manager')
                    <a href="{{ route('import.create') }}" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Import</a>
                    <a href="{{ route('export.assets', ['format' => 'xlsx']) }}" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Export</a>
                @endrole
                <a href="{{ route('assets.create') }}" class="text-sm px-3 py-2 bg-brand text-white">Add asset</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="bulkSelection(@js([
        'idAttribute' => 'data-bulk-asset-id',
        'fields' => [
            'update_status' => false,
            'update_location' => false,
            'clear_location' => false,
            'update_parent' => false,
            'clear_parent' => false,
            'update_test_tag_expiry' => false,
            'clear_test_tag_expiry' => false,
            'update_warranty_expiry' => false,
            'clear_warranty_expiry' => false,
            'update_notes' => false,
            'clear_notes' => false,
        ],
    ]))">
        @if (session('status'))
            <div class="mb-4 text-sm text-brand">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form id="asset-filters" method="GET" class="mb-4 flex flex-col sm:flex-row gap-2 sm:items-start">
            @if (request()->filled(\App\Support\PageSize::PARAM))
                <input type="hidden" name="{{ \App\Support\PageSize::PARAM }}" value="{{ request(\App\Support\PageSize::PARAM) }}">
            @endif
            <x-scannable-search
                id="asset-search-q"
                name="q"
                :value="$q"
                placeholder="Search name, location, FMI, barcode, SN…"
                class="flex-1"
                aria-label="Search assets"
                scan-aria-label="Scan barcode into asset search"
            />
            <button type="submit" class="px-4 py-2 bg-black text-white dark:bg-brand shrink-0">Search</button>
            <x-clear-filters
                :href="route('assets.index')"
                :active="\App\Support\AssetBrowseFilters::hasActiveFilters(request(), $selectedColumns)"
            />
        </form>

        <x-bulk-asset-actions
            :statuses="$statuses"
            :locations="$locations"
            :parents="$parents"
            :lists="$lists"
        />

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2 w-10" aria-hidden="true"></th>
                        @foreach($selectedColumns as $column)
                            <th class="px-3 py-2">{{ \App\Support\AssetTableColumns::label($column) }}</th>
                        @endforeach
                    </tr>
                    <x-asset-column-filters
                        :selected-columns="$selectedColumns"
                        :statuses="$statuses"
                        :locations="$locations"
                        :item-types="$itemTypes"
                        :manufacturers="$manufacturers"
                        :manufacturer-models="$manufacturerModels"
                    />
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                        @php
                            $bulkAssetLabel = $asset->name;
                            $bulkLabelParts = array_filter([$asset->tp_barcode, $asset->fmi_ast, $asset->rig_tag]);
                            if ($bulkLabelParts !== []) {
                                $bulkAssetLabel .= ' ('.implode(' · ', $bulkLabelParts).')';
                            }
                        @endphp
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2">
                                <input
                                    type="checkbox"
                                    class="rounded border-brand-charcoal/40"
                                    data-bulk-asset-id="{{ $asset->id }}"
                                    data-bulk-asset-label="{{ $bulkAssetLabel }}"
                                    :checked="!!selected[{{ $asset->id }}]"
                                    @change="setSelected({{ $asset->id }}, $event.target.checked)"
                                    aria-label="Select {{ $asset->name }}"
                                >
                            </td>
                            @foreach($selectedColumns as $column)
                                <x-asset-table-cell
                                    :asset="$asset"
                                    :column="$column"
                                    :term="$q"
                                    :selected-columns="$selectedColumns"
                                />
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($selectedColumns) + 1 }}" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No assets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginated-results :paginator="$assets" />
    </div>
</x-app-layout>

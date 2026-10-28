<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">{{ $list->name }}</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver capitalize">{{ $list->type }} list</p>
            </div>
            <div class="flex gap-2 items-center">
                <x-asset-column-picker :available-columns="$availableColumns" :selected-columns="$selectedColumns" />
                @can('labels:read')
                    @feature('shelf_tag_generator')
                        <a href="{{ route('labels.index', ['asset_list_id' => $list->id]) }}" class="px-3 py-2 border text-sm">Labels</a>
                    @endfeature
                    <a href="{{ route('export.list', [$list, 'format' => 'xlsx']) }}" class="px-3 py-2 border text-sm">Export</a>
                    <a href="{{ route('lists.edit', $list) }}" class="px-3 py-2 border text-sm">Edit</a>
                    <form
                        method="POST"
                        action="{{ route('lists.destroy', $list) }}"
                        class="inline"
                        onsubmit="return confirm('Delete this list? List membership is removed (assets are kept). Event lists also delete related scan sessions. Unknown-scan history may lose its list linkage.')"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 border text-sm text-red-700">Delete</button>
                    </form>
                @endcan
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
            'update_department' => false,
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
                id="list-search-q"
                name="q"
                :value="$q"
                placeholder="Search FMI, barcode, SN, IP, MAC…"
                class="flex-1"
                aria-label="Search list assets"
                scan-aria-label="Scan barcode into list search"
            />
            <button type="submit" class="px-4 py-2 bg-black text-white dark:bg-brand shrink-0">Search</button>
            <x-clear-filters
                :href="route('lists.show', $list)"
                :active="\App\Support\AssetBrowseFilters::hasActiveFilters(request(), $selectedColumns)"
            />
        </form>

        <x-bulk-asset-actions
            :statuses="$statuses"
            :locations="$locations"
            :parents="$parents"
            :lists="$lists"
            :departments="$departments"
            :current-list="$list"
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
                    @forelse($items as $item)
                        @php
                            $indent = $item->is_child_expand || $item->asset?->parent_id;
                            $bulkAssetLabel = $item->asset?->name;
                            if ($item->asset) {
                                $bulkLabelParts = array_filter([$item->asset->tp_barcode, $item->asset->fmi_ast, $item->asset->rig_tag]);
                                if ($bulkLabelParts !== []) {
                                    $bulkAssetLabel .= ' ('.implode(' · ', $bulkLabelParts).')';
                                }
                            }
                            $rowColour = $item->asset
                                ? (($departmentColours ?? [])[$item->asset->department_id] ?? null)
                                : null;
                        @endphp
                        <tr
                            class="border-t border-brand-silver dark:border-brand-charcoal"
                            @if($rowColour)
                                style="background-color: color-mix(in srgb, {{ $rowColour }} 14%, transparent)"
                            @endif
                        >
                            <td class="px-3 py-2 {{ $indent ? 'pl-8' : '' }}">
                                @if($item->asset)
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        data-bulk-asset-id="{{ $item->asset->id }}"
                                        data-bulk-asset-label="{{ $bulkAssetLabel }}"
                                        :checked="!!selected[{{ $item->asset->id }}]"
                                        @change="setSelected({{ $item->asset->id }}, $event.target.checked)"
                                        aria-label="Select {{ $item->asset->name }}"
                                    >
                                @endif
                            </td>
                            @foreach($selectedColumns as $column)
                                @if($item->asset)
                                    <x-asset-table-cell
                                        :asset="$item->asset"
                                        :column="$column"
                                        :item="$item"
                                        :indent="$indent"
                                        :term="$q"
                                        :selected-columns="$selectedColumns"
                                    />
                                @else
                                    <td class="px-3 py-2">—</td>
                                @endif
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($selectedColumns) + 1 }}" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No items yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginated-results :paginator="$items" />
    </div>
</x-app-layout>

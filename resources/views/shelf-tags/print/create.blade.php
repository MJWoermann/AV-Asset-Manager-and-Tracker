<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Print shelf tags</h2>
            <a href="{{ route('shelf-tags.layouts.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Manage layouts</a>
        </div>
    </x-slot>

    @php
        $assetRows = $listAssets->map(fn ($asset) => [
            'id' => $asset->id,
            'name' => $asset->name,
            'tp_barcode' => $asset->tp_barcode,
            'fmi_ast' => $asset->fmi_ast,
            'status' => $asset->status instanceof \BackedEnum ? $asset->status->value : (string) $asset->status,
            'item_type' => $asset->itemType?->name,
        ])->values()->all();
    @endphp

    <div
        class="max-w-5xl mx-auto px-4"
        x-data="shelfTagPrintSetup(@js($assetRows), @js($preselectedIds), @js($maxBatch))"
    >
        @if ($errors->any())
            <div class="mb-4 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($layouts->isEmpty())
            <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
                Create a <a href="{{ route('shelf-tags.layouts.create') }}" class="text-brand underline">shelf tag layout</a> before printing.
            </p>
        @endif

        <form
            method="GET"
            action="{{ route('shelf-tags.print.create') }}"
            class="mb-4 space-y-4 bg-white dark:bg-brand-slate border p-4"
            id="shelf-tag-print-filters"
        >
            @if($selectedLayoutId)
                <input type="hidden" name="shelf_tag_layout_id" value="{{ $selectedLayoutId }}">
            @endif

            <div>
                <x-input-label for="asset_list_id_filter" value="List" />
                <select
                    id="asset_list_id_filter"
                    name="asset_list_id"
                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="">Select list…</option>
                    @foreach($lists as $list)
                        <option value="{{ $list->id }}" @selected($selectedListId == $list->id)>
                            {{ $list->name }} ({{ $list->type }})
                        </option>
                    @endforeach
                </select>
            </div>

            @if($selectedListId)
                <div class="flex flex-col sm:flex-row gap-2">
                    <input
                        type="search"
                        name="q"
                        value="{{ $q }}"
                        placeholder="Search FMI, barcode, SN, IP, MAC…"
                        class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                    <button type="submit" class="px-4 py-2 bg-black text-white dark:bg-brand text-sm">Filter</button>
                    @if($q !== '')
                        <a
                            href="{{ route('shelf-tags.print.create', array_filter(['asset_list_id' => $selectedListId, 'shelf_tag_layout_id' => $selectedLayoutId])) }}"
                            class="px-4 py-2 border text-sm inline-flex items-center justify-center"
                        >Clear</a>
                    @endif
                </div>
            @endif
        </form>

        <form method="POST" action="{{ route('shelf-tags.print.preview') }}" class="space-y-4 bg-white dark:bg-brand-slate border p-4" @submit="prepareSubmit($event)">
            @csrf
            <input type="hidden" name="asset_list_id" value="{{ $selectedListId }}">
            <input type="hidden" name="mode" value="selected">

            <div>
                <x-input-label for="shelf_tag_layout_id" value="Layout" />
                <select id="shelf_tag_layout_id" name="shelf_tag_layout_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    <option value="">Select layout…</option>
                    @foreach($layouts as $layout)
                        <option value="{{ $layout->id }}" @selected($selectedLayoutId == $layout->id)>
                            {{ $layout->name }} ({{ $layout->width_mm }}×{{ $layout->height_mm }} mm)
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('shelf_tag_layout_id')" class="mt-1" />
            </div>

            <template x-for="id in selectedIds" :key="'sel-'+id">
                <input type="hidden" name="asset_ids[]" :value="id">
            </template>

            @if(! $selectedListId)
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Choose a list above to load assets and select which labels to print.
                </p>
            @elseif($listAssets->isEmpty())
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    @if($q !== '')
                        No assets on this list match your search.
                    @else
                        This list has no assets to print.
                    @endif
                </p>
            @else
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-medium">Assets on list</h3>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                            Showing {{ $listAssets->count() }}
                            @if($listAssetTruncated)
                                of {{ $listAssetTotal }} matching
                            @else
                                of {{ $listAssetTotal }}
                            @endif
                            · max {{ $maxBatch }} labels per batch
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <button type="button" class="px-3 py-1.5 border" @click="selectVisible()">Select visible</button>
                        <button type="button" class="px-3 py-1.5 border" @click="clearVisible()">Clear visible</button>
                        <button type="button" class="px-3 py-1.5 border" @click="clearSelection()">Clear all</button>
                    </div>
                </div>

                @if($listAssetTruncated)
                    <p class="text-xs text-amber-800 dark:text-amber-200">
                        Only the first {{ \App\Http\Controllers\ShelfTagPrintController::SELECTION_DISPLAY_CAP }} matching assets are listed. Refine search to find others.
                    </p>
                @elseif($listAssetTotal > $maxBatch)
                    <p class="text-xs text-amber-800 dark:text-amber-200">
                        This list has more than {{ $maxBatch }} assets. Select at most {{ $maxBatch }} to print.
                    </p>
                @endif

                <div>
                    <input
                        type="search"
                        x-model="filter"
                        placeholder="Filter visible rows…"
                        class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        aria-label="Filter visible assets"
                    >
                </div>

                <p class="text-sm">
                    <span class="font-semibold" x-text="selectedCount"></span>
                    selected
                    <span class="text-brand-charcoal dark:text-brand-silver" x-show="overLimit" x-cloak>
                        · too many (max <span x-text="maxBatch"></span>)
                    </span>
                </p>
                <x-input-error :messages="$errors->get('asset_ids')" class="mt-1" />

                <div class="overflow-x-auto border border-brand-silver dark:border-brand-charcoal max-h-[28rem]">
                    <table class="min-w-full text-sm">
                        <thead class="bg-brand-silver dark:bg-brand-charcoal text-left sticky top-0">
                            <tr>
                                <th class="px-3 py-2 w-10">
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        :checked="allVisibleSelected"
                                        @click.prevent="toggleVisible(!allVisibleSelected)"
                                        aria-label="Select all visible assets"
                                    >
                                </th>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Barcode</th>
                                <th class="px-3 py-2">FMI</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="asset in visibleAssets" :key="asset.id">
                                <tr class="border-t border-brand-silver/60 dark:border-brand-charcoal">
                                    <td class="px-3 py-2">
                                        <input
                                            type="checkbox"
                                            class="rounded border-brand-charcoal/40"
                                            :checked="!!selected[asset.id]"
                                            @change="setSelected(asset.id, $event.target.checked)"
                                            :aria-label="'Select ' + asset.name"
                                        >
                                    </td>
                                    <td class="px-3 py-2" x-text="asset.name"></td>
                                    <td class="px-3 py-2 font-mono text-xs" x-text="asset.tp_barcode || '—'"></td>
                                    <td class="px-3 py-2 font-mono text-xs" x-text="asset.fmi_ast || '—'"></td>
                                    <td class="px-3 py-2" x-text="asset.item_type || '—'"></td>
                                    <td class="px-3 py-2" x-text="asset.status || '—'"></td>
                                </tr>
                            </template>
                            <tr x-show="visibleAssets.length === 0" x-cloak>
                                <td colspan="6" class="px-3 py-4 text-brand-charcoal dark:text-brand-silver">
                                    No visible rows match this filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Server-rendered fallback for tests / no-JS: names remain in page source via @js above --}}
                <noscript>
                    <ul class="mt-3 text-sm space-y-1">
                        @foreach($listAssets as $asset)
                            <li>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}" @checked(in_array($asset->id, $preselectedIds, true))>
                                    {{ $asset->name }}
                                    @if($asset->tp_barcode)
                                        <span class="font-mono text-xs">({{ $asset->tp_barcode }})</span>
                                    @endif
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </noscript>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                <x-primary-button
                    x-bind:disabled="!canSubmit"
                    x-bind:class="!canSubmit ? 'opacity-50 cursor-not-allowed' : ''"
                >Preview labels</x-primary-button>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Browser print is primary; PDF is available from the preview screen.
                </p>
            </div>
            <x-input-error :messages="$errors->get('asset_list_id')" class="mt-1" />
        </form>
    </div>
</x-app-layout>

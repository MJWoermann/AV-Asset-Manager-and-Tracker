<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">{{ $list->name }}</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver capitalize">{{ $list->type }} list</p>
            </div>
            <div class="flex gap-2 items-center">
                <x-asset-column-picker :available-columns="$availableColumns" :selected-columns="$selectedColumns" />
                @role('admin|inventory_manager')
                    <a href="{{ route('export.list', [$list, 'format' => 'xlsx']) }}" class="px-3 py-2 border text-sm">Export</a>
                    <a href="{{ route('lists.edit', $list) }}" class="px-3 py-2 border text-sm">Edit</a>
                @endrole
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="bulkAssets()">
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

        <x-bulk-asset-actions
            :statuses="$statuses"
            :locations="$locations"
            :parents="$parents"
            :lists="$lists"
            :current-list="$list"
        />

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2 w-10">
                            <input
                                type="checkbox"
                                class="rounded border-brand-charcoal/40"
                                :checked="allPageSelected"
                                @click.prevent="toggleAll(!allPageSelected)"
                                aria-label="Select all assets on this page"
                            >
                        </th>
                        @foreach($selectedColumns as $column)
                            <th class="px-3 py-2">{{ \App\Support\AssetTableColumns::label($column) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $indent = $item->is_child_expand || $item->asset?->parent_id;
                        @endphp
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2 {{ $indent ? 'pl-8' : '' }}">
                                @if($item->asset)
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        data-bulk-asset-id="{{ $item->asset->id }}"
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
        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>

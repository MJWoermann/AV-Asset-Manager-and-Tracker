<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Assets</h2>
            <div class="flex gap-2 items-center">
                <x-asset-column-picker :available-columns="$availableColumns" :selected-columns="$selectedColumns" />
                @role('admin|inventory_manager')
                    <a href="{{ route('export.assets', ['format' => 'xlsx']) }}" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Export</a>
                @endrole
                <a href="{{ route('assets.create') }}" class="text-sm px-3 py-2 bg-brand text-white">Add asset</a>
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

        <form method="GET" class="mb-4 flex flex-col sm:flex-row gap-2">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search FMI, barcode, SN, IP, MAC…" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white">
            <select name="status" class="rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white">
                <option value="">All statuses</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="location_id" class="rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white">
                <option value="">All locations</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected($locationId === $location->id)>{{ $location->breadcrumb() }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-black text-white dark:bg-brand">Search</button>
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
                        <th class="px-3 py-2 w-10">
                            <input
                                type="checkbox"
                                class="rounded border-brand-charcoal/40"
                                :checked="allPageSelected"
                                @change="toggleAll($event.target.checked)"
                                aria-label="Select all assets on this page"
                            >
                        </th>
                        @foreach($selectedColumns as $column)
                            <th class="px-3 py-2">{{ \App\Support\AssetTableColumns::label($column) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2">
                                <input
                                    type="checkbox"
                                    class="rounded border-brand-charcoal/40"
                                    data-bulk-asset-id="{{ $asset->id }}"
                                    :checked="!!selected[{{ $asset->id }}]"
                                    @change="selected[{{ $asset->id }}] = $event.target.checked"
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
        <div class="mt-4">{{ $assets->links() }}</div>
    </div>
</x-app-layout>

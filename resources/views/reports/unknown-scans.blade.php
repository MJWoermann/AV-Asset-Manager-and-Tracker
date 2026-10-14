<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Unknown barcode scans</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Codes scanned during stocktake that were not found in the asset register. Export this report to identify and add missing assets.
        </p>

        <form method="GET" action="{{ route('reports.unknown-scans') }}" class="flex flex-col sm:flex-row flex-wrap gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="q" value="Search code / note" />
                <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$q" placeholder="Barcode or note…" />
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="open" @selected($status === 'open')>Open</option>
                    <option value="resolved" @selected($status === 'resolved')>Resolved</option>
                    <option value="all" @selected($status === 'all')>All</option>
                </select>
            </div>
            <div class="flex items-end gap-2 flex-wrap">
                <x-primary-button>Filter</x-primary-button>
                <a href="{{ route('reports.unknown-scans.export', ['format' => 'xlsx', 'status' => $status, 'q' => $q]) }}" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Export XLSX</a>
                <a href="{{ route('reports.unknown-scans.export', ['format' => 'csv', 'status' => $status, 'q' => $q]) }}" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Export CSV</a>
            </div>
        </form>

        @role('admin')
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Admin: remove duplicate codes (keep the latest scan of each) or clear the entire unknown scans table.
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <form
                        method="POST"
                        action="{{ route('reports.unknown-scans.deduplicate') }}"
                        onsubmit="return confirm('Remove duplicate unknown scans, keeping only the latest scan of each code?')"
                    >
                        @csrf
                        <x-secondary-button>Remove duplicates</x-secondary-button>
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
        @endrole

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
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
                            <td class="px-3 py-2 font-medium">{{ $scan->scanned_code }}</td>
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
                                    <a
                                        href="{{ route('assets.create', [
                                            'tp_barcode' => $scan->scanned_code,
                                            'location_id' => $scan->location_id,
                                            'unknown_scan_id' => $scan->id,
                                            'status' => $scan->asset_status?->value,
                                        ]) }}"
                                        class="text-brand underline text-xs"
                                    >Add asset</a>
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
    </div>
</x-app-layout>

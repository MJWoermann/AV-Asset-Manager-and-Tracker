<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Assets</h2>
            <div class="flex gap-2">
                @role('admin|inventory_manager')
                    <a href="{{ route('export.assets', ['format' => 'xlsx']) }}" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Export</a>
                @endrole
                <a href="{{ route('assets.create') }}" class="text-sm px-3 py-2 bg-brand text-white">Add asset</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="GET" class="mb-4 flex flex-col sm:flex-row gap-2">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search FMI, barcode, SN, IP, MAC…" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white">
            <select name="status" class="rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white">
                <option value="">All statuses</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-black text-white dark:bg-brand">Search</button>
        </form>

        <div class="overflow-x-auto bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">TP Barcode</th>
                        <th class="px-3 py-2">Location</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2"><a class="text-brand hover:underline" href="{{ route('assets.show', $asset) }}">{{ $asset->name }}</a></td>
                            <td class="px-3 py-2">{{ $asset->itemType?->name }}</td>
                            <td class="px-3 py-2">{{ $asset->status?->label() }}</td>
                            <td class="px-3 py-2 font-mono">{{ $asset->tp_barcode }}</td>
                            <td class="px-3 py-2">{{ $asset->location?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-brand-charcoal">No assets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $assets->links() }}</div>
    </div>
</x-app-layout>

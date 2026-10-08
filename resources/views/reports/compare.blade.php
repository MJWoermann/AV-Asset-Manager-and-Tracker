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
            </div>
        </form>

        @if($result)
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $source->name }} vs {{ $target->name }}</p>

            @foreach([
                'matched' => 'Matched',
                'inventory_only' => 'Inventory only',
                'scanned_only' => 'Scanned only (not on inventory)',
            ] as $key => $title)
                <section>
                    <h3 class="font-semibold mb-2">{{ $title }} ({{ $result[$key]->count() }})</h3>
                    <ul class="bg-white dark:bg-black border divide-y text-sm max-h-80 overflow-y-auto">
                        @forelse($result[$key] as $row)
                            <li class="px-3 py-2 {{ !empty($row->parent_id) ? 'pl-8' : '' }}">
                                {{ $row->name }}
                                <span class="text-brand-charcoal dark:text-brand-silver font-mono"> {{ $row->tp_barcode ?? $row->fmi_ast ?? '' }}</span>
                            </li>
                        @empty
                            <li class="px-3 py-4 text-brand-charcoal">None</li>
                        @endforelse
                    </ul>
                </section>
            @endforeach
        @endif
    </div>
</x-app-layout>

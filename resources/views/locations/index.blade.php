<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Locations</h2>
            @role('admin|inventory_manager')
                <a href="{{ route('locations.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add</a>
            @endrole
        </div>
    </x-slot>
    @role('admin|inventory_manager')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="bulkLocations()">
    @else
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    @endrole
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

        @role('admin|inventory_manager')
            @if (count($duplicateNames) > 0)
                <div class="mb-4 border border-brand-charcoal/30 dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 text-sm space-y-2">
                    <p>
                        <span class="font-medium">{{ count($duplicateNames) }}</span>
                        duplicate name{{ count($duplicateNames) === 1 ? '' : 's' }} found:
                        {{ implode(', ', $duplicateNames) }}.
                    </p>
                    <p class="text-brand-charcoal dark:text-brand-silver">
                        Select locations (or use Select duplicates), then choose <span class="font-medium">Merge same names</span> in bulk actions. Assets on merged locations are reassigned to the kept location.
                    </p>
                    <button
                        type="button"
                        id="debug-select-duplicates"
                        class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver"
                        data-duplicate-names="{{ json_encode($duplicateNames) }}"
                        @click="selectDuplicateNames(JSON.parse($el.getAttribute('data-duplicate-names') || '[]'))"
                    >Select duplicates</button>
                    {{-- #region agent log --}}
                    <script>
                        (function () {
                            const btn = document.getElementById('debug-select-duplicates');
                            if (!btn) return;
                            btn.addEventListener('click', function () {
                                let attrOk = null;
                                let attrErr = null;
                                let attrCount = null;
                                try {
                                    const raw = btn.getAttribute('data-duplicate-names');
                                    const parsed = JSON.parse(raw);
                                    attrOk = true;
                                    attrCount = Array.isArray(parsed) ? parsed.length : null;
                                } catch (e) {
                                    attrOk = false;
                                    attrErr = String(e && e.message || e);
                                }
                                fetch('http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'2afa3b'},body:JSON.stringify({sessionId:'2afa3b',runId:'pre-fix',hypothesisId:'E',location:'locations/index:native-click',message:'Native click fired on Select duplicates',data:{attrOk,attrErr,attrCount,hasAlpine:!!(window.Alpine),boxCount:document.querySelectorAll('[data-bulk-location-id]').length},timestamp:Date.now()})}).catch(function(){});
                            }, true);
                        })();
                    </script>
                    {{-- #endregion --}}
                </div>
            @endif

            <x-bulk-location-actions :parents="$parentOptions" />
        @endrole

        @if ($locations->isEmpty())
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No locations yet.</p>
        @else
            @role('admin|inventory_manager')
                <div class="mb-2 flex items-center gap-2 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            class="rounded border-brand-charcoal/40"
                            :checked="allPageSelected"
                            @change="toggleAll($event.target.checked)"
                            aria-label="Select all locations"
                        >
                        <span>Select all</span>
                    </label>
                </div>
            @endrole
            <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal text-sm overflow-hidden">
                @php
                    $previousType = null;
                    $sameTypeRun = 0;
                @endphp
                @foreach ($locations as $location)
                    @php
                        if ($previousType === $location->type) {
                            $sameTypeRun++;
                        } else {
                            $previousType = $location->type;
                            $sameTypeRun = 0;
                        }
                    @endphp
                    @include('locations.partials.node', [
                        'location' => $location,
                        'alternate' => $sameTypeRun % 2 === 1,
                    ])
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>


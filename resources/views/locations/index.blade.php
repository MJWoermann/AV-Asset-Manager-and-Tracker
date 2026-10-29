<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Locations</h2>
            @can('locations:write')
                <a href="{{ route('locations.create') }}" data-history-roundtrip class="px-3 py-2 bg-brand text-white text-sm">Add</a>
            @endcan
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" data-location-list @modal-closed.window="if ($event.detail === 'delete-location') discardSavedLocationScroll()" x-data="bulkSelection(@js([
        'idAttribute' => 'data-bulk-location-id',
        'nameAttribute' => 'data-bulk-location-name',
        'enableMerge' => true,
        'enableTreeCollapse' => true,
        'fields' => [
            'update_parent' => false,
            'clear_parent' => false,
        ],
        'locationOptions' => $locationOptions,
        'initialDeleteLocation' => $initialDeleteLocation,
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

        @can('locations:update')
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
                        class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver"
                        data-duplicate-names="{{ json_encode($duplicateNames) }}"
                        @click="selectDuplicateNames(JSON.parse($el.getAttribute('data-duplicate-names') || '[]'))"
                    >Select duplicates</button>
                </div>
            @endif

            <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
                Deleting a location with no assets asks for confirmation. Deleting a location that has assets asks where those assets should go.
            </p>

            <x-bulk-location-actions :parents="$parentOptions" :locations="$locationOptions" />
        @endcan

        @if ($locations->isEmpty())
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No locations yet.</p>
        @else
            <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                @can('locations:update')
                    <label class="inline-flex items-center gap-2 mr-2">
                        <input
                            type="checkbox"
                            class="rounded border-brand-charcoal/40"
                            :checked="allPageSelected"
                            @change="toggleAll($event.target.checked)"
                            aria-label="Select all locations"
                        >
                        <span>Select all</span>
                    </label>
                @endcan
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="expandAll()"
                >Expand all</button>
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="collapseAll()"
                >Collapse all</button>
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="hideRacks()"
                >Hide racks</button>
            </div>
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

        @can('locations:update')
            <x-modal name="delete-location" :show="$errors->has('replacement_location_id')" focusable maxWidth="md">
                <form
                    method="POST"
                    class="p-6 space-y-4"
                    x-bind:action="deleteLocation?.action || '#'"
                >
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="deleting_location_id" x-bind:value="deleteLocation?.id || ''">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “<span x-text="deleteLocation?.name || ''"></span>”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="(deleteLocation?.assetsCount || 0) > 0">
                        This location is used by
                        <span x-text="deleteLocation?.assetsCount || 0"></span>
                        <span x-text="(deleteLocation?.assetsCount || 0) === 1 ? 'asset' : 'assets'"></span>.
                        Choose a replacement location for those assets, then delete.
                        Child locations stay on the list and become top-level.
                    </p>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="(deleteLocation?.assetsCount || 0) === 0">
                        This location has no assets. Delete it?
                        Child locations stay on the list and become top-level.
                    </p>
                    <div x-show="(deleteLocation?.assetsCount || 0) > 0">
                        <x-input-label for="replacement_location_id" value="Replacement location" />
                        <x-searchable-select
                            name="replacement_location_id"
                            id="replacement_location_id"
                            placeholder="Search by name or room code…"
                            :options="$locationOptions->map(fn ($option) => [
                                'value' => $option['id'],
                                'label' => $option['label'],
                                'keywords' => trim(($option['name'] ?? '').' '.($option['room_code'] ?? '')),
                            ])"
                            :selected="old('replacement_location_id')"
                            required
                        />
                        <x-input-error :messages="$errors->get('replacement_location_id')" class="mt-2" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-danger-button>Delete permanently</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endcan
    </div>
    <script>
        (function () {
            if (window.__avHistoryCollapseStarted) {
                return;
            }

            try {
                if (sessionStorage.getItem('av.historyCollapse')) {
                    return;
                }

                var raw = sessionStorage.getItem('av.locationListScroll');
                if (!raw) {
                    return;
                }

                var data = JSON.parse(raw);
                if (!data || data.path !== window.location.pathname) {
                    return;
                }

                if ('scrollRestoration' in history) {
                    history.scrollRestoration = 'manual';
                }

                var x = Number(data.x) || 0;
                var y = Number(data.y) || 0;
                if (x !== 0 || y !== 0) {
                    window.scrollTo(x, y);
                }
            } catch (e) {
                // The deferred script still restores when this early pass cannot.
            }
        })();
    </script>
</x-app-layout>

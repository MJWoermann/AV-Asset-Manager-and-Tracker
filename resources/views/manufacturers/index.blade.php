<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Manufacturers / Models</h2>
            @can('catalog:write')
                <a href="{{ route('manufacturers.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add manufacturer</a>
            @endcan
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
        x-data="manufacturerCatalogue({
            manufacturerOptions: @js($manufacturerOptions),
            totalModelsCount: @js($totalModelsCount),
            canManageCatalogue: @js($canManageCatalogue),
            replacementModelsUrl: @js($replacementModelsUrl),
            initialDeleteManufacturer: @js($initialDeleteManufacturer),
            initialDeleteModel: @js($initialDeleteModel),
        })"
    >
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

        @if ($manufacturers->isEmpty())
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No manufacturers yet.</p>
        @else
            <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
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
            </div>
            <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal text-sm overflow-hidden">
                @foreach ($manufacturers as $index => $manufacturer)
                    @include('manufacturers.partials.node', [
                        'manufacturer' => $manufacturer,
                        'alternate' => $index % 2 === 1,
                    ])
                @endforeach
            </ul>
        @endif

        @can('catalog:update')
            <x-modal name="delete-manufacturer" :show="$errors->has('replacement_manufacturer_id')" focusable maxWidth="md">
                <form
                    method="POST"
                    class="p-6 space-y-4"
                    x-bind:action="deleteManufacturer?.action || '#'"
                >
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="deleting_manufacturer_id" x-bind:value="deleteManufacturer?.id || ''">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “<span x-text="deleteManufacturer?.name || ''"></span>”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Models under this manufacturer are moved to the replacement (merged by name).
                        Assets are reassigned to the replacement manufacturer.
                    </p>
                    <div>
                        <x-input-label for="replacement_manufacturer_id" value="Replacement manufacturer" />
                        <x-searchable-select
                            name="replacement_manufacturer_id"
                            id="replacement_manufacturer_id"
                            placeholder="Search manufacturers…"
                            :options="$manufacturerOptions->map(fn ($option) => [
                                'value' => $option['id'],
                                'label' => $option['name'],
                            ])"
                            :selected="old('replacement_manufacturer_id')"
                            required
                        />
                        <x-input-error :messages="$errors->get('replacement_manufacturer_id')" class="mt-2" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-danger-button>Delete permanently</x-danger-button>
                    </div>
                </form>
            </x-modal>

            <x-modal name="delete-model" :show="$errors->has('replacement_manufacturer_model_id')" focusable maxWidth="md">
                <form
                    method="POST"
                    class="p-6 space-y-4"
                    x-bind:action="deleteModel?.action || '#'"
                >
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="deleting_model_id" x-bind:value="deleteModel?.id || ''">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “<span x-text="deleteModel?.label || ''"></span>”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        <template x-if="(deleteModel?.assetsCount || 0) > 0">
                            <span>
                                This model is used by
                                <span x-text="deleteModel?.assetsCount || 0"></span>
                                <span x-text="(deleteModel?.assetsCount || 0) === 1 ? 'asset' : 'assets'"></span>.
                                Choose a replacement model for those assets, then delete.
                            </span>
                        </template>
                        <template x-if="(deleteModel?.assetsCount || 0) === 0">
                            <span>No assets use this model. Choose a replacement model, then delete.</span>
                        </template>
                    </p>
                    <div>
                        <x-input-label for="replacement_manufacturer_model_id" value="Replacement model" />
                        <x-searchable-select
                            name="replacement_manufacturer_model_id"
                            id="replacement_manufacturer_model_id"
                            placeholder="Search models…"
                            :options="[]"
                            :selected="old('replacement_manufacturer_model_id')"
                            required
                        />
                        <x-input-error :messages="$errors->get('replacement_manufacturer_model_id')" class="mt-2" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-danger-button>Delete permanently</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endcan
    </div>
</x-app-layout>

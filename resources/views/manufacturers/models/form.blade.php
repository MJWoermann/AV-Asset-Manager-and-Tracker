@php
    $initialInterfaceTemplates = collect($interfaceTemplates ?? [])->map(fn ($row) => [
        'id' => $row['id'] ?? null,
        'name' => $row['name'] ?? '',
        'notes' => $row['notes'] ?? '',
        'connection_type_id' => $row['connection_type_id'] ?? null,
    ])->values()->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">
            {{ $manufacturerModel->exists ? 'Edit model' : 'New model' }}
            <span class="text-brand-charcoal dark:text-brand-silver font-normal text-base">— {{ $manufacturer->name }}</span>
        </h2>
    </x-slot>
    <div class="max-w-3xl mx-auto px-4">
        @if (session('status'))
            <p class="mb-3 text-sm text-brand">{{ session('status') }}</p>
        @endif

        <form
            method="POST"
            action="{{ $manufacturerModel->exists ? route('manufacturers.models.update', [$manufacturer, $manufacturerModel]) : route('manufacturers.models.store', $manufacturer) }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
            @feature('asset_connections')
                x-data="interfaceTemplateForm(@js($initialInterfaceTemplates))"
            @endfeature
        >
            @csrf
            @if($manufacturerModel->exists) @method('PUT') @endif
            @if($manufacturerModel->exists && $manufacturer->isUnspecified())
                <div
                    x-data="{ manufacturerId: @js((string) old('manufacturer_id', '')) }"
                    @searchable-select-changed="if ($event.detail.name === 'manufacturer_id') manufacturerId = $event.detail.value || ''"
                >
                    <x-input-label for="manufacturer_id" value="Manufacturer" />
                    <x-searchable-select
                        name="manufacturer_id"
                        id="manufacturer_id"
                        placeholder="Search manufacturers…"
                        :options="$assignmentManufacturers->map(fn ($option) => ['value' => $option->id, 'label' => $option->name])"
                        :selected="old('manufacturer_id')"
                        nullable
                        :create-option="['value' => '__new__', 'label' => '+ Add new manufacturer…']"
                    />
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                        Choose an existing manufacturer or add one. Assets, spares, and procedures that use this model move with it.
                    </p>
                    <x-input-error :messages="$errors->get('manufacturer_id')" class="mt-1" />
                    <div class="mt-2" x-show="manufacturerId === '__new__'" x-cloak>
                        <x-input-label for="new_manufacturer" value="New manufacturer name" />
                        <x-text-input
                            id="new_manufacturer"
                            name="new_manufacturer"
                            class="mt-1 block w-full"
                            :value="old('new_manufacturer')"
                        />
                        <x-input-error :messages="$errors->get('new_manufacturer')" class="mt-1" />
                    </div>
                </div>
            @endif
            <div>
                <x-input-label for="name" value="Model name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $manufacturerModel->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            @feature('asset_connections')
                <x-interface-template-repeater :connection-types="$connectionTypes">
                    New assets with this model receive these interfaces. Editing templates does not rewrite existing assets — use Sync missing below.
                </x-interface-template-repeater>
            @endfeature

            <x-primary-button>Save</x-primary-button>
        </form>

        @if($manufacturerModel->exists)
            @feature('asset_connections')
                <div class="mt-8 space-y-3 border p-4 bg-white dark:bg-brand-slate">
                    <h3 class="font-semibold text-lg">Sync missing interfaces</h3>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Copy any template interfaces that existing assets with this model do not already have (by template or name).
                    </p>
                    <form method="POST" action="{{ route('manufacturers.models.sync-interfaces', [$manufacturer, $manufacturerModel]) }}">
                        @csrf
                        <x-secondary-button type="submit">Sync missing templates</x-secondary-button>
                    </form>
                </div>
            @endfeature

            @if($manufacturerModel->aliases->isNotEmpty())
                <div class="mt-6 rounded border border-gray-200 dark:border-brand-charcoal p-3 space-y-1">
                    <p class="text-sm font-medium">Former names (import aliases)</p>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Spreadsheet rows using these names will use this model instead of creating a new one.
                    </p>
                    <ul class="text-sm list-disc list-inside">
                        @foreach($manufacturerModel->aliases as $alias)
                            <li>{{ $alias->former_name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($manufacturerModel->exists && ($linkedSpares ?? collect())->isNotEmpty())
                <div class="mt-6 border-t border-brand-silver dark:border-brand-charcoal pt-4">
                    <h3 class="font-medium text-sm">Linked spares</h3>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach($linkedSpares as $spare)
                            <li>
                                <a href="{{ route('spares.show', $spare) }}" class="text-brand font-mono">{{ $spare->code }}</a>
                                — {{ $spare->component }}
                                <span class="text-xs text-brand-charcoal">({{ $spare->quantity_on_hand }} on hand)</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6 space-y-2">
                @if($replacementModels->isEmpty())
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Create another model before deleting this one. Assets need a replacement model.
                    </p>
                @else
                    <x-danger-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-model-deletion')"
                    >
                        Delete model
                    </x-danger-button>
                @endif
            </div>

            <x-modal name="confirm-model-deletion" :show="$errors->has('replacement_manufacturer_model_id')" focusable maxWidth="md">
                <form method="POST" action="{{ route('manufacturers.models.destroy', [$manufacturer, $manufacturerModel]) }}" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “{{ $manufacturerModel->name }}”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        @if($assetCount > 0)
                            This model is used by {{ $assetCount }} {{ $assetCount === 1 ? 'asset' : 'assets' }}.
                            Choose a replacement model for those assets, then delete.
                        @else
                            No assets use this model. Choose a replacement model, then delete.
                        @endif
                        The deleted name is kept so future imports that still use it map to the replacement.
                    </p>
                    <div>
                        <x-input-label for="replacement_manufacturer_model_id" value="Replacement model" />
                        <x-searchable-select
                            name="replacement_manufacturer_model_id"
                            id="replacement_manufacturer_model_id"
                            placeholder="Search models…"
                            :options="$replacementModels->map(fn ($option) => [
                                'value' => $option->id,
                                'label' => trim(($option->manufacturer?->name ?? '').' / '.$option->name, ' /'),
                            ])"
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
        @endif
    </div>
</x-app-layout>

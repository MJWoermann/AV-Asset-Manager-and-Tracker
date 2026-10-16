@php
    $initialInterfaceTemplates = collect($interfaceTemplates ?? [])->map(fn ($row) => [
        'id' => $row['id'] ?? null,
        'name' => $row['name'] ?? '',
        'notes' => $row['notes'] ?? '',
        'connection_type_id' => $row['connection_type_id'] ?? null,
    ])->values()->all();
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $itemType->exists ? 'Edit type' : 'New type' }}</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4">
        @if (session('status'))
            <p class="mb-3 text-sm text-brand">{{ session('status') }}</p>
        @endif

        <form
            method="POST"
            action="{{ $itemType->exists ? route('item-types.update', $itemType) : route('item-types.store') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
            @feature('asset_connections')
                x-data="interfaceTemplateForm(@js($initialInterfaceTemplates))"
            @endfeature
        >
            @csrf
            @if($itemType->exists) @method('PUT') @endif
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $itemType->name)" required />
            </div>
            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" rows="2">{{ old('description', $itemType->description) }}</textarea>
            </div>

            @developerFeature('ru_visualiser')
                <label for="supports_ru_visualiser" class="flex items-start gap-2 text-sm">
                    <input type="hidden" name="supports_ru_visualiser" value="0">
                    <input
                        id="supports_ru_visualiser"
                        type="checkbox"
                        name="supports_ru_visualiser"
                        value="1"
                        class="rounded text-brand mt-0.5"
                        @checked(old('supports_ru_visualiser', $itemType->supports_ru_visualiser))
                    >
                    <span>
                        <span class="font-medium">Supports RU visualiser</span>
                        <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-0.5">
                            Enables a Rack tab on assets of this type for front/rear unit placements.
                        </span>
                    </span>
                </label>
            @enddeveloperFeature

            @developerFeature('stock_consumables')
                <label for="supports_stock_tracking" class="flex items-start gap-2 text-sm">
                    <input type="hidden" name="supports_stock_tracking" value="0">
                    <input
                        id="supports_stock_tracking"
                        type="checkbox"
                        name="supports_stock_tracking"
                        value="1"
                        class="rounded text-brand mt-0.5"
                        @checked(old('supports_stock_tracking', $itemType->supports_stock_tracking))
                    >
                    <span>
                        <span class="font-medium">Supports stock tracking</span>
                        <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-0.5">
                            Enables a Stock tab with movements, reorder levels, and Kanban QR tags.
                        </span>
                    </span>
                </label>
            @enddeveloperFeature

            @developerFeature('rigging')
                <div class="space-y-3 border border-brand-silver dark:border-brand-charcoal p-3">
                    <label for="supports_rigging_calculator" class="flex items-start gap-2 text-sm">
                        <input type="hidden" name="supports_rigging_calculator" value="0">
                        <input
                            id="supports_rigging_calculator"
                            type="checkbox"
                            name="supports_rigging_calculator"
                            value="1"
                            class="rounded text-brand mt-0.5"
                            @checked(old('supports_rigging_calculator', $itemType->supports_rigging_calculator))
                        >
                        <span>
                            <span class="font-medium">Supports Rigging calculator</span>
                            <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-0.5">
                                Makes this type available as a catalogue weight line in UDL and related calculators.
                            </span>
                        </span>
                    </label>
                    <div>
                        <x-input-label for="rigging_weight_kg" value="Catalogue weight (kg)" />
                        <x-text-input
                            id="rigging_weight_kg"
                            name="rigging_weight_kg"
                            type="number"
                            step="0.001"
                            min="0"
                            class="mt-1 block w-full"
                            :value="old('rigging_weight_kg', $itemType->rigging_weight_kg)"
                        />
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                            Reference weight for calculators — not live asset weight. Leave blank if users must enter weight manually.
                        </p>
                    </div>
                </div>
            @enddeveloperFeature

            <fieldset>
                <legend class="text-sm font-semibold mb-2">Custom field sets</legend>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">
                    Manage sets on the
                    <a href="{{ route('custom-field-sets.index') }}" class="text-brand">Field sets</a>
                    page.
                </p>
                @foreach($fieldSets as $set)
                    <label class="flex items-start gap-2 text-sm mb-2">
                        <input type="checkbox" name="field_sets[]" value="{{ $set->id }}" class="rounded text-brand mt-0.5"
                            @checked(collect(old('field_sets', $itemType->fieldSets->pluck('id')))->contains($set->id))>
                        <span>{{ $set->name }} <span class="text-brand-charcoal dark:text-brand-silver">({{ $set->definitions->pluck('name')->join(', ') }})</span></span>
                    </label>
                @endforeach
            </fieldset>

            @feature('asset_connections')
                <x-interface-template-repeater :connection-types="$connectionTypes">
                    New assets of this type receive these interfaces. Editing templates does not rewrite existing assets — use Sync missing below.
                </x-interface-template-repeater>
            @endfeature

            <div class="flex flex-wrap items-center gap-3">
                <x-primary-button>Save</x-primary-button>
                <a href="{{ route('item-types.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
            </div>
        </form>

        @if($itemType->exists)
            @feature('asset_connections')
                <div class="mt-8 space-y-3 border p-4 bg-white dark:bg-brand-slate">
                    <h3 class="font-semibold text-lg">Sync missing interfaces</h3>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Copy any template interfaces that existing assets of this type do not already have (by template or name).
                    </p>
                    <form method="POST" action="{{ route('item-types.sync-interfaces', $itemType) }}">
                        @csrf
                        <x-secondary-button type="submit">Sync missing templates</x-secondary-button>
                    </form>
                </div>
            @endfeature

            <div class="mt-8 space-y-3">
                <h3 class="font-semibold text-lg">Files</h3>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Files linked here appear on every asset of this type.
                </p>
                <x-file-attachments
                    linkable-type="item_type"
                    :linkable-id="$itemType->id"
                    :rows="$fileRows"
                    :can-mutate="true"
                />
            </div>

            <div class="mt-6 space-y-2">
                @if($replacementTypes->isEmpty())
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Create another item type before deleting this one. Assets need a replacement type.
                    </p>
                @else
                    <x-danger-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-item-type-deletion')"
                    >
                        Delete item type
                    </x-danger-button>
                @endif
            </div>

            <x-modal name="confirm-item-type-deletion" :show="$errors->has('replacement_item_type_id')" focusable maxWidth="md">
                <form method="POST" action="{{ route('item-types.destroy', $itemType) }}" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “{{ $itemType->name }}”?
                    </h2>

                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        @if($assetCount > 0)
                            This type is used by {{ $assetCount }} {{ $assetCount === 1 ? 'asset' : 'assets' }}.
                            Choose a replacement item type for those assets, then delete.
                        @else
                            No assets use this type. Choose a replacement item type, then delete.
                        @endif
                    </p>

                    <div>
                        <x-input-label for="replacement_item_type_id" value="Replacement item type" />
                        <select
                            id="replacement_item_type_id"
                            name="replacement_item_type_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            required
                        >
                            <option value="">Select a type…</option>
                            @foreach($replacementTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) old('replacement_item_type_id') === (string) $type->id)>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('replacement_item_type_id')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">
                            Cancel
                        </x-secondary-button>
                        <x-danger-button>
                            Delete permanently
                        </x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
</x-app-layout>

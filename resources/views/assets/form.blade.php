<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $asset->exists ? 'Edit asset' : 'New asset' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $asset->exists ? route('assets.update', $asset) : route('assets.store') }}" class="space-y-4 bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal p-4">
            @csrf
            @if($asset->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $asset->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('description', $asset->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="item_type_id" value="Item type" />
                    <x-searchable-select
                        name="item_type_id"
                        id="item_type_id"
                        :options="$itemTypes->map(fn ($type) => ['value' => $type->id, 'label' => $type->name])"
                        :selected="old('item_type_id', $asset->item_type_id)"
                        placeholder="Select item type"
                        required
                    />
                    <x-input-error :messages="$errors->get('item_type_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $asset->status?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="location_id" value="Location" />
                    <x-searchable-select
                        name="location_id"
                        id="location_id"
                        :options="$locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])"
                        :selected="old('location_id', $asset->location_id)"
                        nullable
                    />
                    <x-input-error :messages="$errors->get('location_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="parent_id" value="Parent asset" />
                    <x-searchable-select
                        name="parent_id"
                        id="parent_id"
                        :options="$parents->map(fn ($parent) => ['value' => $parent->id, 'label' => $parent->name])"
                        :selected="old('parent_id', $asset->parent_id)"
                        nullable
                    />
                    <x-input-error :messages="$errors->get('parent_id')" class="mt-1" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    'manufacturer' => 'Manufacturer',
                    'model' => 'Model',
                    'serial_number' => 'Serial Number',
                    'supplier' => 'Supplier',
                    'fmi_ast' => 'FMI AST#',
                    'tp_barcode' => 'TP Barcode',
                    'rig_tag' => 'RIG Tag #',
                    'ip_address' => 'IP Address',
                    'mac_address' => 'MAC Address',
                ] as $field => $label)
                    <div>
                        <x-input-label :for="$field" :value="$label" />
                        <x-text-input :id="$field" :name="$field" class="mt-1 block w-full" :value="old($field, $asset->{$field})" />
                    </div>
                @endforeach
                <div>
                    <x-input-label for="purchase_date" value="Purchase date" />
                    <x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1 block w-full" :value="old('purchase_date', optional($asset->purchase_date)->format('Y-m-d'))" />
                </div>
                <div>
                    <x-input-label for="warranty_expiry" value="Warranty expiry" />
                    <x-text-input id="warranty_expiry" name="warranty_expiry" type="date" class="mt-1 block w-full" :value="old('warranty_expiry', optional($asset->warranty_expiry)->format('Y-m-d'))" />
                </div>
                <div>
                    <x-input-label for="test_tag_expiry" value="Test & Tag expiry" />
                    <x-text-input id="test_tag_expiry" name="test_tag_expiry" type="date" class="mt-1 block w-full" :value="old('test_tag_expiry', optional($asset->test_tag_expiry)->format('Y-m-d'))" />
                </div>
                <div>
                    <x-input-label for="replacement_cost" value="Replacement cost" />
                    <x-text-input id="replacement_cost" name="replacement_cost" type="number" step="0.01" class="mt-1 block w-full" :value="old('replacement_cost', $asset->replacement_cost)" />
                </div>
            </div>

            @if($asset->exists && $asset->itemType)
                @php
                    $values = $asset->customFieldValues->keyBy('custom_field_definition_id');
                    $asset->loadMissing('itemType.fieldSets.definitions');
                @endphp
                @foreach($asset->itemType->fieldSets as $set)
                    <fieldset class="border border-brand-silver dark:border-brand-charcoal p-3">
                        <legend class="px-1 text-sm font-semibold text-brand">{{ $set->name }}</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($set->definitions as $def)
                                <div>
                                    <x-input-label :for="'cf_'.$def->id" :value="$def->name" />
                                    @if($def->type === 'checkbox')
                                        <input type="checkbox" id="cf_{{ $def->id }}" name="custom_fields[{{ $def->id }}]" value="1" class="rounded border-gray-300 text-brand"
                                            @checked(old('custom_fields.'.$def->id, $values[$def->id]->value ?? null) == '1')>
                                    @else
                                        <x-text-input id="cf_{{ $def->id }}" name="custom_fields[{{ $def->id }}]" class="mt-1 block w-full"
                                            :value="old('custom_fields.'.$def->id, $values[$def->id]->value ?? '')" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            @endif

            <div>
                <x-input-label for="notes" value="Notes" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('notes', $asset->notes) }}</textarea>
            </div>

            <div class="flex gap-2">
                <x-primary-button>Save</x-primary-button>
                <a href="{{ $asset->exists ? route('assets.show', $asset) : route('assets.index') }}" class="px-4 py-2 text-sm border border-brand-charcoal dark:border-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>

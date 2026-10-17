@php
    $ruVisualiserEnabled = $ruVisualiserEnabled ?? false;
    $stockTrackingEnabled = $stockTrackingEnabled ?? false;
    $emailIntegrationEnabled = $emailIntegrationEnabled ?? false;
    $macCustomFields = $macCustomFields ?? [];
    $itemTypesSupportRu = $itemTypesSupportRu ?? [];
    $itemTypesSupportStock = $itemTypesSupportStock ?? [];
    $initialItemTypeId = old('item_type_id', $asset->item_type_id);
    $showRuFieldsInitially = $ruVisualiserEnabled && (
        (bool) ($itemTypesSupportRu[(string) $initialItemTypeId] ?? false)
        || ($asset->exists && ($asset->itemType?->supports_ru_visualiser || $asset->ru_height !== null))
    );
    $showStockFieldsInitially = $stockTrackingEnabled && (
        (bool) ($itemTypesSupportStock[(string) $initialItemTypeId] ?? false)
        || ($asset->exists && ($asset->itemType?->supports_stock_tracking || $asset->min_stock_level !== null || $asset->max_stock_level !== null))
    );
    $ruHeightValue = old('ru_height', $ruHeightPrefill ?? $asset->ru_height);
    $needsAssetFormAlpine = $ruVisualiserEnabled || $stockTrackingEnabled || ($emailIntegrationEnabled && ! $asset->exists);
    $modelsByManufacturer = $modelsByManufacturer ?? [];
    $initialManufacturerId = old('manufacturer_id', $asset->manufacturer_id);
    $initialModelId = old('manufacturer_model_id', $asset->manufacturer_model_id);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $asset->exists ? 'Edit asset' : 'New asset' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <form
            method="POST"
            action="{{ $asset->exists ? route('assets.update', $asset) : route('assets.store') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4"
            @if($needsAssetFormAlpine)
                x-data="assetFormFields(@js([
                    'ruSupportsByType' => $itemTypesSupportRu,
                    'stockSupportsByType' => $itemTypesSupportStock,
                    'initialTypeId' => $initialItemTypeId !== null ? (string) $initialItemTypeId : '',
                    'forceShowRu' => (bool) ($asset->exists && $asset->ru_height !== null),
                    'forceShowStock' => (bool) ($asset->exists && ($asset->min_stock_level !== null || $asset->max_stock_level !== null)),
                    'macCustomFields' => $macCustomFields,
                    'emailMacNotify' => (bool) ($emailIntegrationEnabled && ! $asset->exists),
                    'initialMac' => (string) old('mac_address', $asset->mac_address),
                ]))"
                @searchable-select-changed="if ($event.detail.name === 'item_type_id') itemTypeId = $event.detail.value"
                @field-barcode-scanned="if ($event.detail.field === 'mac_address') macAddress = $event.detail.value"
            @endif
        >
            @csrf
            @if($asset->exists) @method('PUT') @endif
            @if(! $asset->exists && ! empty($unknownScanId ?? null))
                <input type="hidden" name="unknown_scan_id" value="{{ $unknownScanId }}">
                <p class="text-sm text-brand-charcoal dark:text-brand-silver border border-brand-silver dark:border-brand-charcoal px-3 py-2">
                    Creating from unknown scan. The scanned code is pre-filled — adjust the identifier fields if needed.
                </p>
            @endif

            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span> Required field
            </p>

            <div>
                <x-input-label for="name" value="Name" required />
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
                    <x-input-label for="item_type_id" value="Item type" required />
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
                    <x-input-label for="status" value="Status" required />
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

            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                x-data="catalogueManufacturerModelFields({
                    manufacturerId: @js($initialManufacturerId !== null && $initialManufacturerId !== '' ? (string) $initialManufacturerId : ''),
                    modelId: @js($initialModelId !== null && $initialModelId !== '' ? (string) $initialModelId : ''),
                    modelsByManufacturer: @js($modelsByManufacturer),
                })"
                @searchable-select-changed="onSearchableChanged($event.detail)"
            >
                <div>
                    <x-input-label for="manufacturer_id" value="Manufacturer" />
                    <x-searchable-select
                        name="manufacturer_id"
                        id="manufacturer_id"
                        :options="$manufacturers->map(fn ($manufacturer) => ['value' => $manufacturer->id, 'label' => $manufacturer->name])"
                        :selected="$initialManufacturerId"
                        nullable
                        :create-option="['value' => '__new__', 'label' => '+ Add new manufacturer…']"
                    />
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
                <div>
                    <x-input-label for="manufacturer_model_id" value="Model" />
                    <x-searchable-select
                        x-ref="modelSelect"
                        name="manufacturer_model_id"
                        id="manufacturer_model_id"
                        :options="[]"
                        :selected="$initialModelId"
                        nullable
                        :disabled="blank($initialManufacturerId)"
                        :create-option="['value' => '__new__', 'label' => '+ Add new model…']"
                    />
                    <x-input-error :messages="$errors->get('manufacturer_model_id')" class="mt-1" />
                    <div class="mt-2" x-show="modelId === '__new__'" x-cloak>
                        <x-input-label for="new_manufacturer_model" value="New model name" />
                        <x-text-input
                            id="new_manufacturer_model"
                            name="new_manufacturer_model"
                            class="mt-1 block w-full"
                            :value="old('new_manufacturer_model')"
                        />
                        <x-input-error :messages="$errors->get('new_manufacturer_model')" class="mt-1" />
                    </div>
                </div>
                @foreach([
                    'serial_number' => 'Serial Number',
                    'supplier' => 'Supplier',
                    'fmi_ast' => 'FMI AST#',
                    'tp_barcode' => 'TP Barcode',
                    'rig_tag' => 'RIG Tag #',
                    'ip_address' => 'IP Address',
                    'mac_address' => 'MAC Address',
                ] as $field => $label)
                    @php
                        $fieldIsScannable = \App\Support\ScannableIdentifierFields::isScannable($field);
                    @endphp
                    @if($fieldIsScannable)
                        @if($field === 'mac_address' && $emailIntegrationEnabled && ! $asset->exists)
                            <x-scannable-text-input
                                :id="$field"
                                :name="$field"
                                :label="$label"
                                :value="old($field, $asset->{$field})"
                                x-model="macAddress"
                            />
                        @else
                            <x-scannable-text-input
                                :id="$field"
                                :name="$field"
                                :label="$label"
                                :value="old($field, $asset->{$field})"
                            />
                        @endif
                    @else
                        <div>
                            <x-input-label :for="$field" :value="$label" />
                            <x-text-input
                                :id="$field"
                                :name="$field"
                                class="mt-1 block w-full"
                                :value="old($field, $asset->{$field})"
                            />
                        </div>
                    @endif
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

            @if($ruVisualiserEnabled)
                <fieldset
                    class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-3"
                    x-show="showRuFields"
                    x-cloak
                    @unless($showRuFieldsInitially)
                        style="display: none;"
                    @endunless
                >
                    <legend class="px-1 text-sm font-semibold text-brand">Rack / RU</legend>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Set rack capacity for the RU visualiser. When empty, a custom field named <span class="font-mono">ru</span> is used to prefill height.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="ru_height" value="RU height" />
                            <x-text-input
                                id="ru_height"
                                name="ru_height"
                                type="number"
                                min="1"
                                class="mt-1 block w-full"
                                :value="$ruHeightValue"
                            />
                            <x-input-error :messages="$errors->get('ru_height')" class="mt-1" />
                        </div>
                        <div class="flex items-end">
                            <label for="ru_rear_enabled" class="flex items-start gap-2 text-sm">
                                <input type="hidden" name="ru_rear_enabled" value="0">
                                <input
                                    id="ru_rear_enabled"
                                    type="checkbox"
                                    name="ru_rear_enabled"
                                    value="1"
                                    class="rounded text-brand mt-0.5"
                                    @checked(old('ru_rear_enabled', $asset->ru_rear_enabled))
                                >
                                <span>
                                    <span class="font-medium">Enable rear face</span>
                                    <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-0.5">
                                        Show a separate rear elevation on the Rack tab.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('ru_rear_enabled')" class="mt-1" />
                </fieldset>
            @endif

            @if($stockTrackingEnabled)
                <fieldset
                    class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-3"
                    x-show="showStockFields"
                    x-cloak
                    @unless($showStockFieldsInitially)
                        style="display: none;"
                    @endunless
                >
                    <legend class="px-1 text-sm font-semibold text-brand">Stock levels</legend>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        On-hand quantity is updated from the Stock tab. Set reorder min/max here.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="min_stock_level" value="Min stock level" />
                            <x-text-input
                                id="min_stock_level"
                                name="min_stock_level"
                                type="number"
                                min="0"
                                class="mt-1 block w-full"
                                :value="old('min_stock_level', $asset->min_stock_level)"
                            />
                            <x-input-error :messages="$errors->get('min_stock_level')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="max_stock_level" value="Max stock level" />
                            <x-text-input
                                id="max_stock_level"
                                name="max_stock_level"
                                type="number"
                                min="0"
                                class="mt-1 block w-full"
                                :value="old('max_stock_level', $asset->max_stock_level)"
                            />
                            <x-input-error :messages="$errors->get('max_stock_level')" class="mt-1" />
                        </div>
                    </div>
                    @if($asset->exists)
                        <p class="text-sm">Current on hand: <span class="font-semibold">{{ $asset->quantity ?? 0 }}</span></p>
                    @endif
                </fieldset>
            @endif

            @if($asset->exists && $asset->itemType)
                @php
                    $values = $asset->customFieldValues->keyBy('custom_field_definition_id');
                    $asset->loadMissing('itemType.fieldSets.definitions');
                    $macFieldCatalog = app(\App\Services\NotificationFieldCatalog::class);
                @endphp
                @foreach($asset->itemType->fieldSets as $set)
                    <fieldset class="border border-brand-silver dark:border-brand-charcoal p-3">
                        <legend class="px-1 text-sm font-semibold text-brand">{{ $set->name }}</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($set->definitions as $def)
                                @if($def->type === 'divider')
                                    <div class="sm:col-span-2 py-1">
                                        @if($def->name !== '' && strcasecmp($def->name, 'Divider') !== 0)
                                            <div class="text-sm font-medium text-brand-charcoal dark:text-brand-silver mb-2">{{ $def->name }}</div>
                                        @endif
                                        <hr class="border-brand-silver dark:border-brand-charcoal">
                                    </div>
                                @elseif($def->type === 'spacer')
                                    <div class="min-h-[2.5rem]" aria-hidden="true"></div>
                                @else
                                    @php
                                        $cfId = 'cf_'.$def->id;
                                        $cfIsMac = $def->type !== 'checkbox'
                                            && $macFieldCatalog->looksLikeMac('custom.'.$def->slug, $def->name);
                                    @endphp
                                    @if($cfIsMac)
                                        <x-scannable-text-input
                                            :id="$cfId"
                                            :name="'custom_fields['.$def->id.']'"
                                            :label="$def->name"
                                            :value="old('custom_fields.'.$def->id, $values[$def->id]->value ?? '')"
                                            :scan-aria-label="'Scan barcode into '.$def->name"
                                        />
                                    @else
                                        <div>
                                            <x-input-label :for="$cfId" :value="$def->name" />
                                            @if($def->type === 'checkbox')
                                                <input type="checkbox" id="{{ $cfId }}" name="custom_fields[{{ $def->id }}]" value="1" class="rounded border-gray-300 text-brand"
                                                    @checked(old('custom_fields.'.$def->id, $values[$def->id]->value ?? null) == '1')>
                                            @else
                                                <x-text-input id="{{ $cfId }}" name="custom_fields[{{ $def->id }}]" class="mt-1 block w-full"
                                                    :value="old('custom_fields.'.$def->id, $values[$def->id]->value ?? '')" />
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            @elseif(! $asset->exists && ($emailIntegrationEnabled ?? false) && count($macCustomFields) > 0)
                <fieldset class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-3" x-show="itemTypeId" x-cloak>
                    <legend class="px-1 text-sm font-semibold text-brand">MAC custom fields</legend>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">Shown for MAC onboarding when the selected item type includes these fields.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($macCustomFields as $macField)
                            <div
                                x-show="(@js($macField['item_type_ids'])).map(String).includes(String(itemTypeId))"
                                x-cloak
                            >
                                <x-scannable-text-input
                                    :id="'cf_'.$macField['id']"
                                    :name="'custom_fields['.$macField['id'].']'"
                                    :label="$macField['name']"
                                    :value="old('custom_fields.'.$macField['id'], '')"
                                    :scan-aria-label="'Scan barcode into '.$macField['name']"
                                    x-model="customMacValues['{{ $macField['id'] }}']"
                                />
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <div>
                <x-input-label for="notes" value="Notes" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('notes', $asset->notes) }}</textarea>
            </div>

            @feature('email_integration')
                @if(! $asset->exists && ($emailIntegrationEnabled ?? false))
                    <label
                        class="flex items-start gap-2 text-sm border border-brand-silver dark:border-brand-charcoal p-3"
                        x-show="showMacNotify"
                        x-cloak
                    >
                        <input type="hidden" name="notify_mac_onboarding" value="0">
                        <input
                            type="checkbox"
                            name="notify_mac_onboarding"
                            value="1"
                            class="rounded text-brand mt-0.5"
                            @checked(old('notify_mac_onboarding'))
                        >
                        <span>
                            <span class="font-medium">Email IT helpdesk with MAC details</span>
                            <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-0.5">
                                Sends the MAC onboarding template when at least one MAC value is present.
                            </span>
                        </span>
                    </label>
                @endif
            @endfeature

            <div class="flex justify-end gap-2">
                <a href="{{ $asset->exists ? route('assets.show', $asset) : route('assets.index') }}" class="px-4 py-2 text-sm border border-brand-charcoal dark:border-white">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

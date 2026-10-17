@php
    $modelsByManufacturer = $modelsByManufacturer ?? [];
    $initialManufacturerId = old('manufacturer_id', $spare->manufacturer_id);
    $initialModelId = old('manufacturer_model_id', $spare->manufacturer_model_id);
    $compatibleSelected = collect(old('compatible_model_ids', $spare->exists ? $spare->compatibleModels->pluck('id')->all() : []));
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $spare->exists ? 'Edit spare' : 'New spare' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 py-4">
        <form method="post" action="{{ $spare->exists ? route('spares.update', $spare) : route('spares.store') }}" class="space-y-4 text-sm">
            @csrf
            @if($spare->exists) @method('PUT') @endif

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-brand-charcoal dark:text-brand-silver">Code (Luhn)</label>
                    <input name="code" value="{{ old('code', $spare->code) }}" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1 font-mono" required>
                    @error('code')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs">Kind</label>
                    <select name="kind" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                        @foreach(\App\Enums\SparePartKind::options() as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', $spare->kind?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs">Component / description</label>
                    <input name="component" value="{{ old('component', $spare->component) }}" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1" required>
                </div>

                <div
                    class="sm:col-span-2 grid sm:grid-cols-2 gap-3"
                    x-data="catalogueManufacturerModelFields({
                        manufacturerId: @js($initialManufacturerId !== null && $initialManufacturerId !== '' ? (string) $initialManufacturerId : ''),
                        modelId: @js($initialModelId !== null && $initialModelId !== '' ? (string) $initialModelId : ''),
                        modelsByManufacturer: @js($modelsByManufacturer),
                    })"
                    @searchable-select-changed="onSearchableChanged($event.detail)"
                >
                    <div>
                        <label class="block text-xs" for="manufacturer_id">Manufacturer</label>
                        <x-searchable-select
                            name="manufacturer_id"
                            id="manufacturer_id"
                            :options="$manufacturers->map(fn ($manufacturer) => ['value' => $manufacturer->id, 'label' => $manufacturer->name])"
                            :selected="$initialManufacturerId"
                            nullable
                            :create-option="['value' => '__new__', 'label' => '+ Add new manufacturer…']"
                        />
                        @error('manufacturer_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        <div class="mt-2" x-show="manufacturerId === '__new__'" x-cloak>
                            <label class="block text-xs" for="new_manufacturer">New manufacturer name</label>
                            <input
                                id="new_manufacturer"
                                name="new_manufacturer"
                                value="{{ old('new_manufacturer') }}"
                                class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1"
                            >
                            @error('new_manufacturer')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs" for="manufacturer_model_id">Model</label>
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
                        @error('manufacturer_model_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        <div class="mt-2" x-show="modelId === '__new__'" x-cloak>
                            <label class="block text-xs" for="new_manufacturer_model">New model name</label>
                            <input
                                id="new_manufacturer_model"
                                name="new_manufacturer_model"
                                value="{{ old('new_manufacturer_model') }}"
                                class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1"
                            >
                            @error('new_manufacturer_model')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs">Product code</label>
                    <input name="product_code" value="{{ old('product_code', $spare->product_code) }}" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs">Severity</label>
                    <select name="severity" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                        @foreach(\App\Enums\SparePartSeverity::options() as $value => $label)
                            <option value="{{ $value }}" @selected(old('severity', $spare->severity?->value ?? 'moderate') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <fieldset class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-2">
                <legend class="px-1 font-medium">Holdings</legend>
                <div class="grid sm:grid-cols-3 gap-3">
                    @if(! $spare->exists)
                        <div>
                            <label class="block text-xs">Opening on hand</label>
                            <input type="number" min="0" name="quantity_on_hand" value="{{ old('quantity_on_hand', 0) }}" class="w-full border px-2 py-1">
                        </div>
                    @endif
                    <div>
                        <label class="block text-xs">Deployed (manual)</label>
                        <input type="number" min="0" name="quantity_deployed" value="{{ old('quantity_deployed', $spare->quantity_deployed) }}" class="w-full border px-2 py-1">
                    </div>
                    <div>
                        <label class="block text-xs">Min holding</label>
                        <input type="number" min="0" name="min_holding" value="{{ old('min_holding', $spare->min_holding) }}" class="w-full border px-2 py-1">
                    </div>
                    <div>
                        <label class="block text-xs">Max holding</label>
                        <input type="number" min="0" name="max_holding" value="{{ old('max_holding', $spare->max_holding) }}" class="w-full border px-2 py-1">
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs" for="location_id">Stock location</label>
                        <x-searchable-select
                            name="location_id"
                            id="location_id"
                            :options="$locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])"
                            :selected="old('location_id', $spare->location_id)"
                            nullable
                            placeholder="Search locations…"
                        />
                        @error('location_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs">Shelf / bin</label>
                        <input name="shelf_location" value="{{ old('shelf_location', $spare->shelf_location) }}" class="w-full border px-2 py-1">
                    </div>
                </div>
            </fieldset>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs">Supplier</label>
                    <input name="supplier" value="{{ old('supplier', $spare->supplier) }}" class="w-full border px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs">Unit cost</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost', $spare->unit_cost) }}" class="w-full border px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs">Lead time (text)</label>
                    <input name="lead_time" value="{{ old('lead_time', $spare->lead_time) }}" class="w-full border px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs">Replacement time (text)</label>
                    <input name="replacement_time" value="{{ old('replacement_time', $spare->replacement_time) }}" class="w-full border px-2 py-1">
                </div>
            </div>

            <div>
                <label class="block text-xs">Notes</label>
                <textarea name="notes" rows="3" class="w-full border px-2 py-1">{{ old('notes', $spare->notes) }}</textarea>
            </div>

            <div>
                <label class="block text-xs mb-1" for="compatible_model_ids">Also fits models</label>
                <x-searchable-multi-select
                    name="compatible_model_ids[]"
                    id="compatible_model_ids"
                    :options="$models->map(fn ($model) => [
                        'value' => $model->id,
                        'label' => trim(($model->manufacturer?->name ?? '').' — '.$model->name, ' —'),
                    ])"
                    :selected="$compatibleSelected"
                    placeholder="Search models…"
                />
                @error('compatible_model_ids')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                @error('compatible_model_ids.*')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            @feature('shelf_tag_generator')
                <div>
                    <label class="block text-xs">Preferred label layout</label>
                    <select name="shelf_tag_layout_id" class="w-full border px-2 py-1">
                        <option value="">Default</option>
                        @foreach($layouts as $layout)
                            <option value="{{ $layout->id }}" @selected((string) old('shelf_tag_layout_id', $spare->shelf_tag_layout_id) === (string) $layout->id)>{{ $layout->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endfeature

            <div class="flex justify-end gap-2">
                <a href="{{ $spare->exists ? route('spares.show', $spare) : route('spares.index') }}" class="px-4 py-2 border">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-brand text-white">Save</button>
            </div>
        </form>
    </div>
</x-app-layout>

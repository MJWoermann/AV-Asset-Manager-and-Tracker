@props([
    'locations',
    'selected' => null,
    'id' => 'location_id',
])

@php
    $initialLocationId = old('location_id', $selected);
    $initialLocationId = $initialLocationId === null || $initialLocationId === ''
        ? ''
        : (string) $initialLocationId;
@endphp

<div
    x-data="{
        locationId: @js($initialLocationId),
        get adding() {
            return this.locationId === '__new__';
        },
    }"
    class="space-y-2"
    :class="{ 'sm:col-span-2': adding }"
    @searchable-select-changed="if ($event.detail.name === 'location_id') locationId = $event.detail.value"
>
    <x-input-label :for="$id" value="Location for next items" />
    <x-searchable-select
        name="location_id"
        :id="$id"
        :options="$locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])->values()"
        :selected="$initialLocationId"
        nullable
        :create-option="['value' => '__new__', 'label' => '+ Add new location…']"
    />
    <x-input-error :messages="$errors->get('location_id')" class="mt-1" />

    <div
        x-show="adding"
        x-cloak
        class="space-y-3 border border-brand-silver dark:border-brand-charcoal p-3"
    >
        <div>
            <x-input-label for="{{ $id }}_new_name" value="New location name" />
            <x-text-input
                id="{{ $id }}_new_name"
                name="new_location[name]"
                class="mt-1 block w-full"
                :value="old('new_location.name')"
                x-bind:required="adding"
            />
            <x-input-error :messages="$errors->get('new_location.name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="{{ $id }}_new_type" value="Type" />
            <select
                id="{{ $id }}_new_type"
                name="new_location[type]"
                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                x-bind:required="adding"
            >
                @foreach(\App\Enums\LocationType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('new_location.type', 'site') === $type->value)>
                        {{ $type->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('new_location.type')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="{{ $id }}_new_parent" value="Parent (optional)" />
            <x-searchable-select
                name="new_location[parent_id]"
                id="{{ $id }}_new_parent"
                :options="$locations->filter(fn ($location) => $location->type !== \App\Enums\LocationType::Rack)->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])->values()"
                :selected="old('new_location.parent_id')"
                nullable
            />
            <x-input-error :messages="$errors->get('new_location.parent_id')" class="mt-1" />
        </div>
    </div>
</div>

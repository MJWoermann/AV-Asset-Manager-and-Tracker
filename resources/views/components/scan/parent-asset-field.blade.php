@props([
    'option' => null,
    'replaceExisting' => false,
    'id' => 'parent_asset_id',
])

@php
    $selectedParentId = old('parent_asset_id', $option['value'] ?? null);
    $selectedParentId = $selectedParentId === null || $selectedParentId === ''
        ? ''
        : (string) $selectedParentId;
    $options = $option ? [$option] : [];
@endphp

<div class="sm:col-span-2 space-y-2">
    <x-input-label :for="$id" value="Assign scanned assets as children of" />
    <x-searchable-select
        name="parent_asset_id"
        :id="$id"
        :options="$options"
        :selected="$selectedParentId"
        :search-url="route('assets.search')"
        placeholder="Search by name, ID#, barcode…"
        nullable
    />
    <x-input-error :messages="$errors->get('parent_asset_id')" class="mt-1" />
    <label class="flex items-start gap-2 text-sm">
        <input
            type="checkbox"
            name="replace_existing_parent"
            value="1"
            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand dark:border-brand-charcoal dark:bg-black"
            @checked($replaceExisting)
        >
        <span>Replace existing parent</span>
    </label>
    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
        Each scanned asset is assigned under this parent and takes its location.
        Assets that already have a different parent stay put unless you confirm that scan or turn on replace.
    </p>
</div>

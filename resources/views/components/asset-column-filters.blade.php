@props([
    'selectedColumns',
    'statuses',
    'locations',
    'itemTypes',
    'manufacturers' => [],
    'manufacturerModels' => [],
    'formId' => 'asset-filters',
])

@php
    use App\Support\AssetBrowseFilters;

    $inputClass = 'w-full min-w-[6rem] rounded border-brand-charcoal/30 text-xs dark:bg-black dark:border-brand-charcoal dark:text-white';

    $statusOptions = collect($statuses)
        ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
        ->values();

    $locationOptions = collect($locations)
        ->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->breadcrumb()])
        ->values();

    $itemTypeOptions = collect($itemTypes)
        ->map(fn ($itemType) => ['value' => (string) $itemType->id, 'label' => $itemType->name])
        ->values();

    $manufacturerOptions = collect($manufacturers)
        ->map(fn ($manufacturer) => ['value' => (string) $manufacturer->id, 'label' => $manufacturer->name])
        ->values();

    $modelOptions = collect($manufacturerModels)
        ->map(fn ($manufacturerModel) => [
            'value' => (string) $manufacturerModel->id,
            'label' => trim(($manufacturerModel->manufacturer?->name ? $manufacturerModel->manufacturer->name.' / ' : '').$manufacturerModel->name),
        ])
        ->values();
@endphp

<tr class="bg-brand-silver/70 dark:bg-brand-charcoal/80">
    <th class="px-3 py-2 w-10 align-middle">
        <input
            type="checkbox"
            class="rounded border-brand-charcoal/40"
            :checked="allPageSelected"
            @change="toggleAll($event.target.checked)"
            aria-label="Select all assets on this page"
        >
    </th>
    @foreach($selectedColumns as $column)
        <th class="px-2 py-2 align-bottom font-normal min-w-[7rem]">
            @switch($column)
                @case('status')
                    <x-searchable-select
                        name="status"
                        id="filter_status"
                        form="{{ $formId }}"
                        :options="$statusOptions"
                        :selected="request('status')"
                        placeholder="All"
                        nullable
                        auto-submit
                        compact
                        aria-label="Filter by status"
                    />
                    @break

                @case('location')
                    <x-searchable-select
                        name="location_id"
                        id="filter_location_id"
                        form="{{ $formId }}"
                        :options="$locationOptions"
                        :selected="request('location_id')"
                        placeholder="All locations"
                        nullable
                        auto-submit
                        compact
                        aria-label="Filter by location"
                    />
                    @break

                @case('item_type')
                    <x-searchable-select
                        name="item_type_id"
                        id="filter_item_type_id"
                        form="{{ $formId }}"
                        :options="$itemTypeOptions"
                        :selected="request('item_type_id')"
                        placeholder="All item types"
                        nullable
                        auto-submit
                        compact
                        aria-label="Filter by item type"
                    />
                    @break

                @case('manufacturer')
                    <x-searchable-select
                        name="manufacturer_id"
                        id="filter_manufacturer_id"
                        form="{{ $formId }}"
                        :options="$manufacturerOptions"
                        :selected="request('manufacturer_id')"
                        placeholder="All manufacturers"
                        nullable
                        auto-submit
                        compact
                        aria-label="Filter by manufacturer"
                    />
                    @break

                @case('model')
                    <x-searchable-select
                        name="manufacturer_model_id"
                        id="filter_manufacturer_model_id"
                        form="{{ $formId }}"
                        :options="$modelOptions"
                        :selected="request('manufacturer_model_id')"
                        placeholder="All models"
                        nullable
                        auto-submit
                        compact
                        aria-label="Filter by model"
                    />
                    @break

                @case('quantity')
                    <input
                        type="number"
                        name="quantity"
                        form="{{ $formId }}"
                        value="{{ request('quantity') }}"
                        placeholder="Qty"
                        min="0"
                        class="{{ $inputClass }}"
                        aria-label="Filter by quantity"
                        onchange="this.form?.requestSubmit()"
                    >
                    @break

                @case('test_tag_expiry')
                    <input
                        type="date"
                        name="test_tag_expiry"
                        form="{{ $formId }}"
                        value="{{ request('test_tag_expiry') }}"
                        class="{{ $inputClass }}"
                        aria-label="Filter by test and tag expiry"
                        onchange="this.form?.requestSubmit()"
                    >
                    @break

                @case('tp_barcode')
                @case('serial_number')
                @case('mac_address')
                    @php
                        $param = AssetBrowseFilters::columnParams()[$column] ?? $column;
                        $columnLabel = \App\Support\AssetTableColumns::label($column);
                    @endphp
                    <x-scannable-search
                        :id="'filter-'.$param"
                        :name="$param"
                        form="{{ $formId }}"
                        :value="request($param)"
                        placeholder="Filter…"
                        input-class="text-xs min-w-[7rem]"
                        :aria-label="'Filter by '.$columnLabel"
                        :scan-aria-label="'Scan barcode into '.$columnLabel.' filter'"
                    />
                    @break

                @default
                    @php
                        $param = AssetBrowseFilters::columnParams()[$column] ?? $column;
                    @endphp
                    <input
                        type="search"
                        name="{{ $param }}"
                        form="{{ $formId }}"
                        value="{{ request($param) }}"
                        placeholder="Filter…"
                        class="{{ $inputClass }}"
                        aria-label="Filter by {{ \App\Support\AssetTableColumns::label($column) }}"
                    >
            @endswitch
        </th>
    @endforeach
</tr>

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
        <th class="px-2 py-2 align-bottom font-normal">
            @switch($column)
                @case('status')
                    <select
                        name="status"
                        form="{{ $formId }}"
                        class="{{ $inputClass }}"
                        aria-label="Filter by status"
                    >
                        <option value="">All</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @break

                @case('location')
                    <select
                        name="location_id"
                        id="filter_location_id"
                        form="{{ $formId }}"
                        class="{{ $inputClass }}"
                        aria-label="Filter by location"
                    >
                        <option value="">All locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) request('location_id') === (string) $location->id)>
                                {{ $location->breadcrumb() }}
                            </option>
                        @endforeach
                    </select>
                    @break

                @case('item_type')
                    <select
                        name="item_type_id"
                        id="filter_item_type_id"
                        form="{{ $formId }}"
                        class="{{ $inputClass }}"
                        aria-label="Filter by item type"
                    >
                        <option value="">All item types</option>
                        @foreach($itemTypes as $itemType)
                            <option value="{{ $itemType->id }}" @selected((string) request('item_type_id') === (string) $itemType->id)>
                                {{ $itemType->name }}
                            </option>
                        @endforeach
                    </select>
                    @break

                @case('manufacturer')
                    <select
                        name="manufacturer_id"
                        id="filter_manufacturer_id"
                        form="{{ $formId }}"
                        class="{{ $inputClass }}"
                        aria-label="Filter by manufacturer"
                    >
                        <option value="">All manufacturers</option>
                        @foreach($manufacturers as $manufacturer)
                            <option value="{{ $manufacturer->id }}" @selected((string) request('manufacturer_id') === (string) $manufacturer->id)>
                                {{ $manufacturer->name }}
                            </option>
                        @endforeach
                    </select>
                    @break

                @case('model')
                    <select
                        name="manufacturer_model_id"
                        id="filter_manufacturer_model_id"
                        form="{{ $formId }}"
                        class="{{ $inputClass }}"
                        aria-label="Filter by model"
                    >
                        <option value="">All models</option>
                        @foreach($manufacturerModels as $manufacturerModel)
                            <option value="{{ $manufacturerModel->id }}" @selected((string) request('manufacturer_model_id') === (string) $manufacturerModel->id)>
                                {{ $manufacturerModel->manufacturer?->name }} / {{ $manufacturerModel->name }}
                            </option>
                        @endforeach
                    </select>
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

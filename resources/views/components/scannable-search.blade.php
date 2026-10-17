@props([
    'name' => null,
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'submitOnScan' => true,
    'scanAriaLabel' => 'Scan barcode into search',
    'inputClass' => '',
])

@php
    $id ??= $name ?: 'scannable-search';
@endphp

<div
    {{ $attributes->only('class')->class(['space-y-2 min-w-0 w-full']) }}
    x-data="fieldBarcodeScanner(@js($id), { submitOnScan: @js((bool) $submitOnScan) })"
>
    <div class="relative">
        <input
            type="search"
            id="{{ $id }}"
            @if($name !== null && $name !== '')
                name="{{ $name }}"
            @endif
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->except('class')->class([
                'w-full rounded border-brand-charcoal/30 pe-16 dark:bg-black dark:border-brand-charcoal dark:text-white',
                $inputClass,
            ]) }}
        >
        <button
            type="button"
            class="absolute inset-y-0 end-0 my-px me-px flex items-center rounded-e-md px-2.5 text-xs font-medium text-brand-charcoal hover:text-brand dark:text-brand-silver dark:hover:text-brand"
            @click="toggleCamera"
            :aria-pressed="scanning.toString()"
            :aria-label="scanning ? 'Stop camera' : @js($scanAriaLabel)"
        >
            <span x-text="scanning ? 'Stop' : 'Scan'"></span>
        </button>
    </div>
    <div
        id="field-qr-{{ $id }}"
        class="scan-qr-reader w-full max-h-[28vh] overflow-hidden border border-brand-silver dark:border-brand-charcoal"
        x-show="scanning"
        x-cloak
    ></div>
    <p class="text-xs text-brand-charcoal dark:text-brand-silver" x-show="scanning" x-cloak>
        Point the camera at a barcode. Search fills automatically, then the camera stops.
    </p>
    <p class="text-sm text-red-600" x-show="error" x-text="error" x-cloak></p>
</div>

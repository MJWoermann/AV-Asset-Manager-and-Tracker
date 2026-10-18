@props([
    'id',
    'name' => null,
    'value' => '',
    'label' => null,
    'showLabel' => true,
    'submitOnScan' => false,
    'scanAriaLabel' => null,
    'hint' => 'Point the camera at a barcode. This field fills automatically, then the camera stops.',
    'inputClass' => '',
])

@php
    $name ??= $id;
    $resolvedLabel = $label ?? \App\Support\ScannableIdentifierFields::label((string) $id);
    $scanAriaLabel ??= 'Scan barcode into '.$resolvedLabel;
@endphp

<div
    {{ $attributes->only('class')->class(['space-y-2']) }}
    x-data="fieldBarcodeScanner(@js($id), { submitOnScan: @js((bool) $submitOnScan) })"
>
    @if($showLabel)
        <x-input-label :for="$id" :value="$resolvedLabel" />
    @endif
    <div @class(['relative', 'mt-1' => $showLabel])>
        <input
            type="text"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value }}"
            {{ $attributes->except('class')->class([
                'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand pe-16 dark:border-brand-charcoal dark:bg-black dark:text-white',
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
        {{ $hint }}
    </p>
    <p class="text-sm text-red-600" x-show="error" x-text="error" x-cloak></p>
</div>

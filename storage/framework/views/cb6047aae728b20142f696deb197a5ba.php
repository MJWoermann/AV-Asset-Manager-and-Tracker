<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => null,
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'submitOnScan' => true,
    'scanAriaLabel' => 'Scan barcode into search',
    'inputClass' => '',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'name' => null,
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'submitOnScan' => true,
    'scanAriaLabel' => 'Scan barcode into search',
    'inputClass' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $id ??= $name ?: 'scannable-search';
?>

<div
    <?php echo e($attributes->only('class')->class(['space-y-2 min-w-0 w-full'])); ?>

    x-data="fieldBarcodeScanner(<?php echo \Illuminate\Support\Js::from($id)->toHtml() ?>, { submitOnScan: <?php echo \Illuminate\Support\Js::from((bool) $submitOnScan)->toHtml() ?> })"
>
    <div class="relative">
        <input
            type="search"
            id="<?php echo e($id); ?>"
            <?php if($name !== null && $name !== ''): ?>
                name="<?php echo e($name); ?>"
            <?php endif; ?>
            value="<?php echo e($value); ?>"
            placeholder="<?php echo e($placeholder); ?>"
            <?php echo e($attributes->except('class')->class([
                'w-full rounded border-brand-charcoal/30 pe-16 dark:bg-black dark:border-brand-charcoal dark:text-white',
                $inputClass,
            ])); ?>

        >
        <button
            type="button"
            class="absolute inset-y-0 end-0 my-px me-px flex items-center rounded-e-md px-2.5 text-xs font-medium text-brand-charcoal hover:text-brand dark:text-brand-silver dark:hover:text-brand"
            @click="toggleCamera"
            :aria-pressed="scanning.toString()"
            :aria-label="scanning ? 'Stop camera' : <?php echo \Illuminate\Support\Js::from($scanAriaLabel)->toHtml() ?>"
        >
            <span x-text="scanning ? 'Stop' : 'Scan'"></span>
        </button>
    </div>
    <div
        id="field-qr-<?php echo e($id); ?>"
        class="scan-qr-reader w-full max-h-[28vh] overflow-hidden border border-brand-silver dark:border-brand-charcoal"
        x-show="scanning"
        x-cloak
    ></div>
    <p class="text-xs text-brand-charcoal dark:text-brand-silver" x-show="scanning" x-cloak>
        Point the camera at a barcode. Search fills automatically, then the camera stops.
    </p>
    <p class="text-sm text-red-600" x-show="error" x-text="error" x-cloak></p>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/scannable-search.blade.php ENDPATH**/ ?>
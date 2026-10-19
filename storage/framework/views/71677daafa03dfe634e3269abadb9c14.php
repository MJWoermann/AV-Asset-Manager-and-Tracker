<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id',
    'name' => null,
    'value' => '',
    'label' => null,
    'showLabel' => true,
    'submitOnScan' => false,
    'scanAriaLabel' => null,
    'hint' => 'Point the camera at a barcode. This field fills automatically, then the camera stops.',
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
    'id',
    'name' => null,
    'value' => '',
    'label' => null,
    'showLabel' => true,
    'submitOnScan' => false,
    'scanAriaLabel' => null,
    'hint' => 'Point the camera at a barcode. This field fills automatically, then the camera stops.',
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
    $name ??= $id;
    $resolvedLabel = $label ?? \App\Support\ScannableIdentifierFields::label((string) $id);
    $scanAriaLabel ??= 'Scan barcode into '.$resolvedLabel;
?>

<div
    <?php echo e($attributes->only('class')->class(['space-y-2'])); ?>

    x-data="fieldBarcodeScanner(<?php echo \Illuminate\Support\Js::from($id)->toHtml() ?>, { submitOnScan: <?php echo \Illuminate\Support\Js::from((bool) $submitOnScan)->toHtml() ?> })"
>
    <?php if($showLabel): ?>
        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => $id,'value' => $resolvedLabel]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($id),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($resolvedLabel)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
    <?php endif; ?>
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['relative', 'mt-1' => $showLabel]); ?>">
        <input
            type="text"
            id="<?php echo e($id); ?>"
            name="<?php echo e($name); ?>"
            value="<?php echo e($value); ?>"
            <?php echo e($attributes->except('class')->class([
                'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand pe-16 dark:border-brand-charcoal dark:bg-black dark:text-white',
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
        <?php echo e($hint); ?>

    </p>
    <p class="text-sm text-red-600" x-show="error" x-text="error" x-cloak></p>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/scannable-text-input.blade.php ENDPATH**/ ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'inputName',
    'selected',
    'availableColumns',
    'customFieldSets',
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
    'inputName',
    'selected',
    'availableColumns',
    'customFieldSets',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="space-y-4 max-h-96 overflow-y-auto">
    <div class="space-y-1">
        <?php $__currentLoopData = $availableColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    name="<?php echo e($inputName); ?>[]"
                    value="<?php echo e($key); ?>"
                    class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                    <?php if(in_array($key, old($inputName, $selected), true)): echo 'checked'; endif; ?>
                    <?php if($key === 'name'): ?> disabled <?php endif; ?>
                >
                <span><?php echo e($label); ?></span>
            </label>
            <?php if($key === 'name'): ?>
                <input type="hidden" name="<?php echo e($inputName); ?>[]" value="name">
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php $__currentLoopData = $customFieldSets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fieldSet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="border-t border-brand-charcoal/20 pt-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-charcoal dark:text-brand-silver mb-2">
                <?php echo e($fieldSet['name']); ?>

            </p>
            <div class="space-y-1">
                <?php $__currentLoopData = $fieldSet['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="<?php echo e($inputName); ?>[]"
                            value="<?php echo e($field['key']); ?>"
                            class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                            <?php if(in_array($field['key'], old($inputName, $selected), true)): echo 'checked'; endif; ?>
                        >
                        <span><?php echo e($field['label']); ?></span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/admin/export-columns/partials/column-picker.blade.php ENDPATH**/ ?>
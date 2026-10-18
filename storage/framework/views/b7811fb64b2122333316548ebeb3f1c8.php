<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'banner',
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
    'variant' => 'banner',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $user = Auth::user();
    $show = $user?->needsDepartmentAssignment() ?? false;
?>

<?php if($show): ?>
    <?php
        $message = 'No Department configured for your profile. Please request an assignment from your IT support.';
    ?>

    <?php if($variant === 'inline'): ?>
        <div
            <?php echo e($attributes->merge(['class' => 'border border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100 text-sm px-4 py-3', 'role' => 'alert'])); ?>

        >
            <?php echo e($message); ?>

        </div>
    <?php else: ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div
                <?php echo e($attributes->merge(['class' => 'rounded border border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100 text-sm px-4 py-3', 'role' => 'alert'])); ?>

            >
                <?php echo e($message); ?>

            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/no-department-notice.blade.php ENDPATH**/ ?>
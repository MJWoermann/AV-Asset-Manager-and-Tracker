<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'availableColumns',
    'selectedColumns',
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
    'availableColumns',
    'selectedColumns',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver"
        @click="open = ! open"
        :aria-expanded="open.toString()"
    >
        Columns
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute z-40 mt-2 end-0 w-64 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-3 shadow-lg"
        style="display: none;"
    >
        <form method="POST" action="<?php echo e(route('preferences.asset-columns')); ?>" class="space-y-2">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">Choose columns for asset tables. Saved to your profile.</p>
            <div class="max-h-64 overflow-y-auto space-y-1 ps-1">
                <?php $__currentLoopData = $availableColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="columns[]"
                            value="<?php echo e($key); ?>"
                            class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                            <?php if(in_array($key, $selectedColumns, true)): echo 'checked'; endif; ?>
                            <?php if($key === 'name'): ?> disabled <?php endif; ?>
                        >
                        <span><?php echo e($label); ?></span>
                    </label>
                    <?php if($key === 'name'): ?>
                        <input type="hidden" name="columns[]" value="name">
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <button type="submit" class="w-full mt-2 px-3 py-2 bg-black text-white dark:bg-brand text-sm">
                Save columns
            </button>
        </form>
    </div>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/asset-column-picker.blade.php ENDPATH**/ ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'paginator',
    'default' => \App\Support\PageSize::DEFAULT,
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
    'paginator',
    'default' => \App\Support\PageSize::DEFAULT,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    use App\Support\PageSize;

    $selected = PageSize::selected(request(), (int) $default);
?>

<div <?php echo e($attributes->class('mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between')); ?>>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            <?php if($paginator->total() > 0 && $paginator->firstItem() !== null): ?>
                Showing
                <span class="font-medium text-black dark:text-white"><?php echo e($paginator->firstItem()); ?></span>
                to
                <span class="font-medium text-black dark:text-white"><?php echo e($paginator->lastItem()); ?></span>
                of
                <span class="font-medium text-black dark:text-white"><?php echo e($paginator->total()); ?></span>
                results
            <?php else: ?>
                Showing
                <span class="font-medium text-black dark:text-white">0</span>
                results
            <?php endif; ?>
        </p>

        <label class="inline-flex items-center gap-2 text-sm text-brand-charcoal dark:text-brand-silver">
            <span>Per page</span>
            <select
                class="rounded border-brand-charcoal/40 bg-white text-sm dark:bg-black dark:border-brand-charcoal dark:text-white"
                aria-label="Results per page"
                onchange="(() => { const url = new URL(window.location.href); url.searchParams.set('<?php echo e(PageSize::PARAM); ?>', this.value); url.searchParams.delete('page'); window.location.assign(url.toString()); })()"
            >
                <?php $__currentLoopData = PageSize::OPTIONS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $size): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($size); ?>" <?php if($selected === $size): echo 'selected'; endif; ?>><?php echo e($size); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e(PageSize::ALL); ?>" <?php if($selected === PageSize::ALL): echo 'selected'; endif; ?>>ALL</option>
            </select>
        </label>
    </div>

    <?php if($paginator->hasPages()): ?>
        <div class="sm:shrink-0">
            <?php echo e($paginator->links('vendor.pagination.numbers')); ?>

        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/paginated-results.blade.php ENDPATH**/ ?>
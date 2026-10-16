<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> <h2 class="font-semibold text-xl">Reports</h2> <?php $__env->endSlot(); ?>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Stocktake comparisons, unknown scans, and related tools.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="<?php echo e(route('reports.compare')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Compare</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Matched, inventory-only, and scanned-only between lists.</p>
            </a>
            <a href="<?php echo e(route('reports.unknown-scans')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Unknown scans</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Codes that did not match an asset during scanning.</p>
            </a>
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'asset_connections')): ?>
                <a href="<?php echo e(route('reports.connection-map')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Connection map</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Visualise asset interface connections.</p>
                </a>
            <?php endif; ?>
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                    <a href="<?php echo e(route('labels.index')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                        <h3 class="font-semibold">Labels</h3>
                        <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Shelf tag layouts and printing.</p>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/reports/index.blade.php ENDPATH**/ ?>
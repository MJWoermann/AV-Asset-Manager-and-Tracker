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
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Shelf tags</h2>
            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <a href="<?php echo e(route('shelf-tags.layouts.import')); ?>" class="px-3 py-2 border border-brand-charcoal/40 dark:border-brand-silver/40 text-sm inline-flex items-center justify-center">Import .lbx</a>
                <a href="<?php echo e(route('shelf-tags.layouts.create')); ?>" class="px-3 py-2 bg-brand text-white text-sm inline-flex items-center justify-center">New layout</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            <section class="min-w-0 lg:flex-[1.35] space-y-3 order-1">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Layouts</h3>
                <?php echo $__env->make('shelf-tags.layouts.partials.list', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </section>

            <section class="min-w-0 lg:flex-1 order-2">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Print</h3>
                <?php echo $__env->make('shelf-tags.print.partials.setup', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </section>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/shelf-tags/index.blade.php ENDPATH**/ ?>
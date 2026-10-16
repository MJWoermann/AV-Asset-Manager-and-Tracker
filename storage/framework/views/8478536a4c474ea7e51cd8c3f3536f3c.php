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
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl">Rigging calculators</h2>
            <a href="<?php echo e(route('rigging.index')); ?>" class="text-sm text-brand">Rigging hub</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-3xl mx-auto px-4 space-y-3">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Planning aid only — verify against certified engineering and venue limits before flying.
        </p>
        <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
            <?php $__currentLoopData = $calculators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <a href="<?php echo e(route('rigging.calculators.show', $key)); ?>" class="flex items-center justify-between px-4 py-3 text-sm hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal/40">
                        <span><?php echo e($label); ?></span>
                        <span class="text-brand-charcoal dark:text-brand-silver">→</span>
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/rigging/calculators/index.blade.php ENDPATH**/ ?>
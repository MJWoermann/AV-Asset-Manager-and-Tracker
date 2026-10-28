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
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Custom field sets</h2>
            <a href="<?php echo e(route('custom-field-sets.create')); ?>" data-history-roundtrip class="px-3 py-2 bg-brand text-white text-sm">Add</a>
        </div>
     <?php $__env->endSlot(); ?>
    <div class="max-w-7xl mx-auto px-4 space-y-3">
        <?php $__empty_1 = true; $__currentLoopData = $fieldSets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $set): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-white dark:bg-brand-slate border p-4 flex justify-between gap-3">
                <div>
                    <div class="font-medium"><?php echo e($set->name); ?></div>
                    <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                        <?php echo e($set->definitions_count); ?> <?php echo e($set->definitions_count === 1 ? 'field' : 'fields'); ?>

                        <?php if($set->itemTypes->isNotEmpty()): ?>
                            — used by <?php echo e($set->itemTypes->pluck('name')->join(', ')); ?>

                        <?php else: ?>
                            — not assigned to any item type
                        <?php endif; ?>
                    </div>
                    <?php if($set->description): ?>
                        <div class="text-sm mt-1"><?php echo e($set->description); ?></div>
                    <?php endif; ?>
                </div>
                <a href="<?php echo e(route('custom-field-sets.edit', $set)); ?>" data-history-roundtrip class="text-brand text-sm shrink-0">Edit</a>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="bg-white dark:bg-brand-slate border p-4 text-sm text-brand-charcoal dark:text-brand-silver">
                No custom field sets yet.
                <a href="<?php echo e(route('custom-field-sets.create')); ?>" data-history-roundtrip class="text-brand">Create one</a>
                to assign on item types.
            </div>
        <?php endif; ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/custom-field-sets/index.blade.php ENDPATH**/ ?>
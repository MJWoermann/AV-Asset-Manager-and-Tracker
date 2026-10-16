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
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Manufacturers / Models</h2>
            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager|scanner')): ?>
                <a href="<?php echo e(route('manufacturers.create')); ?>" class="px-3 py-2 bg-brand text-white text-sm">Add manufacturer</a>
            <?php endif; ?>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="manufacturerCatalogue()">
        <?php if(session('status')): ?>
            <div class="mb-4 text-sm text-brand"><?php echo e(session('status')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="mb-4 text-sm text-red-700 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php if($manufacturers->isEmpty()): ?>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No manufacturers yet.</p>
        <?php else: ?>
            <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="expandAll()"
                >Expand all</button>
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="collapseAll()"
                >Collapse all</button>
            </div>
            <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal text-sm overflow-hidden">
                <?php $__currentLoopData = $manufacturers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $manufacturer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $__env->make('manufacturers.partials.node', [
                        'manufacturer' => $manufacturer,
                        'alternate' => $index % 2 === 1,
                        'replacementManufacturers' => $replacementManufacturers,
                        'allModels' => $allModels,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/manufacturers/index.blade.php ENDPATH**/ ?>
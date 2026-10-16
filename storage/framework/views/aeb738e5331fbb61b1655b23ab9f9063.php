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
            <h2 class="font-semibold text-xl">Locations</h2>
            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                <a href="<?php echo e(route('locations.create')); ?>" class="px-3 py-2 bg-brand text-white text-sm">Add</a>
            <?php endif; ?>
        </div>
     <?php $__env->endSlot(); ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="bulkLocations()">
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

        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
            <?php if(count($duplicateNames) > 0): ?>
                <div class="mb-4 border border-brand-charcoal/30 dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 text-sm space-y-2">
                    <p>
                        <span class="font-medium"><?php echo e(count($duplicateNames)); ?></span>
                        duplicate name<?php echo e(count($duplicateNames) === 1 ? '' : 's'); ?> found:
                        <?php echo e(implode(', ', $duplicateNames)); ?>.
                    </p>
                    <p class="text-brand-charcoal dark:text-brand-silver">
                        Select locations (or use Select duplicates), then choose <span class="font-medium">Merge same names</span> in bulk actions. Assets on merged locations are reassigned to the kept location.
                    </p>
                    <button
                        type="button"
                        class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver"
                        data-duplicate-names="<?php echo e(json_encode($duplicateNames)); ?>"
                        @click="selectDuplicateNames(JSON.parse($el.getAttribute('data-duplicate-names') || '[]'))"
                    >Select duplicates</button>
                </div>
            <?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal814ff6ab62d605e0998112d73e57bd93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal814ff6ab62d605e0998112d73e57bd93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bulk-location-actions','data' => ['parents' => $parentOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bulk-location-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['parents' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parentOptions)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal814ff6ab62d605e0998112d73e57bd93)): ?>
<?php $attributes = $__attributesOriginal814ff6ab62d605e0998112d73e57bd93; ?>
<?php unset($__attributesOriginal814ff6ab62d605e0998112d73e57bd93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal814ff6ab62d605e0998112d73e57bd93)): ?>
<?php $component = $__componentOriginal814ff6ab62d605e0998112d73e57bd93; ?>
<?php unset($__componentOriginal814ff6ab62d605e0998112d73e57bd93); ?>
<?php endif; ?>
        <?php endif; ?>

        <?php if($locations->isEmpty()): ?>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No locations yet.</p>
        <?php else: ?>
            <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                    <label class="inline-flex items-center gap-2 mr-2">
                        <input
                            type="checkbox"
                            class="rounded border-brand-charcoal/40"
                            :checked="allPageSelected"
                            @change="toggleAll($event.target.checked)"
                            aria-label="Select all locations"
                        >
                        <span>Select all</span>
                    </label>
                <?php endif; ?>
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
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="hideRacks()"
                >Hide racks</button>
            </div>
            <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal text-sm overflow-hidden">
                <?php
                    $previousType = null;
                    $sameTypeRun = 0;
                ?>
                <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        if ($previousType === $location->type) {
                            $sameTypeRun++;
                        } else {
                            $previousType = $location->type;
                            $sameTypeRun = 0;
                        }
                    ?>
                    <?php echo $__env->make('locations.partials.node', [
                        'location' => $location,
                        'alternate' => $sameTypeRun % 2 === 1,
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/locations/index.blade.php ENDPATH**/ ?>
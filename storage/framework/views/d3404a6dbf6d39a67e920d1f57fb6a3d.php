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
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">Lists</h2>
            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                <a href="<?php echo e(route('lists.create')); ?>" class="px-3 py-2 bg-brand text-white text-sm">New list</a>
            <?php endif; ?>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
            x-data="bulkSelection(<?php echo \Illuminate\Support\Js::from([
                'idAttribute' => 'data-bulk-list-id',
                'fields' => [
                    'update_department' => false,
                ],
            ])->toHtml() ?>)"
        <?php endif; ?>
    >
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

        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
            <?php if (isset($component)) { $__componentOriginal53082dcd5602dbbb2d601618923cede5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53082dcd5602dbbb2d601618923cede5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bulk-list-actions','data' => ['departments' => $departments]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bulk-list-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['departments' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($departments)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53082dcd5602dbbb2d601618923cede5)): ?>
<?php $attributes = $__attributesOriginal53082dcd5602dbbb2d601618923cede5; ?>
<?php unset($__attributesOriginal53082dcd5602dbbb2d601618923cede5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53082dcd5602dbbb2d601618923cede5)): ?>
<?php $component = $__componentOriginal53082dcd5602dbbb2d601618923cede5; ?>
<?php unset($__componentOriginal53082dcd5602dbbb2d601618923cede5); ?>
<?php endif; ?>
        <?php endif; ?>

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
                            <th class="px-3 py-2 w-10">
                                <input
                                    type="checkbox"
                                    class="rounded border-brand-charcoal/40"
                                    :checked="allPageSelected"
                                    @change="toggleAll($event.target.checked)"
                                    aria-label="Select all lists"
                                >
                            </th>
                        <?php endif; ?>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Items</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $lists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
                                <td class="px-3 py-2">
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        data-bulk-list-id="<?php echo e($list->id); ?>"
                                        :checked="!!selected[<?php echo e($list->id); ?>]"
                                        @change="setSelected(<?php echo e($list->id); ?>, $event.target.checked)"
                                        aria-label="Select <?php echo e($list->name); ?>"
                                    >
                                </td>
                            <?php endif; ?>
                            <td class="px-3 py-2"><a class="text-brand" href="<?php echo e(route('lists.show', $list)); ?>"><?php echo e($list->name); ?></a></td>
                            <td class="px-3 py-2 capitalize"><?php echo e($list->type); ?></td>
                            <td class="px-3 py-2"><?php echo e($list->items_count); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-4 text-brand-charcoal dark:text-brand-silver" colspan="<?php echo e(auth()->user()?->hasRole('admin') ? 4 : 3); ?>">
                                No lists yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/lists/index.blade.php ENDPATH**/ ?>
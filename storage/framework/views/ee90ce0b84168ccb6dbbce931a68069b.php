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
            <h2 class="font-semibold text-xl">Departments</h2>
            <a href="<?php echo e(route('admin.departments.create')); ?>" class="px-3 py-2 bg-brand text-white text-sm">Add department</a>
        </div>
     <?php $__env->endSlot(); ?>
    <div class="max-w-7xl mx-auto px-4">
        <?php if($errors->any()): ?>
            <div class="mb-4 rounded border border-red-300 bg-red-50 dark:bg-red-950/40 dark:border-red-800 p-3 text-sm text-red-700 dark:text-red-300">
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

        <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
            Departments isolate operational records (assets, lists, scans, files, spares). Locations and catalogue data stay shared.
        </p>

        <table class="min-w-full text-sm bg-white dark:bg-brand-slate border">
            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                <tr>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Members</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                        <td class="px-3 py-2"><?php echo e($department->name); ?></td>
                        <td class="px-3 py-2"><?php echo e($department->is_active ? 'Active' : 'Inactive'); ?></td>
                        <td class="px-3 py-2"><?php echo e($department->users_count); ?></td>
                        <td class="px-3 py-2 space-x-3 whitespace-nowrap">
                            <a class="text-brand" href="<?php echo e(route('admin.departments.edit', $department)); ?>">Edit</a>
                            <?php if (! ($department->isGeneral())): ?>
                                <form
                                    method="POST"
                                    action="<?php echo e(route('admin.departments.destroy', $department)); ?>"
                                    class="inline"
                                    onsubmit='return confirm(<?php echo \Illuminate\Support\Js::from("Delete {$department->name}? This cannot be undone.")->toHtml() ?>)'
                                >
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="text-red-600">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <div class="mt-4"><?php echo e($departments->links()); ?></div>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/admin/departments/index.blade.php ENDPATH**/ ?>
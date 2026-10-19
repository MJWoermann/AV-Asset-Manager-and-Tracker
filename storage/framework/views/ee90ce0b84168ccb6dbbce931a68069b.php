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

        <form
            method="POST"
            action="<?php echo e(route('admin.departments.row-colours')); ?>"
            class="mb-6 flex flex-wrap items-center justify-between gap-3 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4"
        >
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <label class="inline-flex items-start gap-2 text-sm max-w-2xl">
                <input type="hidden" name="enabled" value="0">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    class="mt-0.5 rounded text-brand"
                    <?php if(old('enabled', $rowColoursEnabled)): echo 'checked'; endif; ?>
                >
                <span>
                    Colour asset rows by department when a user can see 2+ departments
                    <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-1">
                        Applies soft background tint on Assets and list browse tables. Dashboard department colours always use the colour field below.
                    </span>
                </span>
            </label>
            <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Save <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
        </form>

        <table class="min-w-full text-sm bg-white dark:bg-brand-slate border">
            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                <tr>
                    <th class="px-3 py-2">Colour</th>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Members</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                        <td class="px-3 py-2">
                            <?php if($department->color): ?>
                                <span
                                    class="inline-block h-5 w-5 rounded border border-brand-charcoal/30 dark:border-brand-silver/40"
                                    style="background-color: <?php echo e($department->color); ?>"
                                    title="<?php echo e($department->color); ?>"
                                    aria-label="<?php echo e($department->color); ?>"
                                ></span>
                            <?php else: ?>
                                <span class="text-brand-charcoal dark:text-brand-silver">—</span>
                            <?php endif; ?>
                        </td>
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
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
        <div class="flex justify-between"><h2 class="font-semibold text-xl">Item types</h2>
            <a href="<?php echo e(route('item-types.create')); ?>" class="px-3 py-2 bg-brand text-white text-sm">Add</a></div>
     <?php $__env->endSlot(); ?>
    <div
        class="max-w-7xl mx-auto px-4 space-y-3"
        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
            x-data="bulkSelection(<?php echo \Illuminate\Support\Js::from([
                'idAttribute' => 'data-bulk-item-type-id',
                'fields' => [
                    'update_department' => false,
                ],
            ])->toHtml() ?>)"
        <?php endif; ?>
    >
        <?php if(session('status')): ?>
            <p class="text-sm text-brand"><?php echo e(session('status')); ?></p>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="text-sm text-red-700 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
            <?php if (isset($component)) { $__componentOriginal25d5c979489cea5af3b2e35753d68020 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal25d5c979489cea5af3b2e35753d68020 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bulk-item-type-actions','data' => ['departments' => $departments]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bulk-item-type-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['departments' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($departments)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal25d5c979489cea5af3b2e35753d68020)): ?>
<?php $attributes = $__attributesOriginal25d5c979489cea5af3b2e35753d68020; ?>
<?php unset($__attributesOriginal25d5c979489cea5af3b2e35753d68020); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal25d5c979489cea5af3b2e35753d68020)): ?>
<?php $component = $__componentOriginal25d5c979489cea5af3b2e35753d68020; ?>
<?php unset($__componentOriginal25d5c979489cea5af3b2e35753d68020); ?>
<?php endif; ?>
        <?php endif; ?>

        <?php $__currentLoopData = $itemTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white dark:bg-brand-slate border p-4 flex justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
                        <input
                            type="checkbox"
                            class="rounded border-brand-charcoal/40 mt-1"
                            data-bulk-item-type-id="<?php echo e($type->id); ?>"
                            :checked="!!selected[<?php echo e($type->id); ?>]"
                            @change="setSelected(<?php echo e($type->id); ?>, $event.target.checked)"
                            aria-label="Select <?php echo e($type->name); ?>"
                        >
                    <?php endif; ?>
                    <div class="min-w-0">
                        <div class="font-medium"><?php echo e($type->name); ?></div>
                        <div class="text-sm text-brand-charcoal dark:text-brand-silver"><?php echo e($type->fieldSets->pluck('name')->join(', ') ?: 'No field sets'); ?></div>
                        <div class="text-xs text-brand-charcoal dark:text-brand-silver mt-1"><?php echo e($type->assets_count); ?> <?php echo e($type->assets_count === 1 ? 'asset' : 'assets'); ?></div>
                    </div>
                </div>
                <a href="<?php echo e(route('item-types.edit', $type)); ?>" class="text-brand text-sm shrink-0">Edit</a>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/item-types/index.blade.php ENDPATH**/ ?>
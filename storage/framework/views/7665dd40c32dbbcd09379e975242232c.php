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
     <?php $__env->slot('header', null, []); ?> <h2 class="font-semibold text-xl">Maintenance</h2> <?php $__env->endSlot(); ?>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Repairs, procedures, and critical spares.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="<?php echo e(route('repairs.index')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Repairs</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">
                    <?php if (\Illuminate\Support\Facades\Blade::check('role', 'external_repairer')): ?>
                        Jobs assigned to you.
                    <?php else: ?>
                        Open and completed repair work orders linked to faults.
                    <?php endif; ?>
                </p>
            </a>
            <?php if (! \Illuminate\Support\Facades\Blade::check('role', 'external_repairer')): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('repairs:procedures')): ?>
                    <a href="<?php echo e(route('repair-procedures.index')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                        <h3 class="font-semibold">Repair procedures</h3>
                        <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Reusable SOP checklists scoped to models, types, or spares.</p>
                    </a>
                <?php endif; ?>
                <a href="<?php echo e(route('spares.index')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Spares</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Critical spares register, kits, and usage history.</p>
                </a>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/maintenance/index.blade.php ENDPATH**/ ?>
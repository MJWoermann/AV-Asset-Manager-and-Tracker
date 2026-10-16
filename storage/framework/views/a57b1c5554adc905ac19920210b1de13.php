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
     <?php $__env->slot('header', null, []); ?> <h2 class="font-semibold text-xl">Admin</h2> <?php $__env->endSlot(); ?>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Application settings and administration.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="<?php echo e(route('admin.users.index')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Manage users</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Accounts, roles, and department membership.</p>
            </a>
            <a href="<?php echo e(route('admin.departments.index')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Departments</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Operational data isolation boundaries.</p>
            </a>
            <a href="<?php echo e(route('admin.export-columns.edit')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Export columns</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Default columns for asset exports.</p>
            </a>
            <a href="<?php echo e(route('admin.storage.edit')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">Storage</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Attachment disks, migration, and backups.</p>
            </a>
            <a href="<?php echo e(route('admin.sso.edit')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                <h3 class="font-semibold">SSO</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Single sign-on configuration.</p>
            </a>
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'email_integration')): ?>
                <a href="<?php echo e(route('admin.email.edit')); ?>" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Email</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Inbound and outbound email integration.</p>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/admin/index.blade.php ENDPATH**/ ?>
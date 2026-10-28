<?php
    $canSettings = auth()->user()?->can('admin:settings') ?? false;
    $canOps = auth()->user()?->can('email:ops') ?? false;

    $tabs = [];

    if ($canSettings) {
        $tabs[] = [
            'label' => 'Settings',
            'href' => route('admin.email.edit'),
            'active' => request()->routeIs('admin.email.edit', 'admin.email.update', 'admin.email.test'),
        ];
        $tabs[] = [
            'label' => 'Recipients',
            'href' => route('admin.email.recipients.index'),
            'active' => request()->routeIs('admin.email.recipients.*'),
        ];
        $tabs[] = [
            'label' => 'Templates',
            'href' => route('admin.email.templates.index'),
            'active' => request()->routeIs('admin.email.templates.*'),
        ];
        $tabs[] = [
            'label' => 'Rules',
            'href' => route('admin.email.rules.index'),
            'active' => request()->routeIs('admin.email.rules.*'),
        ];
    }

    if ($canOps) {
        $tabs[] = [
            'label' => 'Inbound',
            'href' => route('admin.email.inbound.index'),
            'active' => request()->routeIs('admin.email.inbound.*'),
        ];
        $tabs[] = [
            'label' => 'Outbound',
            'href' => route('admin.email.outbound.index'),
            'active' => request()->routeIs('admin.email.outbound.*'),
        ];
    }
?>

<div class="overflow-x-auto">
    <div class="flex gap-1 min-w-max border-b border-brand-silver dark:border-brand-charcoal" role="tablist">
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a
                href="<?php echo e($tab['href']); ?>"
                role="tab"
                aria-selected="<?php echo e($tab['active'] ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab['active'] ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                <?php echo e($tab['label']); ?>

            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/email-tabs.blade.php ENDPATH**/ ?>
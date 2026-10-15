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
        <h2 class="font-semibold text-xl">Outbound email threads</h2>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Sent notifications used for inbound reply matching.
        </p>

        <?php $__empty_1 = true; $__currentLoopData = $threads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $thread): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-white dark:bg-brand-slate border p-4 space-y-1">
                <div class="font-medium"><?php echo e($thread->subject ?: '(no subject)'); ?></div>
                <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                    <?php
                        $eventKey = \App\Enums\NotificationEventKey::tryFrom((string) $thread->event_key);
                    ?>
                    <?php echo e($eventKey?->label() ?? $thread->event_key); ?>

                    · <?php echo e($thread->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i')); ?>

                    <?php if($thread->asset): ?>
                        · <a href="<?php echo e(route('assets.show', [$thread->asset, 'tab' => 'maintenance'])); ?>" class="text-brand"><?php echo e($thread->asset->name); ?></a>
                    <?php endif; ?>
                    <?php if($thread->sender): ?>
                        · Sent by <?php echo e($thread->sender->name); ?>

                    <?php endif; ?>
                </div>
                <?php if(is_array($thread->to_addresses) && count($thread->to_addresses) > 0): ?>
                    <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                        To <?php echo e(implode(', ', $thread->to_addresses)); ?>

                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No outbound threads yet.</p>
        <?php endif; ?>

        <?php echo e($threads->links()); ?>

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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/admin/email/outbound/index.blade.php ENDPATH**/ ?>
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
        <h2 class="font-semibold text-xl">Email</h2>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 space-y-4">
        <?php if (isset($component)) { $__componentOriginal1c041b1d1b579c8879da47a4a3f52938 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1c041b1d1b579c8879da47a4a3f52938 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.email-tabs','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('email-tabs'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1c041b1d1b579c8879da47a4a3f52938)): ?>
<?php $attributes = $__attributesOriginal1c041b1d1b579c8879da47a4a3f52938; ?>
<?php unset($__attributesOriginal1c041b1d1b579c8879da47a4a3f52938); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1c041b1d1b579c8879da47a4a3f52938)): ?>
<?php $component = $__componentOriginal1c041b1d1b579c8879da47a4a3f52938; ?>
<?php unset($__componentOriginal1c041b1d1b579c8879da47a4a3f52938); ?>
<?php endif; ?>

        <?php if(session('status')): ?>
            <p class="text-sm text-brand"><?php echo e(session('status')); ?></p>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="text-sm text-red-600"><?php echo e($errors->first()); ?></div>
        <?php endif; ?>

        <form method="GET" action="<?php echo e(route('admin.email.inbound.index')); ?>" class="flex flex-wrap gap-3 items-end">
            <div class="min-w-[12rem]">
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'status','value' => 'Status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'status','value' => 'Status']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'status','id' => 'status','options' => collect(['all' => 'All'])->union($statuses)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),'selected' => $status,'autoSubmit' => true,'ariaLabel' => 'Filter by inbound status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'status','id' => 'status','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(collect(['all' => 'All'])->union($statuses)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status),'auto-submit' => true,'aria-label' => 'Filter by inbound status']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $attributes = $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $component = $__componentOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
            </div>
            <?php if (isset($component)) { $__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.clear-filters','data' => ['href' => route('admin.email.inbound.index'),'active' => $status !== 'all']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('clear-filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.email.inbound.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status !== 'all')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113)): ?>
<?php $attributes = $__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113; ?>
<?php unset($__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113)): ?>
<?php $component = $__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113; ?>
<?php unset($__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113); ?>
<?php endif; ?>
        </form>

        <?php $__empty_1 = true; $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-white dark:bg-brand-slate border p-4 space-y-2">
                <div class="flex flex-wrap justify-between gap-2">
                    <div>
                        <div class="font-medium"><?php echo e($action->subject ?: '(no subject)'); ?></div>
                        <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                            From <?php echo e($action->from_address ?: 'unknown'); ?>

                            · <?php echo e($action->status?->label() ?? $action->status); ?>

                            <?php if($action->asset): ?>
                                · <a href="<?php echo e(route('assets.show', [$action->asset, 'tab' => 'maintenance'])); ?>" class="text-brand"><?php echo e($action->asset->name); ?></a>
                            <?php else: ?>
                                · No asset matched
                            <?php endif; ?>
                            <?php if($action->rule): ?>
                                · Rule: <?php echo e($action->rule->name); ?>

                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($action->status?->value === 'pending'): ?>
                        <div class="flex gap-2 text-sm">
                            <form method="POST" action="<?php echo e(route('admin.email.inbound.apply', $action)); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="px-3 py-1 bg-brand text-white">Apply</button>
                            </form>
                            <form method="POST" action="<?php echo e(route('admin.email.inbound.reject', $action)); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="px-3 py-1 border">Reject</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if($action->snippet): ?>
                    <p class="text-sm whitespace-pre-wrap"><?php echo e($action->snippet); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No inbound actions for this filter.</p>
        <?php endif; ?>

        <?php echo e($actions->links()); ?>

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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/admin/email/inbound/index.blade.php ENDPATH**/ ?>
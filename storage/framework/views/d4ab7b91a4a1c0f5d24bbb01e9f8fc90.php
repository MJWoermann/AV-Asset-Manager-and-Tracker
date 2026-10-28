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
        <h2 class="font-semibold text-xl">Scan to event</h2>
     <?php $__env->endSlot(); ?>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <?php if (! ($session)): ?>
            <form method="POST" action="<?php echo e(route('scan.start')); ?>" class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                <?php echo csrf_field(); ?>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Optionally choose an event list to add scanned items to, and an inventory list to check against.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'event_list_id','value' => 'Event list (add items to)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'event_list_id','value' => 'Event list (add items to)']); ?>
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
                        <select id="event_list_id" name="event_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="" selected>None</option>
                            <?php $__currentLoopData = $eventLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($list->id); ?>"><?php echo e($list->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'inventory_list_id','value' => 'Inventory list (search against)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'inventory_list_id','value' => 'Inventory list (search against)']); ?>
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
                        <select id="inventory_list_id" name="inventory_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="">None</option>
                            <?php $__currentLoopData = $inventoryLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($list->id); ?>"><?php echo e($list->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php if (isset($component)) { $__componentOriginal98b0ed2f3ba771387e29466021246ec2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal98b0ed2f3ba771387e29466021246ec2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.location-field','data' => ['locations' => $locations]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.location-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal98b0ed2f3ba771387e29466021246ec2)): ?>
<?php $attributes = $__attributesOriginal98b0ed2f3ba771387e29466021246ec2; ?>
<?php unset($__attributesOriginal98b0ed2f3ba771387e29466021246ec2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal98b0ed2f3ba771387e29466021246ec2)): ?>
<?php $component = $__componentOriginal98b0ed2f3ba771387e29466021246ec2; ?>
<?php unset($__componentOriginal98b0ed2f3ba771387e29466021246ec2); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal62cf2f378266570595baa55e0afc4388 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal62cf2f378266570595baa55e0afc4388 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.asset-status-field','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.asset-status-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal62cf2f378266570595baa55e0afc4388)): ?>
<?php $attributes = $__attributesOriginal62cf2f378266570595baa55e0afc4388; ?>
<?php unset($__attributesOriginal62cf2f378266570595baa55e0afc4388); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal62cf2f378266570595baa55e0afc4388)): ?>
<?php $component = $__componentOriginal62cf2f378266570595baa55e0afc4388; ?>
<?php unset($__componentOriginal62cf2f378266570595baa55e0afc4388); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal8b1f5e6dd9566fac732a70272e1c0306 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8b1f5e6dd9566fac732a70272e1c0306 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.parent-asset-field','data' => ['option' => $parentOption,'replaceExisting' => $replaceExistingParent]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.parent-asset-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['option' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parentOption),'replace-existing' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($replaceExistingParent)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8b1f5e6dd9566fac732a70272e1c0306)): ?>
<?php $attributes = $__attributesOriginal8b1f5e6dd9566fac732a70272e1c0306; ?>
<?php unset($__attributesOriginal8b1f5e6dd9566fac732a70272e1c0306); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8b1f5e6dd9566fac732a70272e1c0306)): ?>
<?php $component = $__componentOriginal8b1f5e6dd9566fac732a70272e1c0306; ?>
<?php unset($__componentOriginal8b1f5e6dd9566fac732a70272e1c0306); ?>
<?php endif; ?>
                </div>
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Start scanning <?php echo $__env->renderComponent(); ?>
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
        <?php else: ?>
            <form method="POST" action="<?php echo e(route('scan.lists', $session)); ?>" class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'event_list_id','value' => 'Event list (add items to)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'event_list_id','value' => 'Event list (add items to)']); ?>
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
                        <select id="event_list_id" name="event_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="" <?php if(! $session->event_list_id): echo 'selected'; endif; ?>>None</option>
                            <?php $__currentLoopData = $eventLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($list->id); ?>" <?php if($session->event_list_id == $list->id): echo 'selected'; endif; ?>><?php echo e($list->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'inventory_list_id','value' => 'Inventory list (search against)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'inventory_list_id','value' => 'Inventory list (search against)']); ?>
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
                        <select id="inventory_list_id" name="inventory_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="">None</option>
                            <?php $__currentLoopData = $inventoryLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($list->id); ?>" <?php if($session->inventory_list_id == $list->id): echo 'selected'; endif; ?>><?php echo e($list->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php if (isset($component)) { $__componentOriginal98b0ed2f3ba771387e29466021246ec2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal98b0ed2f3ba771387e29466021246ec2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.location-field','data' => ['locations' => $locations,'selected' => $session->location_id]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.location-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($session->location_id)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal98b0ed2f3ba771387e29466021246ec2)): ?>
<?php $attributes = $__attributesOriginal98b0ed2f3ba771387e29466021246ec2; ?>
<?php unset($__attributesOriginal98b0ed2f3ba771387e29466021246ec2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal98b0ed2f3ba771387e29466021246ec2)): ?>
<?php $component = $__componentOriginal98b0ed2f3ba771387e29466021246ec2; ?>
<?php unset($__componentOriginal98b0ed2f3ba771387e29466021246ec2); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal62cf2f378266570595baa55e0afc4388 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal62cf2f378266570595baa55e0afc4388 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.asset-status-field','data' => ['selected' => $session->asset_status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.asset-status-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($session->asset_status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal62cf2f378266570595baa55e0afc4388)): ?>
<?php $attributes = $__attributesOriginal62cf2f378266570595baa55e0afc4388; ?>
<?php unset($__attributesOriginal62cf2f378266570595baa55e0afc4388); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal62cf2f378266570595baa55e0afc4388)): ?>
<?php $component = $__componentOriginal62cf2f378266570595baa55e0afc4388; ?>
<?php unset($__componentOriginal62cf2f378266570595baa55e0afc4388); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal8b1f5e6dd9566fac732a70272e1c0306 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8b1f5e6dd9566fac732a70272e1c0306 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.parent-asset-field','data' => ['option' => $parentOption,'replaceExisting' => $replaceExistingParent,'id' => 'active_parent_asset_id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.parent-asset-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['option' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parentOption),'replace-existing' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($replaceExistingParent),'id' => 'active_parent_asset_id']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8b1f5e6dd9566fac732a70272e1c0306)): ?>
<?php $attributes = $__attributesOriginal8b1f5e6dd9566fac732a70272e1c0306; ?>
<?php unset($__attributesOriginal8b1f5e6dd9566fac732a70272e1c0306); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8b1f5e6dd9566fac732a70272e1c0306)): ?>
<?php $component = $__componentOriginal8b1f5e6dd9566fac732a70272e1c0306; ?>
<?php unset($__componentOriginal8b1f5e6dd9566fac732a70272e1c0306); ?>
<?php endif; ?>
                </div>
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Update lists <?php echo $__env->renderComponent(); ?>
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

            <?php
                $initialEventItems = $eventListItems->map(fn ($item) => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'is_child_expand' => (bool) $item->is_child_expand,
                    'asset' => [
                        'id' => $item->asset?->id,
                        'name' => $item->asset?->name,
                        'tp_barcode' => $item->asset?->tp_barcode,
                        'fmi_ast' => $item->asset?->fmi_ast,
                        'rig_tag' => $item->asset?->rig_tag,
                        'serial_number' => $item->asset?->serial_number,
                        'item_type' => $item->asset?->itemType?->name,
                        'url' => $item->asset ? route('assets.show', $item->asset) : null,
                    ],
                ])->values();

                $initialUnknownScans = $unknownScans->map(fn ($scan) => [
                    'id' => $scan->id,
                    'scanned_code' => $scan->scanned_code,
                    'quantity' => $scan->quantity,
                    'note' => $scan->note,
                    'location' => $scan->location?->name,
                    'has_event_list' => (bool) $scan->event_list_id,
                    'has_location' => (bool) $scan->location_id,
                    'resolved' => $scan->isResolved(),
                    'create_url' => route('assets.create', array_filter([
                        'tp_barcode' => $scan->scanned_code,
                        'location_id' => $scan->location_id,
                        'unknown_scan_id' => $scan->id,
                        'status' => $scan->asset_status?->value,
                        'parent_id' => $session->parent_asset_id,
                        'return_to' => route('scan.index', absolute: false),
                    ], fn ($value) => $value !== null && $value !== '')),
                    'time' => $scan->created_at?->timezone(config('app.timezone'))->format('g:i:s A'),
                ])->values();
            ?>

            <div
                x-data="barcodeScanner(<?php echo \Illuminate\Support\Js::from(route('scan.scan', $session))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($session->event_list_id)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($initialEventItems)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(route('unknown-scans.update', ['unknownScan' => '__ID__']))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($initialUnknownScans)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($clearScanRecent ?? false)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(auth()->user()?->wantsScanNoise() ?? false)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(auth()->user()?->wantsScanVibrate() ?? false)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(route('unknown-scans.link', ['unknownScan' => '__ID__']))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(route('assets.search'))->toHtml() ?>)"
                class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4"
            >
                <?php if($session->parentAsset): ?>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Scanned assets will be assigned as children of
                        <a href="<?php echo e(route('assets.show', $session->parentAsset)); ?>" class="text-brand"><?php echo e($session->parentAsset->name); ?></a>
                        and will take that parent's location.
                    </p>
                <?php endif; ?>
                <div
                    x-show="cameraNeedsSecureOrigin"
                    x-cloak
                    class="border border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100 text-sm px-3 py-2 space-y-2"
                    role="status"
                >
                    <p class="font-medium">Camera blocked on this address</p>
                    <p>
                        Browsers only allow the camera on <strong>HTTPS</strong> or <strong>http://localhost</strong>.
                        You opened this page over plain HTTP (usually a LAN IP), so camera scanning cannot start.
                        A USB / Bluetooth hardware barcode scanner still works here.
                    </p>
                    <ol class="list-decimal list-inside space-y-1">
                        <li x-show="localhostCameraUrl" x-cloak>
                            On <strong>this PC</strong>:
                            <a :href="localhostCameraUrl" class="underline font-medium">open Scan via localhost</a>
                            (same session cookie may not carry over — sign in again if asked).
                        </li>
                        <li x-show="httpsCameraUrl" x-cloak>
                            If HTTPS is already set up:
                            <a :href="httpsCameraUrl" class="underline font-medium">open this page over HTTPS</a>.
                        </li>
                        <li>
                            For phones and other PCs: ask an admin to enable HTTPS on the server
                            (private CA / self-signed TLS), then bookmark the <code class="text-xs">https://</code> URL.
                        </li>
                    </ol>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-text="inputModeStatus" data-scan-input-mode-status></p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="useCamera"
                            x-show="!scanning && !cameraNeedsSecureOrigin"
                            class="px-4 py-2 bg-brand text-white text-sm"
                        >Use camera</button>
                        <a
                            x-show="!scanning && cameraNeedsSecureOrigin && (localhostCameraUrl || httpsCameraUrl)"
                            x-cloak
                            :href="httpsCameraUrl || localhostCameraUrl"
                            class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm"
                        >Open secure Scan page</a>
                        <button
                            type="button"
                            @click="stopCamera"
                            x-show="scanning"
                            x-cloak
                            class="px-4 py-2 bg-black text-white text-sm"
                        >Stop camera</button>
                        <button
                            type="button"
                            @click="useHardware"
                            x-show="showHardwareSwitch"
                            x-cloak
                            class="px-4 py-2 border border-brand-charcoal dark:border-brand-silver text-sm"
                        >Use hardware scanner</button>
                    </div>
                </div>
                <div
                    id="qr-reader"
                    class="scan-qr-reader w-full max-w-xl mx-auto max-h-[28vh] sm:max-h-[min(50vh,28rem)] overflow-hidden"
                    x-show="scanning"
                    x-cloak
                ></div>

                <form @submit.prevent="submitCode" class="flex flex-col sm:flex-row gap-2">
                    <input
                        type="text"
                        x-ref="codeInput"
                        x-model="code"
                        @keydown.enter.prevent="submitCode"
                        placeholder="Scan or type barcode / ID"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        class="flex-1 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                    <input type="number" min="1" x-model="quantity" class="w-24 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" aria-label="Quantity">
                    <button type="submit" class="px-4 py-2 bg-black text-white dark:bg-brand text-sm" :disabled="busy && queue.length === 0">Add</button>
                </form>

                <p class="text-sm text-brand" x-text="message" x-show="message" x-cloak></p>
                <p class="text-sm text-red-600" x-text="error" x-show="error" x-cloak></p>

                <div
                    x-show="warning"
                    x-cloak
                    class="border border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100 text-sm px-3 py-2"
                    role="alert"
                >
                    <p class="font-medium">Not on inventory list</p>
                    <p x-text="warning"></p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold">Added this session</h3>
                        <div class="flex items-center gap-2">
                            <a href="<?php echo e(route('reports.unknown-scans')); ?>" class="text-xs text-brand underline">Unknown scan report</a>
                            <button
                                type="button"
                                @click="resetRecent"
                                x-show="recent.length > 0"
                                x-cloak
                                class="px-2 py-1 border border-brand-charcoal dark:border-brand-silver text-xs"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="recent.length === 0" x-cloak>No items added yet.</p>
                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal border border-brand-silver dark:border-brand-charcoal max-h-40 sm:max-h-64 overflow-y-auto" x-show="recent.length > 0">
                        <template x-for="entry in recent" :key="entry.key">
                            <li
                                class="px-3 py-2 text-sm flex flex-col gap-1"
                                :class="{
                                    'bg-amber-50 dark:bg-amber-950/30': entry.inventory_status === 'not_on_inventory',
                                    'bg-red-50 dark:bg-red-950/30': entry.unknown,
                                }"
                            >
                                <div class="flex justify-between gap-2">
                                    <a
                                        x-show="!entry.unknown && entry.asset?.url"
                                        :href="entry.asset.url"
                                        class="font-medium text-brand underline"
                                        x-text="entry.asset?.name || 'Unknown asset'"
                                    ></a>
                                    <span
                                        x-show="entry.unknown || !entry.asset?.url"
                                        class="font-medium"
                                        x-text="entry.unknown ? ('Not in system: ' + (entry.unknown_scan?.scanned_code || '')) : (entry.asset?.name || 'Unknown asset')"
                                    ></span>
                                    <span class="text-xs text-brand-charcoal dark:text-brand-silver whitespace-nowrap" x-text="entry.time"></span>
                                </div>
                                <div class="text-xs text-brand-charcoal dark:text-brand-silver flex flex-wrap items-center gap-x-3 gap-y-0.5">
                                    <span x-show="!entry.unknown && entry.asset?.tp_barcode" x-text="'TP: ' + entry.asset.tp_barcode"></span>
                                    <span x-show="!entry.unknown && entry.asset?.fmi_ast" x-text="'FMI: ' + entry.asset.fmi_ast"></span>
                                    <span x-show="!entry.unknown && entry.asset?.item_type" x-text="entry.asset.item_type"></span>
                                    <span x-show="!entry.unknown && entry.is_child_expand">Child</span>
                                    <span x-show="!entry.unknown && entry.duplicate" class="text-amber-700 dark:text-amber-300">Already on list</span>
                                    <span x-show="!entry.unknown && entry.inventory_status === 'not_on_inventory'" class="text-amber-700 dark:text-amber-300">Not on inventory</span>
                                    <span x-show="!entry.unknown && entry.inventory_status === 'matched'" class="text-brand">On inventory</span>
                                    <span x-show="entry.parent_message" class="text-amber-700 dark:text-amber-300" x-text="entry.parent_message"></span>
                                    <span x-show="entry.unknown" class="text-red-700 dark:text-red-300">Missing asset</span>
                                    <span x-show="entry.unknown && entry.unknown_scan?.location" x-text="'Location: ' + entry.unknown_scan.location"></span>
                                    <span x-show="entry.unknown && entry.unknown_scan?.note" class="truncate max-w-[14rem]" x-text="'Note: ' + entry.unknown_scan.note"></span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2" x-show="entry.parent_assignment === 'needs_confirm'" x-cloak>
                                    <button
                                        type="button"
                                        class="px-2 py-1 border border-brand-charcoal dark:border-brand-silver text-xs"
                                        @click="confirmParentAssignment(entry)"
                                        :disabled="entry.confirmingParent"
                                    >
                                        Assign under <span x-text="entry.session_parent?.name || 'parent'"></span>
                                    </button>
                                </div>
                                <div class="flex items-center gap-2" x-show="entry.unknown && entry.unknown_scan" x-cloak>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center p-1 border border-brand-charcoal dark:border-brand-silver"
                                        title="Add note"
                                        aria-label="Add note"
                                        @click="toggleNoteEditor(entry)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        x-show="!entry.unknown_scan.resolved"
                                        class="inline-flex items-center justify-center p-1 border border-brand-charcoal dark:border-brand-silver"
                                        title="Link item"
                                        aria-label="Link item"
                                        @click="openLink(entry)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path d="M12.232 4.232a2.5 2.5 0 013.536 3.536l-1.225 1.224a.75.75 0 001.061 1.06l1.224-1.224a4 4 0 00-5.656-5.656l-3 3a4 4 0 00.225 5.865.75.75 0 00.977-1.138 2.5 2.5 0 01-.142-3.667l3-3z" />
                                            <path d="M11.603 7.963a.75.75 0 00-.977 1.138 2.5 2.5 0 01.142 3.667l-3 3a2.5 2.5 0 01-3.536-3.536l1.225-1.224a.75.75 0 00-1.061-1.06l-1.224 1.224a4 4 0 105.656 5.656l3-3a4 4 0 00-.225-5.865z" />
                                        </svg>
                                    </button>
                                    <a
                                        x-show="!entry.unknown_scan.resolved"
                                        :href="entry.unknown_scan.create_url"
                                        class="inline-flex items-center justify-center p-1 border border-brand-charcoal dark:border-brand-silver"
                                        title="Create asset"
                                        aria-label="Create asset"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                        </svg>
                                    </a>
                                </div>
                                <div class="space-y-1" x-show="entry.editingNote" x-cloak>
                                    <textarea
                                        x-model="entry.noteDraft"
                                        rows="2"
                                        class="w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm"
                                        placeholder="Note to help identify this item later…"
                                    ></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="px-2 py-1 border text-xs" @click="entry.editingNote = false">Cancel</button>
                                        <button type="button" class="px-2 py-1 bg-black text-white dark:bg-brand text-xs" @click="saveNote(entry)" :disabled="entry.savingNote">Save note</button>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'link-unknown-scan','focusable' => true,'maxWidth' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'link-unknown-scan','focusable' => true,'maxWidth' => 'md']); ?>
                    <form class="p-6 space-y-4" @submit.prevent="submitLink">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            Link to existing asset
                        </h2>
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                            Resolve scanned code
                            <span class="font-medium text-gray-900 dark:text-white" x-text="linkScannedCode"></span>
                            by attaching it to an asset that already exists.
                        </p>

                        <div class="relative">
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'scan-link-asset-search','value' => 'Search assets']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'scan-link-asset-search','value' => 'Search assets']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'scan-link-asset-search','xRef' => 'linkSearch','type' => 'search','class' => 'mt-1 block w-full','xModel' => 'linkQuery','@input' => 'scheduleLinkSearch()','placeholder' => 'Name, barcode, FMI, serial…','autocomplete' => 'off']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'scan-link-asset-search','x-ref' => 'linkSearch','type' => 'search','class' => 'mt-1 block w-full','x-model' => 'linkQuery','@input' => 'scheduleLinkSearch()','placeholder' => 'Name, barcode, FMI, serial…','autocomplete' => 'off']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                            <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-show="linkSearching" x-cloak>Searching…</p>
                            <p class="mt-1 text-xs text-brand" x-show="linkSelectedId" x-cloak>
                                Selected: <span x-text="linkSelectedLabel"></span>
                            </p>
                            <ul
                                class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto border border-brand-silver bg-white shadow-sm dark:border-brand-charcoal dark:bg-brand-slate"
                                x-show="linkResults.length > 0"
                                x-cloak
                            >
                                <template x-for="asset in linkResults" :key="asset.id">
                                    <li>
                                        <button
                                            type="button"
                                            class="block w-full px-3 py-2 text-left text-sm hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal"
                                            @click="pickLinkAsset(asset)"
                                        >
                                            <span x-text="asset.label || asset.name"></span>
                                            <span
                                                class="ml-1 text-xs text-brand-charcoal dark:text-brand-silver"
                                                x-show="asset.item_type"
                                                x-text="asset.item_type"
                                            ></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <p class="mt-2 text-sm text-red-600" x-show="linkError" x-text="linkError" x-cloak></p>
                        </div>

                        <label class="flex items-start gap-2 text-sm" x-show="linkHasEventList" x-cloak>
                            <input type="checkbox" x-model="linkAddToEventList" class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand dark:border-brand-charcoal dark:bg-black">
                            <span>Add asset to the event list from this scan (skip if already on the list)</span>
                        </label>

                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'scan-link-identifier-field','value' => 'Write scanned code to']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'scan-link-identifier-field','value' => 'Write scanned code to']); ?>
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
                            <select
                                id="scan-link-identifier-field"
                                x-model="linkIdentifierField"
                                class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-black dark:text-white focus:border-brand focus:ring-brand rounded-md shadow-sm text-sm"
                            >
                                <option value="">Don’t update an identifier</option>
                                <?php $__currentLoopData = \App\Support\ScannableIdentifierFields::linkTargets(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($field); ?>"><?php echo e(\App\Support\ScannableIdentifierFields::label($field)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                                Replaces the chosen field on the linked asset. Skipped if another asset already uses this code.
                            </p>
                            <p
                                class="mt-1 text-xs text-amber-700 dark:text-amber-300"
                                x-show="linkIdentifierField && linkSelectedFieldCurrentValue()"
                                x-cloak
                            >
                                Current value will be overwritten:
                                <span class="font-medium" x-text="linkSelectedFieldCurrentValue()"></span>
                            </p>
                        </div>

                        <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="linkHasLocation" x-cloak>
                            This scan has a location — the linked asset’s location will be updated (child assets keep their parent’s location).
                        </p>

                        <div class="flex justify-end gap-2 pt-2">
                            <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','xOn:click' => '$dispatch(\'close\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','x-on:click' => '$dispatch(\'close\')']); ?>
                                Cancel
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['type' => 'submit','xBind:disabled' => '!linkSelectedId || linkSaving']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','x-bind:disabled' => '!linkSelectedId || linkSaving']); ?>
                                Link asset
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                        </div>
                    </form>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

                <?php if($session->event_list_id): ?>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold">On event list</h3>
                            <div class="flex items-center gap-2">
                                <a
                                    href="<?php echo e(route('lists.show', $session->event_list_id)); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-xs text-brand underline"
                                >Open list</a>
                                <span class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="eventItems.length + ' item' + (eventItems.length === 1 ? '' : 's')"></span>
                            </div>
                        </div>
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="eventItems.length === 0" x-cloak>No items on this event list yet.</p>
                        <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal border border-brand-silver dark:border-brand-charcoal max-h-72 overflow-y-auto" x-show="eventItems.length > 0">
                            <template x-for="entry in eventItems" :key="'event-' + entry.id">
                                <li class="px-3 py-1.5 text-sm flex items-baseline justify-between gap-2" :class="entry.is_child_expand ? 'pl-6' : ''">
                                    <a
                                        x-show="entry.asset?.url"
                                        :href="entry.asset.url"
                                        class="truncate text-brand underline"
                                        x-text="entry.asset?.name || 'Unknown asset'"
                                    ></a>
                                    <span
                                        x-show="!entry.asset?.url"
                                        class="truncate"
                                        x-text="entry.asset?.name || 'Unknown asset'"
                                    ></span>
                                    <span class="text-xs text-brand-charcoal dark:text-brand-silver whitespace-nowrap shrink-0">
                                        <span x-show="entry.asset?.tp_barcode" x-text="entry.asset.tp_barcode"></span>
                                        <span x-show="!entry.asset?.tp_barcode && entry.asset?.fmi_ast" x-text="entry.asset.fmi_ast"></span>
                                    </span>
                                </li>
                            </template>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex justify-end gap-2">
                <?php if($session->event_list_id): ?>
                    <a href="<?php echo e(route('reports.compare')); ?>?scanned_list_id=<?php echo e($session->event_list_id); ?>&inventory_list_id=<?php echo e($session->inventory_list_id); ?>" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Compare report</a>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('scan.close', $session)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Save &amp; close <?php echo $__env->renderComponent(); ?>
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
            </div>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/scan/index.blade.php ENDPATH**/ ?>
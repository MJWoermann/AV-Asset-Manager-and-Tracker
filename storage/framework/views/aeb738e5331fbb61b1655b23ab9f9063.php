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
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Locations</h2>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('locations:write')): ?>
                <a href="<?php echo e(route('locations.create')); ?>" data-history-roundtrip class="px-3 py-2 bg-brand text-white text-sm">Add</a>
            <?php endif; ?>
        </div>
     <?php $__env->endSlot(); ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="bulkSelection(<?php echo \Illuminate\Support\Js::from([
        'idAttribute' => 'data-bulk-location-id',
        'nameAttribute' => 'data-bulk-location-name',
        'enableMerge' => true,
        'enableTreeCollapse' => true,
        'fields' => [
            'update_parent' => false,
            'clear_parent' => false,
        ],
        'locationOptions' => $locationOptions,
        'initialDeleteLocation' => $initialDeleteLocation,
    ])->toHtml() ?>)">
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

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('locations:update')): ?>
            <?php if(count($duplicateNames) > 0): ?>
                <div class="mb-4 border border-brand-charcoal/30 dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 text-sm space-y-2">
                    <p>
                        <span class="font-medium"><?php echo e(count($duplicateNames)); ?></span>
                        duplicate name<?php echo e(count($duplicateNames) === 1 ? '' : 's'); ?> found:
                        <?php echo e(implode(', ', $duplicateNames)); ?>.
                    </p>
                    <p class="text-brand-charcoal dark:text-brand-silver">
                        Select locations (or use Select duplicates), then choose <span class="font-medium">Merge same names</span> in bulk actions. Assets on merged locations are reassigned to the kept location.
                    </p>
                    <button
                        type="button"
                        class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver"
                        data-duplicate-names="<?php echo e(json_encode($duplicateNames)); ?>"
                        @click="selectDuplicateNames(JSON.parse($el.getAttribute('data-duplicate-names') || '[]'))"
                    >Select duplicates</button>
                </div>
            <?php endif; ?>

            <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
                Deleting a location with no assets asks for confirmation. Deleting a location that has assets asks where those assets should go.
            </p>

            <?php if (isset($component)) { $__componentOriginal814ff6ab62d605e0998112d73e57bd93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal814ff6ab62d605e0998112d73e57bd93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bulk-location-actions','data' => ['parents' => $parentOptions,'locations' => $locationOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bulk-location-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['parents' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parentOptions),'locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locationOptions)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal814ff6ab62d605e0998112d73e57bd93)): ?>
<?php $attributes = $__attributesOriginal814ff6ab62d605e0998112d73e57bd93; ?>
<?php unset($__attributesOriginal814ff6ab62d605e0998112d73e57bd93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal814ff6ab62d605e0998112d73e57bd93)): ?>
<?php $component = $__componentOriginal814ff6ab62d605e0998112d73e57bd93; ?>
<?php unset($__componentOriginal814ff6ab62d605e0998112d73e57bd93); ?>
<?php endif; ?>
        <?php endif; ?>

        <?php if($locations->isEmpty()): ?>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No locations yet.</p>
        <?php else: ?>
            <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('locations:update')): ?>
                    <label class="inline-flex items-center gap-2 mr-2">
                        <input
                            type="checkbox"
                            class="rounded border-brand-charcoal/40"
                            :checked="allPageSelected"
                            @change="toggleAll($event.target.checked)"
                            aria-label="Select all locations"
                        >
                        <span>Select all</span>
                    </label>
                <?php endif; ?>
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="expandAll()"
                >Expand all</button>
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="collapseAll()"
                >Collapse all</button>
                <button
                    type="button"
                    class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver"
                    @click="hideRacks()"
                >Hide racks</button>
            </div>
            <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal text-sm overflow-hidden">
                <?php
                    $previousType = null;
                    $sameTypeRun = 0;
                ?>
                <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        if ($previousType === $location->type) {
                            $sameTypeRun++;
                        } else {
                            $previousType = $location->type;
                            $sameTypeRun = 0;
                        }
                    ?>
                    <?php echo $__env->make('locations.partials.node', [
                        'location' => $location,
                        'alternate' => $sameTypeRun % 2 === 1,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('locations:update')): ?>
            <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'delete-location','show' => $errors->has('replacement_location_id'),'focusable' => true,'maxWidth' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'delete-location','show' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->has('replacement_location_id')),'focusable' => true,'maxWidth' => 'md']); ?>
                <form
                    method="POST"
                    class="p-6 space-y-4"
                    x-bind:action="deleteLocation?.action || '#'"
                >
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <input type="hidden" name="deleting_location_id" x-bind:value="deleteLocation?.id || ''">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “<span x-text="deleteLocation?.name || ''"></span>”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="(deleteLocation?.assetsCount || 0) > 0">
                        This location is used by
                        <span x-text="deleteLocation?.assetsCount || 0"></span>
                        <span x-text="(deleteLocation?.assetsCount || 0) === 1 ? 'asset' : 'assets'"></span>.
                        Choose a replacement location for those assets, then delete.
                        Child locations stay on the list and become top-level.
                    </p>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="(deleteLocation?.assetsCount || 0) === 0">
                        This location has no assets. Delete it?
                        Child locations stay on the list and become top-level.
                    </p>
                    <div x-show="(deleteLocation?.assetsCount || 0) > 0">
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'replacement_location_id','value' => 'Replacement location']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'replacement_location_id','value' => 'Replacement location']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'replacement_location_id','id' => 'replacement_location_id','placeholder' => 'Search by name or room code…','options' => $locationOptions->map(fn ($option) => [
                                'value' => $option['id'],
                                'label' => $option['label'],
                                'keywords' => trim(($option['name'] ?? '').' '.($option['room_code'] ?? '')),
                            ]),'selected' => old('replacement_location_id'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'replacement_location_id','id' => 'replacement_location_id','placeholder' => 'Search by name or room code…','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locationOptions->map(fn ($option) => [
                                'value' => $option['id'],
                                'label' => $option['label'],
                                'keywords' => trim(($option['name'] ?? '').' '.($option['room_code'] ?? '')),
                            ])),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('replacement_location_id')),'required' => true]); ?>
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
                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('replacement_location_id'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('replacement_location_id')),'class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                    </div>
                    <div class="flex justify-end gap-2">
                        <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','xOn:click' => '$dispatch(\'close\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','x-on:click' => '$dispatch(\'close\')']); ?>Cancel <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.danger-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('danger-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Delete permanently <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $attributes = $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $component = $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/locations/index.blade.php ENDPATH**/ ?>
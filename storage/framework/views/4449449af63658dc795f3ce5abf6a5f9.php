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
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Spares register</h2>
            <div class="flex flex-wrap gap-2">
                <a href="<?php echo e(route('spares.groups.index')); ?>" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Groups</a>
                <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                    <a href="<?php echo e(route('spares.create')); ?>" class="px-3 py-2 bg-brand text-white text-sm">Add spare</a>
                <?php endif; ?>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <?php if(session('status')): ?>
            <p class="text-sm text-brand"><?php echo e(session('status')); ?></p>
        <?php endif; ?>

        <?php
            $sparesFiltersActive = filled($term)
                || filled($kind)
                || filled($severity)
                || filled($stockStatus ?? null)
                || ($belowMin ?? false)
                || $includeEol;
        ?>
        <form method="get" class="flex flex-wrap gap-2 items-end text-sm">
            <div class="min-w-[14rem] flex-1 sm:flex-none sm:w-64">
                <label for="spares-search-q" class="block text-xs text-brand-charcoal dark:text-brand-silver">Search</label>
                <?php if (isset($component)) { $__componentOriginal6ba2bee0210b38146ba04d5bb3b73030 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scannable-search','data' => ['id' => 'spares-search-q','name' => 'q','value' => $term,'placeholder' => 'Code, component…','class' => 'mt-1','inputClass' => 'border border-brand-silver dark:border-brand-charcoal px-2 py-1 text-sm','scanAriaLabel' => 'Scan barcode into spares search']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scannable-search'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'spares-search-q','name' => 'q','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($term),'placeholder' => 'Code, component…','class' => 'mt-1','input-class' => 'border border-brand-silver dark:border-brand-charcoal px-2 py-1 text-sm','scan-aria-label' => 'Scan barcode into spares search']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030)): ?>
<?php $attributes = $__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030; ?>
<?php unset($__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6ba2bee0210b38146ba04d5bb3b73030)): ?>
<?php $component = $__componentOriginal6ba2bee0210b38146ba04d5bb3b73030; ?>
<?php unset($__componentOriginal6ba2bee0210b38146ba04d5bb3b73030); ?>
<?php endif; ?>
            </div>
            <div class="min-w-[9rem]">
                <label class="block text-xs text-brand-charcoal dark:text-brand-silver" for="spares_kind">Kind</label>
                <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'kind','id' => 'spares_kind','options' => collect(\App\Enums\SparePartKind::options())->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),'selected' => $kind,'placeholder' => 'All','nullable' => true,'autoSubmit' => true,'compact' => true,'ariaLabel' => 'Filter by kind']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'kind','id' => 'spares_kind','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(collect(\App\Enums\SparePartKind::options())->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kind),'placeholder' => 'All','nullable' => true,'auto-submit' => true,'compact' => true,'aria-label' => 'Filter by kind']); ?>
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
            <div class="min-w-[9rem]">
                <label class="block text-xs text-brand-charcoal dark:text-brand-silver" for="spares_severity">Severity</label>
                <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'severity','id' => 'spares_severity','options' => collect(\App\Enums\SparePartSeverity::options())->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),'selected' => $severity,'placeholder' => 'All','nullable' => true,'autoSubmit' => true,'compact' => true,'ariaLabel' => 'Filter by severity']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'severity','id' => 'spares_severity','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(collect(\App\Enums\SparePartSeverity::options())->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($severity),'placeholder' => 'All','nullable' => true,'auto-submit' => true,'compact' => true,'aria-label' => 'Filter by severity']); ?>
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
            <div class="min-w-[9rem]">
                <label class="block text-xs text-brand-charcoal dark:text-brand-silver" for="spares_stock_status">Stock status</label>
                <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'stock_status','id' => 'spares_stock_status','options' => collect(\App\Enums\SparePartStockStatus::options())->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),'selected' => $stockStatus ?? null,'placeholder' => 'All','nullable' => true,'autoSubmit' => true,'compact' => true,'ariaLabel' => 'Filter by stock status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'stock_status','id' => 'spares_stock_status','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(collect(\App\Enums\SparePartStockStatus::options())->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stockStatus ?? null),'placeholder' => 'All','nullable' => true,'auto-submit' => true,'compact' => true,'aria-label' => 'Filter by stock status']); ?>
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
            <label class="flex items-center gap-2 pb-1">
                <input type="checkbox" name="below_min" value="1" <?php if($belowMin ?? false): echo 'checked'; endif; ?> onchange="this.form.requestSubmit()">
                <span>Below minimum</span>
            </label>
            <label class="flex items-center gap-2 pb-1">
                <input type="checkbox" name="include_eol" value="1" <?php if($includeEol): echo 'checked'; endif; ?> onchange="this.form.requestSubmit()">
                <span>Include EOL / superseded</span>
            </label>
            <button type="submit" class="px-3 py-1 bg-black text-white">Search</button>
            <?php if (isset($component)) { $__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.clear-filters','data' => ['href' => route('spares.index'),'active' => $sparesFiltersActive,'class' => '!px-3 !py-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('clear-filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('spares.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sparesFiltersActive),'class' => '!px-3 !py-1']); ?>
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

        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                <form id="batch-print-form" method="post" action="<?php echo e(route('spares.labels.print')); ?>" class="text-sm border border-brand-silver dark:border-brand-charcoal p-3 space-y-2">
                    <?php echo csrf_field(); ?>
                    <p class="font-medium">Print box labels (tick rows in the table)</p>
                    <div class="flex flex-wrap gap-2 items-end">
                        <div>
                            <label class="block text-xs">Layout</label>
                            <select name="shelf_tag_layout_id" required class="border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                                <?php $__currentLoopData = \App\Models\ShelfTagLayout::query()->orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $layout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($layout->id); ?>"><?php echo e($layout->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <button type="submit" class="px-3 py-1 border border-brand">Print HTML</button>
                        <button type="submit" name="format" value="pdf" class="px-3 py-1 bg-brand text-white">PDF</button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <div class="overflow-x-auto border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate text-sm">
            <table class="min-w-full">
                <thead class="bg-brand-charcoal/5 dark:bg-brand-charcoal text-left">
                    <tr>
                        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                                <th class="p-2 w-8"></th>
                            <?php endif; ?>
                        <?php endif; ?>
                        <th class="p-2">Code</th>
                        <th class="p-2">Component</th>
                        <th class="p-2">Kind</th>
                        <th class="p-2">On hand</th>
                        <th class="p-2">Min/Max</th>
                        <th class="p-2">Severity</th>
                        <th class="p-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $parts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal hover:bg-brand/5">
                            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                                <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                                    <td class="p-2">
                                        <input type="checkbox" name="spare_ids[]" value="<?php echo e($part->id); ?>" form="batch-print-form">
                                    </td>
                                <?php endif; ?>
                            <?php endif; ?>
                            <td class="p-2 font-mono">
                                <a href="<?php echo e(route('spares.show', $part)); ?>" class="text-brand hover:underline"><?php echo e($part->code); ?></a>
                            </td>
                            <td class="p-2"><?php echo e($part->component); ?></td>
                            <td class="p-2"><?php echo e($part->kind?->label()); ?></td>
                            <td class="p-2 <?php if($part->isBelowMin()): ?> text-red-600 font-semibold <?php endif; ?>"><?php echo e($part->quantity_on_hand); ?></td>
                            <td class="p-2"><?php echo e($part->min_holding ?? '—'); ?> / <?php echo e($part->max_holding ?? '—'); ?></td>
                            <td class="p-2"><?php echo e($part->severity?->label()); ?></td>
                            <td class="p-2">
                                <?php echo e($part->lifecycle_status?->label()); ?>

                                <?php if($part->supersededBy): ?>
                                    <span class="text-xs block text-brand-charcoal dark:text-brand-silver">→ <?php echo e($part->supersededBy->code); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8" class="p-4 text-brand-charcoal dark:text-brand-silver">No spare lines match your filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php echo e($parts->links()); ?>

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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/spares/index.blade.php ENDPATH**/ ?>
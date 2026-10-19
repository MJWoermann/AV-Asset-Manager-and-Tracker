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
            <h2 class="font-semibold text-xl">Assets</h2>
            <div class="flex gap-2 items-center">
                <?php if (isset($component)) { $__componentOriginale38ded69f40ce1c2c90acaee9ee96445 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale38ded69f40ce1c2c90acaee9ee96445 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset-column-picker','data' => ['availableColumns' => $availableColumns,'selectedColumns' => $selectedColumns]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset-column-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['available-columns' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($availableColumns),'selected-columns' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedColumns)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale38ded69f40ce1c2c90acaee9ee96445)): ?>
<?php $attributes = $__attributesOriginale38ded69f40ce1c2c90acaee9ee96445; ?>
<?php unset($__attributesOriginale38ded69f40ce1c2c90acaee9ee96445); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale38ded69f40ce1c2c90acaee9ee96445)): ?>
<?php $component = $__componentOriginale38ded69f40ce1c2c90acaee9ee96445; ?>
<?php unset($__componentOriginale38ded69f40ce1c2c90acaee9ee96445); ?>
<?php endif; ?>
                <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                    <a href="<?php echo e(route('assets.duplicates.index')); ?>" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Duplicates</a>
                    <a href="<?php echo e(route('import.create')); ?>" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Import</a>
                    <a href="<?php echo e(route('export.assets', ['format' => 'xlsx'])); ?>" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Export</a>
                <?php endif; ?>
                <a href="<?php echo e(route('assets.create')); ?>" class="text-sm px-3 py-2 bg-brand text-white">Add asset</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="bulkSelection(<?php echo \Illuminate\Support\Js::from([
        'idAttribute' => 'data-bulk-asset-id',
        'fields' => [
            'update_status' => false,
            'update_location' => false,
            'clear_location' => false,
            'update_parent' => false,
            'clear_parent' => false,
            'update_department' => false,
            'update_test_tag_expiry' => false,
            'clear_test_tag_expiry' => false,
            'update_warranty_expiry' => false,
            'clear_warranty_expiry' => false,
            'update_notes' => false,
            'clear_notes' => false,
        ],
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

        <form id="asset-filters" method="GET" class="mb-4 flex flex-col sm:flex-row gap-2 sm:items-start">
            <?php if(request()->filled(\App\Support\PageSize::PARAM)): ?>
                <input type="hidden" name="<?php echo e(\App\Support\PageSize::PARAM); ?>" value="<?php echo e(request(\App\Support\PageSize::PARAM)); ?>">
            <?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal6ba2bee0210b38146ba04d5bb3b73030 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scannable-search','data' => ['id' => 'asset-search-q','name' => 'q','value' => $q,'placeholder' => 'Search name, location, FMI, barcode, SN…','class' => 'flex-1','ariaLabel' => 'Search assets','scanAriaLabel' => 'Scan barcode into asset search']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scannable-search'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'asset-search-q','name' => 'q','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($q),'placeholder' => 'Search name, location, FMI, barcode, SN…','class' => 'flex-1','aria-label' => 'Search assets','scan-aria-label' => 'Scan barcode into asset search']); ?>
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
            <button type="submit" class="px-4 py-2 bg-black text-white dark:bg-brand shrink-0">Search</button>
            <?php if (isset($component)) { $__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.clear-filters','data' => ['href' => route('assets.index'),'active' => \App\Support\AssetBrowseFilters::hasActiveFilters(request(), $selectedColumns)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('clear-filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('assets.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Support\AssetBrowseFilters::hasActiveFilters(request(), $selectedColumns))]); ?>
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

        <?php if (isset($component)) { $__componentOriginal9ffcf2017585465c912ad277f9f14b73 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ffcf2017585465c912ad277f9f14b73 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bulk-asset-actions','data' => ['statuses' => $statuses,'locations' => $locations,'parents' => $parents,'lists' => $lists,'departments' => $departments]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bulk-asset-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['statuses' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statuses),'locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations),'parents' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parents),'lists' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($lists),'departments' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($departments)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ffcf2017585465c912ad277f9f14b73)): ?>
<?php $attributes = $__attributesOriginal9ffcf2017585465c912ad277f9f14b73; ?>
<?php unset($__attributesOriginal9ffcf2017585465c912ad277f9f14b73); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ffcf2017585465c912ad277f9f14b73)): ?>
<?php $component = $__componentOriginal9ffcf2017585465c912ad277f9f14b73; ?>
<?php unset($__componentOriginal9ffcf2017585465c912ad277f9f14b73); ?>
<?php endif; ?>

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2 w-10" aria-hidden="true"></th>
                        <?php $__currentLoopData = $selectedColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th class="px-3 py-2"><?php echo e(\App\Support\AssetTableColumns::label($column)); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                    <?php if (isset($component)) { $__componentOriginal1801d267616eb6cd3630dd30196134f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1801d267616eb6cd3630dd30196134f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset-column-filters','data' => ['selectedColumns' => $selectedColumns,'statuses' => $statuses,'locations' => $locations,'itemTypes' => $itemTypes,'manufacturers' => $manufacturers,'manufacturerModels' => $manufacturerModels]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset-column-filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['selected-columns' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedColumns),'statuses' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statuses),'locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations),'item-types' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($itemTypes),'manufacturers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($manufacturers),'manufacturer-models' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($manufacturerModels)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1801d267616eb6cd3630dd30196134f2)): ?>
<?php $attributes = $__attributesOriginal1801d267616eb6cd3630dd30196134f2; ?>
<?php unset($__attributesOriginal1801d267616eb6cd3630dd30196134f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1801d267616eb6cd3630dd30196134f2)): ?>
<?php $component = $__componentOriginal1801d267616eb6cd3630dd30196134f2; ?>
<?php unset($__componentOriginal1801d267616eb6cd3630dd30196134f2); ?>
<?php endif; ?>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $assets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $bulkAssetLabel = $asset->name;
                            $bulkLabelParts = array_filter([$asset->tp_barcode, $asset->fmi_ast, $asset->rig_tag]);
                            if ($bulkLabelParts !== []) {
                                $bulkAssetLabel .= ' ('.implode(' · ', $bulkLabelParts).')';
                            }
                        ?>
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2">
                                <input
                                    type="checkbox"
                                    class="rounded border-brand-charcoal/40"
                                    data-bulk-asset-id="<?php echo e($asset->id); ?>"
                                    data-bulk-asset-label="<?php echo e($bulkAssetLabel); ?>"
                                    :checked="!!selected[<?php echo e($asset->id); ?>]"
                                    @change="setSelected(<?php echo e($asset->id); ?>, $event.target.checked)"
                                    aria-label="Select <?php echo e($asset->name); ?>"
                                >
                            </td>
                            <?php $__currentLoopData = $selectedColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if (isset($component)) { $__componentOriginal1d16dd3b01f7f2b7cebb6af032ca183a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1d16dd3b01f7f2b7cebb6af032ca183a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset-table-cell','data' => ['asset' => $asset,'column' => $column,'term' => $q,'selectedColumns' => $selectedColumns]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset-table-cell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['asset' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($asset),'column' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($column),'term' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($q),'selected-columns' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedColumns)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1d16dd3b01f7f2b7cebb6af032ca183a)): ?>
<?php $attributes = $__attributesOriginal1d16dd3b01f7f2b7cebb6af032ca183a; ?>
<?php unset($__attributesOriginal1d16dd3b01f7f2b7cebb6af032ca183a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1d16dd3b01f7f2b7cebb6af032ca183a)): ?>
<?php $component = $__componentOriginal1d16dd3b01f7f2b7cebb6af032ca183a; ?>
<?php unset($__componentOriginal1d16dd3b01f7f2b7cebb6af032ca183a); ?>
<?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="<?php echo e(count($selectedColumns) + 1); ?>" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No assets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (isset($component)) { $__componentOriginal6e24cf2ff912fed75ae4f2edca067c58 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e24cf2ff912fed75ae4f2edca067c58 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.paginated-results','data' => ['paginator' => $assets]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('paginated-results'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($assets)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6e24cf2ff912fed75ae4f2edca067c58)): ?>
<?php $attributes = $__attributesOriginal6e24cf2ff912fed75ae4f2edca067c58; ?>
<?php unset($__attributesOriginal6e24cf2ff912fed75ae4f2edca067c58); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6e24cf2ff912fed75ae4f2edca067c58)): ?>
<?php $component = $__componentOriginal6e24cf2ff912fed75ae4f2edca067c58; ?>
<?php unset($__componentOriginal6e24cf2ff912fed75ae4f2edca067c58); ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/index.blade.php ENDPATH**/ ?>
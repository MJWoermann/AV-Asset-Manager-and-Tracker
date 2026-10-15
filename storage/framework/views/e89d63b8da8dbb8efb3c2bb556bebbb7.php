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
            <div>
                <h2 class="font-semibold text-xl"><?php echo e($list->name); ?></h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver capitalize"><?php echo e($list->type); ?> list</p>
            </div>
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
                    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                        <a href="<?php echo e(route('shelf-tags.index', ['asset_list_id' => $list->id])); ?>" class="px-3 py-2 border text-sm">Shelf tags</a>
                    <?php endif; ?>
                    <a href="<?php echo e(route('export.list', [$list, 'format' => 'xlsx'])); ?>" class="px-3 py-2 border text-sm">Export</a>
                    <a href="<?php echo e(route('lists.edit', $list)); ?>" class="px-3 py-2 border text-sm">Edit</a>
                    <form
                        method="POST"
                        action="<?php echo e(route('lists.destroy', $list)); ?>"
                        class="inline"
                        onsubmit="return confirm('Delete this list? List membership is removed (assets are kept). Event lists also delete related scan sessions. Unknown-scan history may lose its list linkage.')"
                    >
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="px-3 py-2 border text-sm text-red-700">Delete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="bulkAssets()">
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

        <form id="asset-filters" method="GET" class="mb-4 flex flex-col sm:flex-row gap-2">
            <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Search FMI, barcode, SN, IP, MAC…" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white">
            <?php $__currentLoopData = $preservedFilters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $param => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="hidden" name="<?php echo e($param); ?>" value="<?php echo e($value); ?>">
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <button class="px-4 py-2 bg-black text-white dark:bg-brand">Filter</button>
            <?php if(\App\Support\AssetBrowseFilters::hasActiveFilters(request())): ?>
                <a href="<?php echo e(route('lists.show', $list)); ?>" class="px-4 py-2 border text-sm inline-flex items-center">Clear</a>
            <?php endif; ?>
        </form>

        <?php if (isset($component)) { $__componentOriginal9ffcf2017585465c912ad277f9f14b73 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ffcf2017585465c912ad277f9f14b73 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bulk-asset-actions','data' => ['statuses' => $statuses,'locations' => $locations,'parents' => $parents,'lists' => $lists,'currentList' => $list]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bulk-asset-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['statuses' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statuses),'locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations),'parents' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($parents),'lists' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($lists),'current-list' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($list)]); ?>
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
                        <th class="px-3 py-2 w-10">
                            <input
                                type="checkbox"
                                class="rounded border-brand-charcoal/40"
                                :checked="allPageSelected"
                                @click.prevent="toggleAll(!allPageSelected)"
                                aria-label="Select all assets on this page"
                            >
                        </th>
                        <?php $__currentLoopData = $selectedColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th class="px-3 py-2"><?php echo e(\App\Support\AssetTableColumns::label($column)); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                    <?php if (isset($component)) { $__componentOriginal1801d267616eb6cd3630dd30196134f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1801d267616eb6cd3630dd30196134f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset-column-filters','data' => ['selectedColumns' => $selectedColumns,'statuses' => $statuses,'locations' => $locations,'itemTypes' => $itemTypes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset-column-filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['selected-columns' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedColumns),'statuses' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statuses),'locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations),'item-types' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($itemTypes)]); ?>
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
                    <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $indent = $item->is_child_expand || $item->asset?->parent_id;
                        ?>
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2 <?php echo e($indent ? 'pl-8' : ''); ?>">
                                <?php if($item->asset): ?>
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        data-bulk-asset-id="<?php echo e($item->asset->id); ?>"
                                        :checked="!!selected[<?php echo e($item->asset->id); ?>]"
                                        @change="setSelected(<?php echo e($item->asset->id); ?>, $event.target.checked)"
                                        aria-label="Select <?php echo e($item->asset->name); ?>"
                                    >
                                <?php endif; ?>
                            </td>
                            <?php $__currentLoopData = $selectedColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($item->asset): ?>
                                    <?php if (isset($component)) { $__componentOriginal1d16dd3b01f7f2b7cebb6af032ca183a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1d16dd3b01f7f2b7cebb6af032ca183a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset-table-cell','data' => ['asset' => $item->asset,'column' => $column,'item' => $item,'indent' => $indent,'term' => $q,'selectedColumns' => $selectedColumns]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset-table-cell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['asset' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->asset),'column' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($column),'item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item),'indent' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($indent),'term' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($q),'selected-columns' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedColumns)]); ?>
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
                                <?php else: ?>
                                    <td class="px-3 py-2">—</td>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="<?php echo e(count($selectedColumns) + 1); ?>" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No items yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><?php echo e($items->links()); ?></div>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/lists/show.blade.php ENDPATH**/ ?>
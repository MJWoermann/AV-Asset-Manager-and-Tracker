<?php
    $comparisonSections = null;
    if ($result) {
        $mapRows = static function ($rows) {
            return $rows->map(fn ($row) => [
                'name' => (string) $row->name,
                'parent_id' => $row->parent_id,
                'code' => (string) ($row->tp_barcode ?? $row->fmi_ast ?? $row->rig_tag ?? $row->serial_number ?? ''),
                'haystack' => trim(implode(' ', array_filter([
                    $row->name,
                    $row->tp_barcode ?? null,
                    $row->fmi_ast ?? null,
                    $row->rig_tag ?? null,
                    $row->serial_number ?? null,
                ]))),
            ])->values()->all();
        };

        $comparisonSections = [
            ['key' => 'matched', 'title' => 'Matched', 'rows' => $mapRows($result['matched'])],
            ['key' => 'inventory_only', 'title' => 'Inventory only', 'rows' => $mapRows($result['inventory_only'])],
            ['key' => 'scanned_only', 'title' => 'Scanned only (not on inventory)', 'rows' => $mapRows($result['scanned_only'])],
        ];
    }
?>

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
        <h2 class="font-semibold text-xl">Compare lists</h2>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <form method="POST" action="<?php echo e(route('reports.compare.run')); ?>" class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white dark:bg-brand-slate border p-4">
            <?php echo csrf_field(); ?>
            <div>
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'scanned_list_id','value' => 'Scanned / event list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'scanned_list_id','value' => 'Scanned / event list']); ?>
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
                <select id="scanned_list_id" name="scanned_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    <?php $__currentLoopData = $lists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($list->id); ?>" <?php if(old('scanned_list_id', request('scanned_list_id')) == $list->id): echo 'selected'; endif; ?>><?php echo e($list->name); ?> (<?php echo e($list->type); ?>)</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'inventory_list_id','value' => 'Inventory / source list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'inventory_list_id','value' => 'Inventory / source list']); ?>
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
                <select id="inventory_list_id" name="inventory_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    <?php $__currentLoopData = $lists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($list->id); ?>" <?php if(old('inventory_list_id', request('inventory_list_id')) == $list->id): echo 'selected'; endif; ?>><?php echo e($list->name); ?> (<?php echo e($list->type); ?>)</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3">
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Run comparison <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-brand-charcoal dark:text-brand-silver">Export</span>
                    <button name="export" value="xlsx" class="px-4 py-2 border text-sm">XLSX</button>
                    <button name="export" value="csv" class="px-4 py-2 border text-sm">CSV</button>
                    <button name="export" value="pdf" class="px-4 py-2 border text-sm">PDF</button>
                </div>
            </div>
        </form>

        <?php if($comparisonSections): ?>
            <div x-data="comparisonSearch(<?php echo \Illuminate\Support\Js::from($comparisonSections)->toHtml() ?>)" class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver"><?php echo e($source->name); ?> vs <?php echo e($target->name); ?></p>
                    <input
                        type="search"
                        x-model="query"
                        placeholder="Filter results by name, barcode, FMI, SN…"
                        class="w-full sm:max-w-md rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                </div>

                <template x-for="section in sections" :key="section.key">
                    <section>
                        <h3 class="font-semibold mb-2">
                            <span x-text="section.title"></span>
                            (<span x-text="visibleCount(section)"></span>)
                        </h3>
                        <ul class="bg-white dark:bg-brand-slate border divide-y text-sm max-h-80 overflow-y-auto">
                            <template x-for="(row, index) in section.rows" :key="section.key + '-' + index">
                                <li
                                    class="px-3 py-2"
                                    :class="row.parent_id ? 'pl-8' : ''"
                                    x-show="rowMatches(row.haystack)"
                                >
                                    <span x-html="highlight(row.name)"></span>
                                    <span
                                        class="text-brand-charcoal dark:text-brand-silver font-mono"
                                        x-show="row.code"
                                        x-html="row.code ? (' ' + highlight(row.code)) : ''"
                                    ></span>
                                </li>
                            </template>
                            <li
                                class="px-3 py-4 text-brand-charcoal dark:text-brand-silver"
                                x-show="visibleCount(section) === 0"
                            >None</li>
                        </ul>
                    </section>
                </template>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/reports/compare.blade.php ENDPATH**/ ?>
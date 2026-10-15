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
            <h2 class="font-semibold text-xl">Print shelf tags</h2>
            <a href="<?php echo e(route('shelf-tags.layouts.index')); ?>" class="text-sm text-brand-charcoal dark:text-brand-silver">Manage layouts</a>
        </div>
     <?php $__env->endSlot(); ?>

    <?php
        $assetRows = $listAssets->map(fn ($asset) => [
            'id' => $asset->id,
            'name' => $asset->name,
            'tp_barcode' => $asset->tp_barcode,
            'fmi_ast' => $asset->fmi_ast,
            'status' => $asset->status instanceof \BackedEnum ? $asset->status->value : (string) $asset->status,
            'item_type' => $asset->itemType?->name,
        ])->values()->all();
    ?>

    <div
        class="max-w-5xl mx-auto px-4"
        x-data="shelfTagPrintSetup(<?php echo \Illuminate\Support\Js::from($assetRows)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($preselectedIds)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($maxBatch)->toHtml() ?>)"
    >
        <?php if($errors->any()): ?>
            <div class="mb-4 text-sm text-red-700 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php if($layouts->isEmpty()): ?>
            <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
                Create a <a href="<?php echo e(route('shelf-tags.layouts.create')); ?>" class="text-brand underline">shelf tag layout</a> before printing.
            </p>
        <?php endif; ?>

        <form
            method="GET"
            action="<?php echo e(route('shelf-tags.print.create')); ?>"
            class="mb-4 space-y-4 bg-white dark:bg-brand-slate border p-4"
            id="shelf-tag-print-filters"
        >
            <?php if($selectedLayoutId): ?>
                <input type="hidden" name="shelf_tag_layout_id" value="<?php echo e($selectedLayoutId); ?>">
            <?php endif; ?>

            <div>
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'asset_list_id_filter','value' => 'List']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'asset_list_id_filter','value' => 'List']); ?>
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
                    id="asset_list_id_filter"
                    name="asset_list_id"
                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="">Select list…</option>
                    <?php $__currentLoopData = $lists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($list->id); ?>" <?php if($selectedListId == $list->id): echo 'selected'; endif; ?>>
                            <?php echo e($list->name); ?> (<?php echo e($list->type); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <?php if($selectedListId): ?>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input
                        type="search"
                        name="q"
                        value="<?php echo e($q); ?>"
                        placeholder="Search FMI, barcode, SN, IP, MAC…"
                        class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                    <button type="submit" class="px-4 py-2 bg-black text-white dark:bg-brand text-sm">Filter</button>
                    <?php if($q !== ''): ?>
                        <a
                            href="<?php echo e(route('shelf-tags.print.create', array_filter(['asset_list_id' => $selectedListId, 'shelf_tag_layout_id' => $selectedLayoutId]))); ?>"
                            class="px-4 py-2 border text-sm inline-flex items-center justify-center"
                        >Clear</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>

        <form method="POST" action="<?php echo e(route('shelf-tags.print.preview')); ?>" class="space-y-4 bg-white dark:bg-brand-slate border p-4" @submit="prepareSubmit($event)">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="asset_list_id" value="<?php echo e($selectedListId); ?>">
            <input type="hidden" name="mode" value="selected">

            <div>
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'shelf_tag_layout_id','value' => 'Layout']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'shelf_tag_layout_id','value' => 'Layout']); ?>
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
                <select id="shelf_tag_layout_id" name="shelf_tag_layout_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    <option value="">Select layout…</option>
                    <?php $__currentLoopData = $layouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $layout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($layout->id); ?>" <?php if($selectedLayoutId == $layout->id): echo 'selected'; endif; ?>>
                            <?php echo e($layout->name); ?> (<?php echo e($layout->width_mm); ?>×<?php echo e($layout->height_mm); ?> mm)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('shelf_tag_layout_id'),'class' => 'mt-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('shelf_tag_layout_id')),'class' => 'mt-1']); ?>
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

            <template x-for="id in selectedIds" :key="'sel-'+id">
                <input type="hidden" name="asset_ids[]" :value="id">
            </template>

            <?php if(! $selectedListId): ?>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Choose a list above to load assets and select which labels to print.
                </p>
            <?php elseif($listAssets->isEmpty()): ?>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    <?php if($q !== ''): ?>
                        No assets on this list match your search.
                    <?php else: ?>
                        This list has no assets to print.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-medium">Assets on list</h3>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                            Showing <?php echo e($listAssets->count()); ?>

                            <?php if($listAssetTruncated): ?>
                                of <?php echo e($listAssetTotal); ?> matching
                            <?php else: ?>
                                of <?php echo e($listAssetTotal); ?>

                            <?php endif; ?>
                            · max <?php echo e($maxBatch); ?> labels per batch
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <button type="button" class="px-3 py-1.5 border" @click="selectVisible()">Select visible</button>
                        <button type="button" class="px-3 py-1.5 border" @click="clearVisible()">Clear visible</button>
                        <button type="button" class="px-3 py-1.5 border" @click="clearSelection()">Clear all</button>
                    </div>
                </div>

                <?php if($listAssetTruncated): ?>
                    <p class="text-xs text-amber-800 dark:text-amber-200">
                        Only the first <?php echo e(\App\Http\Controllers\ShelfTagPrintController::SELECTION_DISPLAY_CAP); ?> matching assets are listed. Refine search to find others.
                    </p>
                <?php elseif($listAssetTotal > $maxBatch): ?>
                    <p class="text-xs text-amber-800 dark:text-amber-200">
                        This list has more than <?php echo e($maxBatch); ?> assets. Select at most <?php echo e($maxBatch); ?> to print.
                    </p>
                <?php endif; ?>

                <div>
                    <input
                        type="search"
                        x-model="filter"
                        placeholder="Filter visible rows…"
                        class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        aria-label="Filter visible assets"
                    >
                </div>

                <p class="text-sm">
                    <span class="font-semibold" x-text="selectedCount"></span>
                    selected
                    <span class="text-brand-charcoal dark:text-brand-silver" x-show="overLimit" x-cloak>
                        · too many (max <span x-text="maxBatch"></span>)
                    </span>
                </p>
                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('asset_ids'),'class' => 'mt-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('asset_ids')),'class' => 'mt-1']); ?>
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

                <div class="overflow-x-auto border border-brand-silver dark:border-brand-charcoal max-h-[28rem]">
                    <table class="min-w-full text-sm">
                        <thead class="bg-brand-silver dark:bg-brand-charcoal text-left sticky top-0">
                            <tr>
                                <th class="px-3 py-2 w-10">
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        :checked="allVisibleSelected"
                                        @click.prevent="toggleVisible(!allVisibleSelected)"
                                        aria-label="Select all visible assets"
                                    >
                                </th>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Barcode</th>
                                <th class="px-3 py-2">FMI</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="asset in visibleAssets" :key="asset.id">
                                <tr class="border-t border-brand-silver/60 dark:border-brand-charcoal">
                                    <td class="px-3 py-2">
                                        <input
                                            type="checkbox"
                                            class="rounded border-brand-charcoal/40"
                                            :checked="!!selected[asset.id]"
                                            @change="setSelected(asset.id, $event.target.checked)"
                                            :aria-label="'Select ' + asset.name"
                                        >
                                    </td>
                                    <td class="px-3 py-2" x-text="asset.name"></td>
                                    <td class="px-3 py-2 font-mono text-xs" x-text="asset.tp_barcode || '—'"></td>
                                    <td class="px-3 py-2 font-mono text-xs" x-text="asset.fmi_ast || '—'"></td>
                                    <td class="px-3 py-2" x-text="asset.item_type || '—'"></td>
                                    <td class="px-3 py-2" x-text="asset.status || '—'"></td>
                                </tr>
                            </template>
                            <tr x-show="visibleAssets.length === 0" x-cloak>
                                <td colspan="6" class="px-3 py-4 text-brand-charcoal dark:text-brand-silver">
                                    No visible rows match this filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                
                <noscript>
                    <ul class="mt-3 text-sm space-y-1">
                        <?php $__currentLoopData = $listAssets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="asset_ids[]" value="<?php echo e($asset->id); ?>" <?php if(in_array($asset->id, $preselectedIds, true)): echo 'checked'; endif; ?>>
                                    <?php echo e($asset->name); ?>

                                    <?php if($asset->tp_barcode): ?>
                                        <span class="font-mono text-xs">(<?php echo e($asset->tp_barcode); ?>)</span>
                                    <?php endif; ?>
                                </label>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </noscript>
            <?php endif; ?>

            <div class="flex flex-wrap items-center gap-3">
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['xBind:disabled' => '!canSubmit','xBind:class' => '!canSubmit ? \'opacity-50 cursor-not-allowed\' : \'\'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-bind:disabled' => '!canSubmit','x-bind:class' => '!canSubmit ? \'opacity-50 cursor-not-allowed\' : \'\'']); ?>Preview labels <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Browser print is primary; PDF is available from the preview screen.
                </p>
            </div>
            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('asset_list_id'),'class' => 'mt-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('asset_list_id')),'class' => 'mt-1']); ?>
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
        </form>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/shelf-tags/print/create.blade.php ENDPATH**/ ?>
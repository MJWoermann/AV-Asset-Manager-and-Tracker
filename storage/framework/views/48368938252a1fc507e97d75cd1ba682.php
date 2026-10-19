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
            <h2 class="font-semibold text-xl">Merge assets</h2>
            <a href="<?php echo e(route('assets.duplicates.index')); ?>" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Back to duplicates</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
        x-data="assetMerge(<?php echo \Illuminate\Support\Js::from([
            'survivorId' => $assets->first()->id,
            'emptySource' => $emptySource,
            'fields' => $defaultFields,
            'customFields' => $defaultCustomFields,
            'assetIds' => $assets->pluck('id')->values(),
            'quantityTotal' => $quantityTotal,
            'setQuantityToTotal' => (bool) old('set_quantity_to_total', false),
        ])->toHtml() ?>)"
    >
        <?php if($errors->any()): ?>
            <div class="text-sm text-red-700 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Choose which asset survives (keeps its ID). For each field, pick which column’s value to keep, or clear it.
            Conflicting values are highlighted. Non-survivor assets are deleted after related records are reassigned.
        </p>

        <form method="POST" action="<?php echo e(route('assets.duplicates.merge.store')); ?>" class="space-y-4">
            <?php echo csrf_field(); ?>
            <?php $__currentLoopData = $assets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="hidden" name="asset_ids[]" value="<?php echo e($asset->id); ?>">
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <input type="hidden" name="survivor_id" :value="survivorId">
            <input type="hidden" name="confirm_merge" value="1">

            <label class="flex items-start gap-3 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 text-sm cursor-pointer">
                <input
                    type="checkbox"
                    name="set_quantity_to_total"
                    value="1"
                    class="mt-1 rounded border-brand-charcoal/40"
                    x-model="setQuantityToTotal"
                >
                <span>
                    <span class="font-medium block">Set quantity to total of merged assets</span>
                    <span class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Survivor quantity becomes
                        <span class="font-mono font-semibold" x-text="quantityTotal"></span>
                        (sum of each selected asset’s quantity; blank/zero counts as 1). Overrides the Quantity row below.
                    </span>
                </span>
            </label>

            <div class="overflow-x-auto border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
                <table class="min-w-full text-sm">
                    <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                        <tr>
                            <th class="px-3 py-2 sticky left-0 z-10 bg-brand-silver dark:bg-brand-charcoal min-w-[10rem]">Field</th>
                            <?php $__currentLoopData = $assets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th class="px-3 py-2 min-w-[12rem] align-top">
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="survivor_preview"
                                            class="mt-1"
                                            value="<?php echo e($asset->id); ?>"
                                            x-model.number="survivorId"
                                        >
                                        <span>
                                            <span class="font-medium block"><?php echo e($asset->name); ?></span>
                                            <span class="text-xs text-brand-charcoal dark:text-brand-silver">#<?php echo e($asset->id); ?> · survivor when selected · qty <?php echo e(max(1, (int) ($asset->quantity ?? 1))); ?></span>
                                        </span>
                                    </label>
                                </th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <th class="px-3 py-2 min-w-[7rem]">Clear</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $key = $row['key'];
                                $isCustom = ($row['type'] ?? '') === 'custom';
                                $definitionId = $row['definition_id'] ?? null;
                                $inputName = $isCustom
                                    ? 'custom_fields['.$definitionId.']'
                                    : 'fields['.$key.']';
                                $modelKey = $isCustom ? 'customFields[\''.$definitionId.'\']' : 'fields[\''.$key.'\']';
                                $conflict = $valueMatrix[$key]['_conflict'] ?? false;
                                $allBlank = $valueMatrix[$key]['_all_blank'] ?? false;
                                $isQuantityRow = $key === 'quantity';
                            ?>
                            <tr
                                class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                    'border-t border-brand-silver dark:border-brand-charcoal',
                                    'bg-amber-50 dark:bg-amber-950/30' => $conflict && ! $isQuantityRow,
                                ]); ?>"
                                <?php if($isQuantityRow): ?>
                                    :class="setQuantityToTotal ? 'opacity-50' : ''"
                                <?php endif; ?>
                            >
                                <td class="px-3 py-2 sticky left-0 z-10 bg-white dark:bg-brand-slate font-medium align-top">
                                    <?php echo e($row['label']); ?>

                                    <?php if($isQuantityRow): ?>
                                        <span class="block text-xs font-normal text-brand" x-show="setQuantityToTotal" x-cloak>
                                            Forced to total (<span x-text="quantityTotal"></span>)
                                        </span>
                                    <?php elseif($conflict): ?>
                                        <span class="block text-xs font-normal text-amber-800 dark:text-amber-200">Conflict</span>
                                    <?php elseif($allBlank): ?>
                                        <span class="block text-xs font-normal text-brand-charcoal dark:text-brand-silver">All empty</span>
                                    <?php endif; ?>
                                    <?php if(! $isQuantityRow): ?>
                                        <input type="hidden" name="<?php echo e($inputName); ?>" :value="<?php echo e($modelKey); ?>">
                                    <?php else: ?>
                                        <template x-if="!setQuantityToTotal">
                                            <input type="hidden" name="fields[quantity]" :value="fields['quantity']">
                                        </template>
                                    <?php endif; ?>
                                </td>
                                <?php $__currentLoopData = $assets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $cell = $valueMatrix[$key][$asset->id] ?? ['display' => '', 'blank' => true];
                                    ?>
                                    <td class="px-3 py-2 align-top">
                                        <?php if($isQuantityRow): ?>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    class="mt-1"
                                                    value="<?php echo e($asset->id); ?>"
                                                    x-model="fields['quantity']"
                                                    :disabled="setQuantityToTotal || <?php echo e($cell['blank'] ? 'true' : 'false'); ?>"
                                                >
                                                <span class="text-xs break-words <?php echo e($cell['blank'] ? 'text-brand-charcoal/50 dark:text-brand-silver/50' : ''); ?>">
                                                    <?php echo e($cell['blank'] ? '—' : $cell['display']); ?>

                                                </span>
                                            </label>
                                        <?php else: ?>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    class="mt-1"
                                                    value="<?php echo e($asset->id); ?>"
                                                    x-model="<?php echo e($modelKey); ?>"
                                                    <?php if($cell['blank']): echo 'disabled'; endif; ?>
                                                >
                                                <span class="text-xs break-words <?php echo e($cell['blank'] ? 'text-brand-charcoal/50 dark:text-brand-silver/50' : ''); ?>">
                                                    <?php echo e($cell['blank'] ? '—' : $cell['display']); ?>

                                                </span>
                                            </label>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <td class="px-3 py-2 align-top">
                                    <?php if($isQuantityRow): ?>
                                        <span class="text-xs text-brand-charcoal/50 dark:text-brand-silver/50">Required</span>
                                    <?php elseif(! in_array($key, \App\Services\AssetMergeService::REQUIRED_FIELDS, true)): ?>
                                        <label class="flex items-center gap-2 cursor-pointer text-xs">
                                            <input type="radio" value="<?php echo e($emptySource); ?>" x-model="<?php echo e($modelKey); ?>">
                                            <span>Empty</span>
                                        </label>
                                    <?php else: ?>
                                        <span class="text-xs text-brand-charcoal/50 dark:text-brand-silver/50">Required</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-2">
                <a href="<?php echo e(route('assets.duplicates.index')); ?>" class="px-4 py-2 text-sm border border-brand-charcoal dark:border-brand-silver">Cancel</a>
                <button type="submit" class="px-4 py-2 text-sm bg-brand text-white">Merge permanently</button>
            </div>
        </form>
    </div>

    <script>
        function assetMerge(config) {
            return {
                survivorId: config.survivorId,
                emptySource: config.emptySource,
                fields: config.fields || {},
                customFields: config.customFields || {},
                quantityTotal: config.quantityTotal || 0,
                setQuantityToTotal: !!config.setQuantityToTotal,
            };
        }
    </script>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/duplicates/merge.blade.php ENDPATH**/ ?>
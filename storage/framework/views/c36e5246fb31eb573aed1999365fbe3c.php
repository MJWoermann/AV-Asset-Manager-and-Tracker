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
            <h2 class="font-semibold text-xl">Duplicate check</h2>
            <a href="<?php echo e(route('assets.index')); ?>" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Back to assets</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4" x-data="duplicateGroups()">
        <?php if(session('status')): ?>
            <div class="text-sm text-brand"><?php echo e(session('status')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="text-sm text-red-700 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Groups share at least one identifier (serial, FMI/AST, MAC, rig tag, TP barcode, or IP), matched case-insensitively within your active department scope.
            Select 2–<?php echo e(\App\Services\AssetMergeService::MAX_ASSETS); ?> assets in a group to review a side-by-side merge.
        </p>

        <div class="text-sm">
            <span class="font-semibold"><?php echo e($groupCount); ?> <?php echo e($groupCount === 1 ? 'group' : 'groups'); ?></span>
            ·
            <span class="font-semibold"><?php echo e($assetCount); ?> <?php echo e($assetCount === 1 ? 'asset' : 'assets'); ?> involved</span>
        </div>

        <?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupIndex => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
                <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <p class="font-medium">
                            Group <?php echo e($groupIndex + 1); ?>

                            <span class="text-brand-charcoal dark:text-brand-silver font-normal">· <?php echo e($group['count']); ?> assets</span>
                        </p>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver mt-1">
                            Matched on
                            <?php $__currentLoopData = $group['matched_on']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="inline-block mr-2">
                                    <?php echo e($matchKeyLabels[$key] ?? $key); ?>:
                                    <span class="font-mono"><?php echo e($group['match_values'][$key] ?? '—'); ?></span>
                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </p>
                    </div>
                    <form method="GET" action="<?php echo e(route('assets.duplicates.merge')); ?>" class="flex gap-2 items-center"
                          @submit="prepareGroupSubmit($event, <?php echo e($groupIndex); ?>)">
                        <template x-for="id in selectedByGroup[<?php echo e($groupIndex); ?>] || []" :key="'g<?php echo e($groupIndex); ?>-'+id">
                            <input type="hidden" name="asset_ids[]" :value="id">
                        </template>
                        <span class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="selectionLabel(<?php echo e($groupIndex); ?>)"></span>
                        <button
                            type="submit"
                            class="px-3 py-2 text-sm bg-brand text-white disabled:opacity-40"
                            :disabled="!canMergeGroup(<?php echo e($groupIndex); ?>)"
                        >Merge selected</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                            <tr>
                                <th class="px-3 py-2 w-10">
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        @change="toggleGroup(<?php echo e($groupIndex); ?>, $event.target.checked, <?php echo \Illuminate\Support\Js::from($group['asset_ids'])->toHtml() ?>)"
                                        :checked="isGroupFullySelected(<?php echo e($groupIndex); ?>, <?php echo e(count($group['asset_ids'])); ?>)"
                                    >
                                </th>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">TP barcode</th>
                                <th class="px-3 py-2">FMI / AST</th>
                                <th class="px-3 py-2">Serial</th>
                                <th class="px-3 py-2">Rig tag</th>
                                <th class="px-3 py-2">MAC</th>
                                <th class="px-3 py-2">IP</th>
                                <th class="px-3 py-2">ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $group['assets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                                    <td class="px-3 py-2">
                                        <input
                                            type="checkbox"
                                            class="rounded border-brand-charcoal/40"
                                            value="<?php echo e($asset->id); ?>"
                                            @change="toggleAsset(<?php echo e($groupIndex); ?>, <?php echo e($asset->id); ?>, $event.target.checked)"
                                            :checked="isSelected(<?php echo e($groupIndex); ?>, <?php echo e($asset->id); ?>)"
                                        >
                                    </td>
                                    <td class="px-3 py-2">
                                        <a href="<?php echo e(route('assets.show', $asset)); ?>" class="text-brand hover:underline"><?php echo e($asset->name); ?></a>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs"><?php echo e($asset->tp_barcode ?: '—'); ?></td>
                                    <td class="px-3 py-2 font-mono text-xs"><?php echo e($asset->fmi_ast ?: '—'); ?></td>
                                    <td class="px-3 py-2 font-mono text-xs"><?php echo e($asset->serial_number ?: '—'); ?></td>
                                    <td class="px-3 py-2 font-mono text-xs"><?php echo e($asset->rig_tag ?: '—'); ?></td>
                                    <td class="px-3 py-2 font-mono text-xs"><?php echo e($asset->mac_address ?: '—'); ?></td>
                                    <td class="px-3 py-2 font-mono text-xs"><?php echo e($asset->ip_address ?: '—'); ?></td>
                                    <td class="px-3 py-2 text-xs text-brand-charcoal dark:text-brand-silver">#<?php echo e($asset->id); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-6 text-sm text-brand-charcoal dark:text-brand-silver">
                No duplicates found. Assets are grouped when they share a serial number, FMI/AST, MAC, rig tag, TP barcode, or IP address.
            </div>
        <?php endif; ?>
    </div>

    <script>
        function duplicateGroups() {
            const maxAssets = <?php echo e(\App\Services\AssetMergeService::MAX_ASSETS); ?>;

            return {
                selectedByGroup: {},

                ensureGroup(groupIndex) {
                    if (!this.selectedByGroup[groupIndex]) {
                        this.selectedByGroup[groupIndex] = [];
                    }
                },

                isSelected(groupIndex, assetId) {
                    this.ensureGroup(groupIndex);
                    return this.selectedByGroup[groupIndex].includes(assetId);
                },

                isGroupFullySelected(groupIndex, total) {
                    this.ensureGroup(groupIndex);
                    return this.selectedByGroup[groupIndex].length === total && total > 0;
                },

                toggleAsset(groupIndex, assetId, checked) {
                    this.ensureGroup(groupIndex);
                    const list = this.selectedByGroup[groupIndex];
                    if (checked) {
                        if (!list.includes(assetId) && list.length < maxAssets) {
                            list.push(assetId);
                        }
                    } else {
                        this.selectedByGroup[groupIndex] = list.filter((id) => id !== assetId);
                    }
                },

                toggleGroup(groupIndex, checked, assetIds) {
                    this.ensureGroup(groupIndex);
                    if (!checked) {
                        this.selectedByGroup[groupIndex] = [];
                        return;
                    }
                    this.selectedByGroup[groupIndex] = assetIds.slice(0, maxAssets);
                },

                canMergeGroup(groupIndex) {
                    this.ensureGroup(groupIndex);
                    const count = this.selectedByGroup[groupIndex].length;
                    return count >= 2 && count <= maxAssets;
                },

                selectionLabel(groupIndex) {
                    this.ensureGroup(groupIndex);
                    const count = this.selectedByGroup[groupIndex].length;
                    if (count === 0) {
                        return 'None selected';
                    }
                    return count + ' selected' + (count > maxAssets ? ' (max ' + maxAssets + ')' : '');
                },

                prepareGroupSubmit(event, groupIndex) {
                    if (!this.canMergeGroup(groupIndex)) {
                        event.preventDefault();
                        return false;
                    }
                    return true;
                },
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/duplicates/index.blade.php ENDPATH**/ ?>
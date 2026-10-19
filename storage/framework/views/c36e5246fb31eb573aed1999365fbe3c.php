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

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
        x-data="duplicateGroups(<?php echo \Illuminate\Support\Js::from([
            'maxMerge' => \App\Services\AssetMergeService::MAX_ASSETS,
            'matchKeys' => \App\Services\AssetDuplicateService::MATCH_KEYS,
            'matchKeyLabels' => $matchKeyLabels,
            'groupMatchedOn' => collect($groups)->map(fn ($group) => $group['matched_on'])->values(),
            'groupAssetOptions' => collect($groups)->map(fn ($group) => $group['assets']->map(fn ($asset) => [
                'id' => $asset->id,
                'label' => $asset->name
                    .(collect([$asset->tp_barcode, $asset->fmi_ast, $asset->serial_number])->filter()->isNotEmpty()
                        ? ' ('.collect([$asset->tp_barcode, $asset->fmi_ast, $asset->serial_number])->filter()->implode(' · ').')'
                        : ''),
            ])->values())->values(),
        ])->toHtml() ?>)"
    >
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
            Select assets to merge (2–<?php echo e(\App\Services\AssetMergeService::MAX_ASSETS); ?>), clear shared identifiers, or link as parent/child.
        </p>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3">
            <p class="text-sm">
                <?php if($includeRelated): ?>
                    <span class="font-medium">Mode:</span> including parent/child matches
                    <span class="text-brand-charcoal dark:text-brand-silver">(assets already linked in a hierarchy may appear)</span>
                <?php else: ?>
                    <span class="font-medium">Mode:</span> excluding parent/child matches
                    <span class="text-brand-charcoal dark:text-brand-silver">(default — ancestor/descendant pairs that share identifiers are hidden)</span>
                <?php endif; ?>
            </p>
            <div class="flex flex-wrap gap-2 sm:justify-end">
                <?php if($includeRelated): ?>
                    <a
                        href="<?php echo e($excludeRelatedUrl); ?>"
                        class="px-3 py-2 border border-brand-charcoal dark:border-brand-silver text-sm inline-flex items-center justify-center"
                    >Hide parent/child matches</a>
                <?php else: ?>
                    <a
                        href="<?php echo e($includeRelatedUrl); ?>"
                        class="px-3 py-2 border border-brand-charcoal dark:border-brand-silver text-sm inline-flex items-center justify-center"
                    >Search including parent/child</a>
                <?php endif; ?>
            </div>
        </div>

        <form method="GET" action="<?php echo e(route('assets.duplicates.index')); ?>" class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:items-end border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3">
            <?php if($includeRelated): ?>
                <input type="hidden" name="include_related" value="1">
            <?php endif; ?>
            <div class="min-w-[12rem]">
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'duplicate_field_filter','value' => 'Matched field']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'duplicate_field_filter','value' => 'Matched field']); ?>
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
                    id="duplicate_field_filter"
                    name="field"
                    class="mt-1 w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm"
                    onchange="this.form.requestSubmit()"
                >
                    <option value="">All fields</option>
                    <?php $__currentLoopData = $matchKeyLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($key); ?>" <?php if($fieldFilter === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm pb-2 sm:pb-1">
                <input
                    type="checkbox"
                    name="multi"
                    value="1"
                    class="rounded border-brand-charcoal/40"
                    <?php if($multiFieldOnly): echo 'checked'; endif; ?>
                    onchange="this.form.requestSubmit()"
                >
                <span>2+ fields only</span>
            </label>
            <?php if (isset($component)) { $__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.clear-filters','data' => ['href' => $clearFiltersUrl,'active' => $filtersActive]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('clear-filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($clearFiltersUrl),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filtersActive)]); ?>
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

        <div class="text-sm">
            <span class="font-semibold"><?php echo e($groupCount); ?> <?php echo e($groupCount === 1 ? 'group' : 'groups'); ?></span>
            ·
            <span class="font-semibold"><?php echo e($assetCount); ?> <?php echo e($assetCount === 1 ? 'asset' : 'assets'); ?> involved</span>
            <?php if($filtersActive && $totalGroupCount !== $groupCount): ?>
                <span class="text-brand-charcoal dark:text-brand-silver">(<?php echo e($totalGroupCount); ?> total before filter)</span>
            <?php endif; ?>
        </div>

        <?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupIndex => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
                <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
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
                    <div class="flex flex-wrap gap-2 items-center">
                        <span class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="selectionLabel(<?php echo e($groupIndex); ?>)"></span>
                        <button
                            type="button"
                            class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver disabled:opacity-40"
                            :disabled="!canClearGroup(<?php echo e($groupIndex); ?>)"
                            @click="openClearModal(<?php echo e($groupIndex); ?>)"
                        >Clear fields…</button>
                        <button
                            type="button"
                            class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver disabled:opacity-40"
                            :disabled="!canParentChildGroup(<?php echo e($groupIndex); ?>)"
                            @click="openParentModal(<?php echo e($groupIndex); ?>)"
                        >Make parent/child…</button>
                        <form method="GET" action="<?php echo e(route('assets.duplicates.merge')); ?>" class="inline"
                              @submit="prepareMergeSubmit($event, <?php echo e($groupIndex); ?>)">
                            <template x-for="id in selectedByGroup[<?php echo e($groupIndex); ?>] || []" :key="'merge-g<?php echo e($groupIndex); ?>-'+id">
                                <input type="hidden" name="asset_ids[]" :value="id">
                            </template>
                            <button
                                type="submit"
                                class="px-3 py-2 text-sm bg-brand text-white disabled:opacity-40"
                                :disabled="!canMergeGroup(<?php echo e($groupIndex); ?>)"
                            >Merge selected</button>
                        </form>
                    </div>
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
                <?php if($filtersActive): ?>
                    No duplicate groups match the current filters.
                    <a href="<?php echo e(route('assets.duplicates.index')); ?>" class="text-brand underline ml-1">Clear filters</a>
                <?php else: ?>
                    No duplicates found. Assets are grouped when they share a serial number, FMI/AST, MAC, rig tag, TP barcode, or IP address.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div
            x-cloak
            x-show="clearModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="clear-duplicate-fields-title"
        >
            <div class="fixed inset-0 bg-black/50" @click="cancelClear()"></div>
            <div class="relative w-full max-w-lg max-h-[calc(100vh-3rem)] overflow-y-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-4 shadow-xl">
                <h3 id="clear-duplicate-fields-title" class="text-lg font-semibold">Clear identifier fields</h3>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Remove the selected identifier values from
                    <span class="font-semibold text-brand-black dark:text-white" x-text="clearSelectedCount()"></span>
                    asset<span x-show="clearSelectedCount() !== 1">s</span>.
                    Leave the field on any assets you did not select so they are no longer flagged together on that field.
                </p>

                <fieldset class="space-y-2 text-sm border border-brand-silver dark:border-brand-charcoal p-3">
                    <legend class="px-1 font-medium">Fields to clear</legend>
                    <template x-for="key in matchKeys" :key="'clear-field-'+key">
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                class="rounded border-brand-charcoal/40"
                                :value="key"
                                x-model="clearFields"
                            >
                            <span x-text="matchKeyLabels[key] || key"></span>
                        </label>
                    </template>
                </fieldset>

                <form method="POST" action="<?php echo e(route('assets.duplicates.clear')); ?>" @submit="prepareClearSubmit($event)">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="confirm_clear" value="1">
                    <template x-for="id in clearAssetIds()" :key="'clear-asset-'+id">
                        <input type="hidden" name="asset_ids[]" :value="id">
                    </template>
                    <template x-for="field in clearFields" :key="'clear-post-'+field">
                        <input type="hidden" name="fields[]" :value="field">
                    </template>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="cancelClear()">Cancel</button>
                        <button
                            type="submit"
                            class="px-3 py-2 text-sm bg-brand text-white disabled:opacity-40"
                            :disabled="clearFields.length === 0 || clearSelectedCount() < 1"
                        >Clear values</button>
                    </div>
                </form>
            </div>
        </div>

        <div
            x-cloak
            x-show="parentModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="parent-child-title"
        >
            <div class="fixed inset-0 bg-black/50" @click="cancelParent()"></div>
            <div class="relative w-full max-w-lg max-h-[calc(100vh-3rem)] overflow-y-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-4 shadow-xl">
                <h3 id="parent-child-title" class="text-lg font-semibold">Make parent / child</h3>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Choose which selected asset is the parent. The others become its children.
                    Children inherit the parent’s location (and department). Existing parents are replaced.
                    Cycles are blocked.
                </p>

                <fieldset class="space-y-2 text-sm">
                    <legend class="font-medium text-brand-black dark:text-white">Parent asset</legend>
                    <template x-for="option in parentOptions()" :key="'parent-opt-'+option.id">
                        <label class="flex items-start gap-2 border border-brand-silver dark:border-brand-charcoal p-2 cursor-pointer">
                            <input
                                type="radio"
                                class="mt-1"
                                :value="option.id"
                                x-model.number="parentId"
                            >
                            <span x-text="option.label"></span>
                        </label>
                    </template>
                </fieldset>

                <form method="POST" action="<?php echo e(route('assets.duplicates.parent-child')); ?>" @submit="prepareParentSubmit($event)">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="confirm_parent_child" value="1">
                    <input type="hidden" name="parent_id" :value="parentId">
                    <template x-for="id in parentAssetIds()" :key="'parent-asset-'+id">
                        <input type="hidden" name="asset_ids[]" :value="id">
                    </template>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="cancelParent()">Cancel</button>
                        <button
                            type="submit"
                            class="px-3 py-2 text-sm bg-brand text-white disabled:opacity-40"
                            :disabled="!parentId || parentSelectedCount() < 2"
                        >Link as parent/child</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function duplicateGroups(config) {
            const maxMerge = config.maxMerge || 6;

            return {
                selectedByGroup: {},
                matchKeys: config.matchKeys || [],
                matchKeyLabels: config.matchKeyLabels || {},
                groupMatchedOn: config.groupMatchedOn || [],
                groupAssetOptions: config.groupAssetOptions || [],
                clearModalOpen: false,
                clearGroupIndex: null,
                clearFields: [],
                parentModalOpen: false,
                parentGroupIndex: null,
                parentId: null,

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
                        if (!list.includes(assetId)) {
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
                    this.selectedByGroup[groupIndex] = [...assetIds];
                },

                canMergeGroup(groupIndex) {
                    this.ensureGroup(groupIndex);
                    const count = this.selectedByGroup[groupIndex].length;
                    return count >= 2 && count <= maxMerge;
                },

                canClearGroup(groupIndex) {
                    this.ensureGroup(groupIndex);
                    return this.selectedByGroup[groupIndex].length >= 1;
                },

                canParentChildGroup(groupIndex) {
                    this.ensureGroup(groupIndex);
                    return this.selectedByGroup[groupIndex].length >= 2;
                },

                selectionLabel(groupIndex) {
                    this.ensureGroup(groupIndex);
                    const count = this.selectedByGroup[groupIndex].length;
                    if (count === 0) {
                        return 'None selected';
                    }
                    let label = count + ' selected';
                    if (count > maxMerge) {
                        label += ' (merge max ' + maxMerge + ')';
                    }
                    return label;
                },

                prepareMergeSubmit(event, groupIndex) {
                    if (!this.canMergeGroup(groupIndex)) {
                        event.preventDefault();
                        return false;
                    }
                    return true;
                },

                openClearModal(groupIndex) {
                    if (!this.canClearGroup(groupIndex)) {
                        return;
                    }
                    this.clearGroupIndex = groupIndex;
                    const matched = this.groupMatchedOn[groupIndex] || [];
                    this.clearFields = matched.length > 0 ? [...matched] : [...this.matchKeys];
                    this.clearModalOpen = true;
                },

                cancelClear() {
                    this.clearModalOpen = false;
                    this.clearGroupIndex = null;
                    this.clearFields = [];
                },

                clearSelectedCount() {
                    if (this.clearGroupIndex === null) {
                        return 0;
                    }
                    this.ensureGroup(this.clearGroupIndex);
                    return this.selectedByGroup[this.clearGroupIndex].length;
                },

                clearAssetIds() {
                    if (this.clearGroupIndex === null) {
                        return [];
                    }
                    this.ensureGroup(this.clearGroupIndex);
                    return this.selectedByGroup[this.clearGroupIndex];
                },

                prepareClearSubmit(event) {
                    if (this.clearSelectedCount() < 1 || this.clearFields.length === 0) {
                        event.preventDefault();
                        return false;
                    }
                    return true;
                },

                openParentModal(groupIndex) {
                    if (!this.canParentChildGroup(groupIndex)) {
                        return;
                    }
                    this.parentGroupIndex = groupIndex;
                    this.ensureGroup(groupIndex);
                    this.parentId = this.selectedByGroup[groupIndex][0] ?? null;
                    this.parentModalOpen = true;
                },

                cancelParent() {
                    this.parentModalOpen = false;
                    this.parentGroupIndex = null;
                    this.parentId = null;
                },

                parentSelectedCount() {
                    if (this.parentGroupIndex === null) {
                        return 0;
                    }
                    this.ensureGroup(this.parentGroupIndex);
                    return this.selectedByGroup[this.parentGroupIndex].length;
                },

                parentAssetIds() {
                    if (this.parentGroupIndex === null) {
                        return [];
                    }
                    this.ensureGroup(this.parentGroupIndex);
                    return this.selectedByGroup[this.parentGroupIndex];
                },

                parentOptions() {
                    const ids = this.parentAssetIds();
                    const options = this.groupAssetOptions[this.parentGroupIndex] || [];
                    return options.filter((option) => ids.includes(option.id));
                },

                prepareParentSubmit(event) {
                    if (!this.parentId || this.parentSelectedCount() < 2) {
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
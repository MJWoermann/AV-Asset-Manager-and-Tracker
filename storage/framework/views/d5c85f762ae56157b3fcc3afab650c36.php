<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'statuses',
    'locations',
    'parents',
    'lists',
    'departments' => null,
    'currentList' => null,
    'canDelete' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'statuses',
    'locations',
    'parents',
    'lists',
    'departments' => null,
    'currentList' => null,
    'canDelete' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $canDelete = $canDelete || auth()->user()?->hasAnyRole(['admin', 'inventory_manager']);
    $canChangeDepartment = auth()->user()?->hasRole('admin') ?? false;
    $departments = collect($departments ?? []);
?>

<div
    x-cloak
    x-show="selectedCount > 0"
    class="sticky top-0 z-20 mb-4 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 shadow-sm"
>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm">
            <span class="font-semibold" x-text="selectedCount"></span>
            <span x-text="selectedCount === 1 ? 'asset selected' : 'assets selected'"></span>
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="panelOpen = !panelOpen" x-text="panelOpen ? 'Hide bulk edit' : 'Bulk edit'"></button>
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                    <?php if($currentList): ?>
                        <form method="GET" action="<?php echo e(route('labels.index')); ?>" class="inline">
                            <input type="hidden" name="asset_list_id" value="<?php echo e($currentList->id); ?>">
                            <template x-for="id in selectedIds" :key="'shelf-'+id">
                                <input type="hidden" name="asset_ids[]" :value="id">
                            </template>
                            <button type="submit" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver">Labels</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="clearSelection()">Clear</button>
            <?php if($canDelete): ?>
                <form
                    method="POST"
                    action="<?php echo e(route('assets.duplicates.start')); ?>"
                    class="inline"
                    x-show="selectedCount >= 2"
                    x-ref="mergeStartForm"
                >
                    <?php echo csrf_field(); ?>
                    <template x-for="id in selectedIds" :key="'merge-start-'+id">
                        <input type="hidden" name="asset_ids[]" :value="id">
                    </template>
                    <button type="submit" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver">Merge selected</button>
                </form>
                <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="openDeleteConfirm()">Delete selected</button>
            <?php endif; ?>
        </div>
    </div>

    <form
        method="POST"
        action="<?php echo e(route('assets.bulk-update')); ?>"
        class="mt-4 space-y-4 border-t border-brand-silver dark:border-brand-charcoal pt-4"
        x-show="panelOpen || pendingDelete || pendingMerge"
        x-ref="bulkForm"
        @submit="prepareSubmit($event)"
    >
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" :value="pendingDelete ? 'delete' : (pendingMerge ? 'merge' : 'update')">
        <template x-for="id in selectedIds" :key="id">
            <input type="hidden" name="asset_ids[]" :value="id">
        </template>
        <?php if($currentList): ?>
            <input type="hidden" name="current_list_id" value="<?php echo e($currentList->id); ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Status</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_status" value="1" x-model="fields.update_status">
                    <span>Update status</span>
                </label>
                <select name="status" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="!fields.update_status">
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Location</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_location" value="1" x-model="fields.update_location" @change="if (fields.update_location) fields.clear_location = false">
                    <span>Update location</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_location" value="1" x-model="fields.clear_location" @change="if (fields.clear_location) fields.update_location = false">
                    <span>Clear location</span>
                </label>
                <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'location_id','id' => 'bulk_location_id','options' => $locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name]),'nullable' => true,'placeholder' => 'Select location']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'location_id','id' => 'bulk_location_id','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])),'nullable' => true,'placeholder' => 'Select location']); ?>
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
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Parent asset</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_parent" value="1" x-model="fields.update_parent" @change="if (fields.update_parent) fields.clear_parent = false">
                    <span>Update parent</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_parent" value="1" x-model="fields.clear_parent" @change="if (fields.clear_parent) fields.update_parent = false">
                    <span>Clear parent</span>
                </label>
                <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'parent_id','id' => 'bulk_parent_id','options' => collect($parents ?? [])->map(fn ($parent) => is_array($parent)
                        ? $parent
                        : ['value' => $parent->id, 'label' => \App\Support\AssetSearch::optionLabel($parent)]),'searchUrl' => route('assets.search'),'searchParams' => ['parents_only' => 1],'nullable' => true,'placeholder' => 'Search by name, ID#, barcode…']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'parent_id','id' => 'bulk_parent_id','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(collect($parents ?? [])->map(fn ($parent) => is_array($parent)
                        ? $parent
                        : ['value' => $parent->id, 'label' => \App\Support\AssetSearch::optionLabel($parent)])),'search-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('assets.search')),'search-params' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['parents_only' => 1]),'nullable' => true,'placeholder' => 'Search by name, ID#, barcode…']); ?>
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
            </fieldset>

            <?php if($canChangeDepartment && $departments->isNotEmpty()): ?>
                <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                    <legend class="px-1 font-medium">Department</legend>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="update_department" value="1" x-model="fields.update_department">
                        <span>Update department</span>
                    </label>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Child assets follow the parent. Moving a child alone clears its parent link.
                    </p>
                    <select
                        name="department_id"
                        class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        :disabled="!fields.update_department"
                    >
                        <option value="">Select department</option>
                        <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($department->id); ?>"><?php echo e($department->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </fieldset>
            <?php endif; ?>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Test &amp; Tag expiry</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_test_tag_expiry" value="1" x-model="fields.update_test_tag_expiry" @change="if (fields.update_test_tag_expiry) fields.clear_test_tag_expiry = false">
                    <span>Update expiry</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_test_tag_expiry" value="1" x-model="fields.clear_test_tag_expiry" @change="if (fields.clear_test_tag_expiry) fields.update_test_tag_expiry = false">
                    <span>Clear expiry</span>
                </label>
                <input type="date" name="test_tag_expiry" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="!fields.update_test_tag_expiry">
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Warranty expiry</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_warranty_expiry" value="1" x-model="fields.update_warranty_expiry" @change="if (fields.update_warranty_expiry) fields.clear_warranty_expiry = false">
                    <span>Update warranty</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_warranty_expiry" value="1" x-model="fields.clear_warranty_expiry" @change="if (fields.clear_warranty_expiry) fields.update_warranty_expiry = false">
                    <span>Clear warranty</span>
                </label>
                <input type="date" name="warranty_expiry" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="!fields.update_warranty_expiry">
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Notes</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_notes" value="1" x-model="fields.update_notes" @change="if (fields.update_notes) fields.clear_notes = false">
                    <span>Replace notes</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_notes" value="1" x-model="fields.clear_notes" @change="if (fields.clear_notes) fields.update_notes = false">
                    <span>Clear notes</span>
                </label>
                <textarea name="notes" rows="2" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="!fields.update_notes"></textarea>
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3 md:col-span-2">
                <legend class="px-1 font-medium">Lists</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'add_to_list_id','value' => 'Add to list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'add_to_list_id','value' => 'Add to list']); ?>
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
                        <select id="add_to_list_id" name="add_to_list_id" class="mt-1 w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="">—</option>
                            <?php $__currentLoopData = $lists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($listOption->id); ?>"><?php echo e($listOption->name); ?> (<?php echo e($listOption->type); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php if($currentList): ?>
                        <label class="flex items-center gap-2 mt-6">
                            <input type="checkbox" name="remove_from_list" value="1">
                            <span>Remove from this list (<?php echo e($currentList->name); ?>)</span>
                        </label>
                    <?php endif; ?>
                </div>
            </fieldset>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-brand text-white text-sm">Apply bulk update</button>
        </div>
    </form>
</div>

<?php if($canDelete): ?>
    <div
        x-cloak
        x-show="deleteConfirmOpen"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-black/50" @click="cancelDelete()"></div>
        <div class="relative w-full max-w-md max-h-[calc(100vh-3rem)] overflow-y-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-4 shadow-xl">
            <h3 class="text-lg font-semibold">Confirm delete</h3>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                Permanently delete
                <span class="font-semibold text-brand-black dark:text-white" x-text="selectedCount"></span>
                <span x-text="selectedCount === 1 ? 'asset' : 'assets'"></span>?
                This cannot be undone.
            </p>
            <div class="flex justify-end gap-2">
                <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="cancelDelete()">Cancel</button>
                <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="confirmDelete()">Delete permanently</button>
            </div>
        </div>
    </div>

<?php endif; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/bulk-asset-actions.blade.php ENDPATH**/ ?>
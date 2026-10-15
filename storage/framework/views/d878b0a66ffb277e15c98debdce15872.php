<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'parents',
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
    'parents',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div
    x-cloak
    x-show="selectedCount > 0"
    class="sticky top-0 z-20 mb-4 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 shadow-sm"
>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm">
            <span class="font-semibold" x-text="selectedCount"></span>
            <span x-text="selectedCount === 1 ? 'location selected' : 'locations selected'"></span>
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="panelOpen = !panelOpen" x-text="panelOpen ? 'Hide bulk edit' : 'Bulk edit'"></button>
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="submitMerge()">Merge same names</button>
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="clearSelection()">Clear</button>
            <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="openDeleteConfirm()">Delete selected</button>
        </div>
    </div>

    <form
        method="POST"
        action="<?php echo e(route('locations.bulk-update')); ?>"
        class="mt-4 space-y-4 border-t border-brand-silver dark:border-brand-charcoal pt-4"
        x-show="panelOpen || pendingDelete || pendingMerge"
        x-ref="bulkForm"
        @submit="prepareSubmit($event)"
    >
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" :value="pendingDelete ? 'delete' : (pendingMerge ? 'merge_duplicates' : 'update')">
        <template x-for="id in selectedIds" :key="id">
            <input type="hidden" name="location_ids[]" :value="id">
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" x-show="!pendingDelete && !pendingMerge">
            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3 md:col-span-2">
                <legend class="px-1 font-medium">Parent location</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_parent" value="1" x-model="fields.update_parent" @change="if (fields.update_parent) fields.clear_parent = false">
                    <span>Assign parent</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_parent" value="1" x-model="fields.clear_parent" @change="if (fields.clear_parent) fields.update_parent = false">
                    <span>Clear parent</span>
                </label>
                <select
                    name="parent_id"
                    class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    :disabled="!fields.update_parent"
                >
                    <option value="">—</option>
                    <?php $__currentLoopData = $parents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($parent->id); ?>"><?php echo e($parent->breadcrumb()); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Use “Merge same names” to combine selected locations that share a name. Assets on duplicates move to the kept location.
                </p>
            </fieldset>
        </div>

        <div class="flex justify-end" x-show="!pendingDelete && !pendingMerge">
            <button type="submit" class="px-4 py-2 bg-brand text-white text-sm">Apply bulk update</button>
        </div>
    </form>
</div>

<div
    x-cloak
    x-show="deleteConfirmOpen"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-black/50" @click="cancelDelete()"></div>
    <div class="relative mx-auto max-w-md bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-4">
        <h3 class="text-lg font-semibold">Confirm delete</h3>
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Permanently delete
            <span class="font-semibold text-brand-black dark:text-white" x-text="selectedCount"></span>
            <span x-text="selectedCount === 1 ? 'location' : 'locations'"></span>?
            Child locations become top-level and assets at these locations lose their location.
        </p>
        <div class="flex justify-end gap-2">
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="cancelDelete()">Cancel</button>
            <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="confirmDelete()">Delete permanently</button>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/bulk-location-actions.blade.php ENDPATH**/ ?>
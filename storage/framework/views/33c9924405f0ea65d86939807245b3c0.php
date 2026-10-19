<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'departments',
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
    'departments',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $departments = collect($departments ?? []);
?>

<?php if($departments->isNotEmpty()): ?>
    <div
        x-cloak
        x-show="selectedCount > 0"
        class="sticky top-0 z-20 mb-4 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 shadow-sm"
    >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm">
                <span class="font-semibold" x-text="selectedCount"></span>
                <span x-text="selectedCount === 1 ? 'list selected' : 'lists selected'"></span>
            </p>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="panelOpen = !panelOpen" x-text="panelOpen ? 'Hide bulk edit' : 'Bulk edit'"></button>
                <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="clearSelection()">Clear</button>
            </div>
        </div>

        <form
            method="POST"
            action="<?php echo e(route('lists.bulk-update')); ?>"
            class="mt-4 space-y-4 border-t border-brand-silver dark:border-brand-charcoal pt-4"
            x-show="panelOpen"
            x-ref="bulkForm"
            @submit="prepareSubmit($event)"
        >
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update">
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="asset_list_ids[]" :value="id">
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                    <legend class="px-1 font-medium">Department</legend>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="update_department" value="1" x-model="fields.update_department">
                        <span>Update department</span>
                    </label>
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
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-brand text-white text-sm">Apply bulk update</button>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/bulk-list-actions.blade.php ENDPATH**/ ?>
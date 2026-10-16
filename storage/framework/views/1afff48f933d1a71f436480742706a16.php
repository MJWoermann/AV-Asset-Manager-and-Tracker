<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['variant' => 'dropdown']));

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

foreach (array_filter((['variant' => 'dropdown']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $user = Auth::user();
    $activeDepartmentId = $user?->resolveActiveDepartmentId();
    $isAdmin = $user?->hasRole('admin');
    $memberDepartments = $isAdmin
        ? \App\Models\Department::query()->where('is_active', true)->orderBy('name')->get()
        : ($user?->departments()->where('departments.is_active', true)->orderBy('departments.name')->get() ?? collect());
    $allDepartments = $isAdmin
        ? \App\Models\Department::query()->orderBy('name')->get()
        : collect();
    $visibleIds = $user?->visibleDepartmentIdsPreference() ?? [];
    $compact = $variant === 'mobile';
?>

<?php if($user && ($memberDepartments->isNotEmpty() || $isAdmin)): ?>
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-4 py-2 space-y-3' => $compact, 'px-4 py-3 space-y-3 border-b border-gray-100 dark:border-brand-charcoal' => ! $compact]); ?>">
        <?php if($memberDepartments->isNotEmpty()): ?>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-brand-silver/70 mb-1">Active department</p>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">New assets, lists, and scans use this department.</p>
                <form method="POST" action="<?php echo e(route('preferences.active-department')); ?>" class="space-y-2">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <select
                        name="department_id"
                        class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm"
                        onchange="this.form.requestSubmit()"
                    >
                        <?php $__currentLoopData = $memberDepartments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($department->id); ?>" <?php if((int) $activeDepartmentId === (int) $department->id): echo 'selected'; endif; ?>>
                                <?php echo e($department->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>

        <?php if($isAdmin): ?>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-brand-silver/70 mb-1">Departments to show</p>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">
                    When any departments are selected, only those are shown. Clear all to show every department.
                </p>
                <form method="POST" action="<?php echo e(route('preferences.department-visibility')); ?>" class="space-y-2">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <div class="max-h-36 overflow-y-auto space-y-1 border border-brand-silver dark:border-brand-charcoal p-2">
                        <?php $__currentLoopData = $allDepartments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-center gap-2 text-sm text-brand-charcoal dark:text-brand-silver">
                                <input
                                    type="checkbox"
                                    name="visible_department_ids[]"
                                    value="<?php echo e($department->id); ?>"
                                    class="rounded text-brand"
                                    <?php if($visibleIds !== [] && in_array($department->id, $visibleIds, true)): echo 'checked'; endif; ?>
                                >
                                <?php echo e($department->name); ?>

                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="text-sm text-brand hover:underline">Apply visibility</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/department-controls.blade.php ENDPATH**/ ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'selectedColumns',
    'statuses',
    'locations',
    'itemTypes',
    'formId' => 'asset-filters',
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
    'selectedColumns',
    'statuses',
    'locations',
    'itemTypes',
    'formId' => 'asset-filters',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    use App\Support\AssetBrowseFilters;

    $inputClass = 'w-full min-w-[6rem] rounded border-brand-charcoal/30 text-xs dark:bg-black dark:border-brand-charcoal dark:text-white';
?>

<tr class="bg-brand-silver/70 dark:bg-brand-charcoal/80">
    <th class="px-3 py-2 w-10"></th>
    <?php $__currentLoopData = $selectedColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <th class="px-2 py-2 align-bottom font-normal">
            <?php switch($column):
                case ('status'): ?>
                    <select
                        name="status"
                        form="<?php echo e($formId); ?>"
                        class="<?php echo e($inputClass); ?>"
                        aria-label="Filter by status"
                    >
                        <option value="">All</option>
                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php break; ?>

                <?php case ('location'): ?>
                    <select
                        name="location_id"
                        id="filter_location_id"
                        form="<?php echo e($formId); ?>"
                        class="<?php echo e($inputClass); ?>"
                        aria-label="Filter by location"
                    >
                        <option value="">All locations</option>
                        <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($location->id); ?>" <?php if((string) request('location_id') === (string) $location->id): echo 'selected'; endif; ?>>
                                <?php echo e($location->breadcrumb()); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php break; ?>

                <?php case ('item_type'): ?>
                    <select
                        name="item_type_id"
                        id="filter_item_type_id"
                        form="<?php echo e($formId); ?>"
                        class="<?php echo e($inputClass); ?>"
                        aria-label="Filter by item type"
                    >
                        <option value="">All item types</option>
                        <?php $__currentLoopData = $itemTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($itemType->id); ?>" <?php if((string) request('item_type_id') === (string) $itemType->id): echo 'selected'; endif; ?>>
                                <?php echo e($itemType->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php break; ?>

                <?php case ('quantity'): ?>
                    <input
                        type="number"
                        name="quantity"
                        form="<?php echo e($formId); ?>"
                        value="<?php echo e(request('quantity')); ?>"
                        placeholder="Qty"
                        min="0"
                        class="<?php echo e($inputClass); ?>"
                        aria-label="Filter by quantity"
                    >
                    <?php break; ?>

                <?php case ('test_tag_expiry'): ?>
                    <input
                        type="date"
                        name="test_tag_expiry"
                        form="<?php echo e($formId); ?>"
                        value="<?php echo e(request('test_tag_expiry')); ?>"
                        class="<?php echo e($inputClass); ?>"
                        aria-label="Filter by test and tag expiry"
                    >
                    <?php break; ?>

                <?php default: ?>
                    <?php
                        $param = AssetBrowseFilters::columnParams()[$column] ?? $column;
                    ?>
                    <input
                        type="search"
                        name="<?php echo e($param); ?>"
                        form="<?php echo e($formId); ?>"
                        value="<?php echo e(request($param)); ?>"
                        placeholder="Filter…"
                        class="<?php echo e($inputClass); ?>"
                        aria-label="Filter by <?php echo e(\App\Support\AssetTableColumns::label($column)); ?>"
                    >
            <?php endswitch; ?>
        </th>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tr>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/asset-column-filters.blade.php ENDPATH**/ ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'selectedColumns',
    'statuses',
    'locations',
    'itemTypes',
    'manufacturers' => [],
    'manufacturerModels' => [],
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
    'manufacturers' => [],
    'manufacturerModels' => [],
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

    $statusOptions = collect($statuses)
        ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
        ->values();

    $locationOptions = collect($locations)
        ->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->breadcrumb()])
        ->values();

    $itemTypeOptions = collect($itemTypes)
        ->map(fn ($itemType) => ['value' => (string) $itemType->id, 'label' => $itemType->name])
        ->values();

    $manufacturerOptions = collect($manufacturers)
        ->map(fn ($manufacturer) => ['value' => (string) $manufacturer->id, 'label' => $manufacturer->name])
        ->values();

    $modelOptions = collect($manufacturerModels)
        ->map(fn ($manufacturerModel) => [
            'value' => (string) $manufacturerModel->id,
            'label' => trim(($manufacturerModel->manufacturer?->name ? $manufacturerModel->manufacturer->name.' / ' : '').$manufacturerModel->name),
        ])
        ->values();
?>

<tr class="bg-brand-silver/70 dark:bg-brand-charcoal/80">
    <th class="px-3 py-2 w-10 align-middle">
        <input
            type="checkbox"
            class="rounded border-brand-charcoal/40"
            :checked="allPageSelected"
            @change="toggleAll($event.target.checked)"
            aria-label="Select all assets on this page"
        >
    </th>
    <?php $__currentLoopData = $selectedColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <th class="px-2 py-2 align-bottom font-normal min-w-[7rem]">
            <?php switch($column):
                case ('status'): ?>
                    <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'status','id' => 'filter_status','form' => ''.e($formId).'','options' => $statusOptions,'selected' => request('status'),'placeholder' => 'All','nullable' => true,'autoSubmit' => true,'compact' => true,'ariaLabel' => 'Filter by status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'status','id' => 'filter_status','form' => ''.e($formId).'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('status')),'placeholder' => 'All','nullable' => true,'auto-submit' => true,'compact' => true,'aria-label' => 'Filter by status']); ?>
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
                    <?php break; ?>

                <?php case ('location'): ?>
                    <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'location_id','id' => 'filter_location_id','form' => ''.e($formId).'','options' => $locationOptions,'selected' => request('location_id'),'placeholder' => 'All locations','nullable' => true,'autoSubmit' => true,'compact' => true,'ariaLabel' => 'Filter by location']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'location_id','id' => 'filter_location_id','form' => ''.e($formId).'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locationOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('location_id')),'placeholder' => 'All locations','nullable' => true,'auto-submit' => true,'compact' => true,'aria-label' => 'Filter by location']); ?>
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
                    <?php break; ?>

                <?php case ('item_type'): ?>
                    <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'item_type_id','id' => 'filter_item_type_id','form' => ''.e($formId).'','options' => $itemTypeOptions,'selected' => request('item_type_id'),'placeholder' => 'All item types','nullable' => true,'autoSubmit' => true,'compact' => true,'ariaLabel' => 'Filter by item type']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'item_type_id','id' => 'filter_item_type_id','form' => ''.e($formId).'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($itemTypeOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('item_type_id')),'placeholder' => 'All item types','nullable' => true,'auto-submit' => true,'compact' => true,'aria-label' => 'Filter by item type']); ?>
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
                    <?php break; ?>

                <?php case ('manufacturer'): ?>
                    <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'manufacturer_id','id' => 'filter_manufacturer_id','form' => ''.e($formId).'','options' => $manufacturerOptions,'selected' => request('manufacturer_id'),'placeholder' => 'All manufacturers','nullable' => true,'autoSubmit' => true,'compact' => true,'ariaLabel' => 'Filter by manufacturer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'manufacturer_id','id' => 'filter_manufacturer_id','form' => ''.e($formId).'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($manufacturerOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('manufacturer_id')),'placeholder' => 'All manufacturers','nullable' => true,'auto-submit' => true,'compact' => true,'aria-label' => 'Filter by manufacturer']); ?>
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
                    <?php break; ?>

                <?php case ('model'): ?>
                    <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'manufacturer_model_id','id' => 'filter_manufacturer_model_id','form' => ''.e($formId).'','options' => $modelOptions,'selected' => request('manufacturer_model_id'),'placeholder' => 'All models','nullable' => true,'autoSubmit' => true,'compact' => true,'ariaLabel' => 'Filter by model']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'manufacturer_model_id','id' => 'filter_manufacturer_model_id','form' => ''.e($formId).'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($modelOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('manufacturer_model_id')),'placeholder' => 'All models','nullable' => true,'auto-submit' => true,'compact' => true,'aria-label' => 'Filter by model']); ?>
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
                        onchange="this.form?.requestSubmit()"
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
                        onchange="this.form?.requestSubmit()"
                    >
                    <?php break; ?>

                <?php default: ?>
                    <?php
                        $param = AssetBrowseFilters::columnParams()[$column] ?? $column;
                        $columnLabel = \App\Support\AssetTableColumns::label($column);
                        $columnIsScannable = \App\Support\ScannableIdentifierFields::isScannable($column);
                    ?>
                    <?php if($columnIsScannable): ?>
                        <?php if (isset($component)) { $__componentOriginal6ba2bee0210b38146ba04d5bb3b73030 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scannable-search','data' => ['id' => 'filter-'.$param,'name' => $param,'form' => ''.e($formId).'','value' => request($param),'placeholder' => 'Filter…','inputClass' => 'text-xs min-w-[7rem]','ariaLabel' => 'Filter by '.$columnLabel,'scanAriaLabel' => 'Scan barcode into '.$columnLabel.' filter']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scannable-search'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('filter-'.$param),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($param),'form' => ''.e($formId).'','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request($param)),'placeholder' => 'Filter…','input-class' => 'text-xs min-w-[7rem]','aria-label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Filter by '.$columnLabel),'scan-aria-label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Scan barcode into '.$columnLabel.' filter')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030)): ?>
<?php $attributes = $__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030; ?>
<?php unset($__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6ba2bee0210b38146ba04d5bb3b73030)): ?>
<?php $component = $__componentOriginal6ba2bee0210b38146ba04d5bb3b73030; ?>
<?php unset($__componentOriginal6ba2bee0210b38146ba04d5bb3b73030); ?>
<?php endif; ?>
                    <?php else: ?>
                        <input
                            type="search"
                            name="<?php echo e($param); ?>"
                            form="<?php echo e($formId); ?>"
                            value="<?php echo e(request($param)); ?>"
                            placeholder="Filter…"
                            class="<?php echo e($inputClass); ?>"
                            aria-label="Filter by <?php echo e($columnLabel); ?>"
                        >
                    <?php endif; ?>
            <?php endswitch; ?>
        </th>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tr>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/asset-column-filters.blade.php ENDPATH**/ ?>
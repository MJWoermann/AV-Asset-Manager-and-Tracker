<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'asset',
    'column',
    'item' => null,
    'indent' => false,
    'term' => null,
    'selectedColumns' => [],
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
    'asset',
    'column',
    'item' => null,
    'indent' => false,
    'term' => null,
    'selectedColumns' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    use App\Support\AssetSearch;
    use App\Support\AssetTableColumns;

    $value = AssetTableColumns::value($asset, $column, $item);
    $mono = AssetTableColumns::isMono($column);
    $match = $term ? AssetSearch::bestMatch($asset, $term) : null;
    $showMatchHint = $column === 'name'
        && $match
        && $match['field'] !== 'name'
        && ! in_array($match['field'], $selectedColumns, true);
?>

<td <?php echo e($attributes->class(['px-3 py-2', 'font-mono' => $mono, 'pl-10' => $indent && $column === 'name'])); ?>>
    <?php if($column === 'name'): ?>
        <a class="text-brand hover:underline" href="<?php echo e(route('assets.show', $asset)); ?>">
            <?php if (isset($component)) { $__componentOriginal50233b75799bab718e6b51a0e60721e4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50233b75799bab718e6b51a0e60721e4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.highlight-match','data' => ['text' => $value,'term' => $term]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('highlight-match'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($value),'term' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($term)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50233b75799bab718e6b51a0e60721e4)): ?>
<?php $attributes = $__attributesOriginal50233b75799bab718e6b51a0e60721e4; ?>
<?php unset($__attributesOriginal50233b75799bab718e6b51a0e60721e4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50233b75799bab718e6b51a0e60721e4)): ?>
<?php $component = $__componentOriginal50233b75799bab718e6b51a0e60721e4; ?>
<?php unset($__componentOriginal50233b75799bab718e6b51a0e60721e4); ?>
<?php endif; ?>
        </a>
        <?php if($showMatchHint): ?>
            <div class="text-xs text-brand-charcoal dark:text-brand-silver mt-0.5 font-sans">
                <?php echo e($match['label']); ?>:
                <span class="font-mono"><?php if (isset($component)) { $__componentOriginal50233b75799bab718e6b51a0e60721e4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50233b75799bab718e6b51a0e60721e4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.highlight-match','data' => ['text' => $match['value'],'term' => $term]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('highlight-match'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($match['value']),'term' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($term)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50233b75799bab718e6b51a0e60721e4)): ?>
<?php $attributes = $__attributesOriginal50233b75799bab718e6b51a0e60721e4; ?>
<?php unset($__attributesOriginal50233b75799bab718e6b51a0e60721e4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50233b75799bab718e6b51a0e60721e4)): ?>
<?php $component = $__componentOriginal50233b75799bab718e6b51a0e60721e4; ?>
<?php unset($__componentOriginal50233b75799bab718e6b51a0e60721e4); ?>
<?php endif; ?></span>
            </div>
        <?php endif; ?>
    <?php elseif($column === 'parent' && $asset->parent): ?>
        <a class="text-brand hover:underline" href="<?php echo e(route('assets.show', $asset->parent)); ?>">
            <?php if (isset($component)) { $__componentOriginal50233b75799bab718e6b51a0e60721e4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50233b75799bab718e6b51a0e60721e4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.highlight-match','data' => ['text' => $value,'term' => $term]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('highlight-match'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($value),'term' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($term)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50233b75799bab718e6b51a0e60721e4)): ?>
<?php $attributes = $__attributesOriginal50233b75799bab718e6b51a0e60721e4; ?>
<?php unset($__attributesOriginal50233b75799bab718e6b51a0e60721e4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50233b75799bab718e6b51a0e60721e4)): ?>
<?php $component = $__componentOriginal50233b75799bab718e6b51a0e60721e4; ?>
<?php unset($__componentOriginal50233b75799bab718e6b51a0e60721e4); ?>
<?php endif; ?>
        </a>
    <?php elseif($value !== ''): ?>
        <?php if (isset($component)) { $__componentOriginal50233b75799bab718e6b51a0e60721e4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal50233b75799bab718e6b51a0e60721e4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.highlight-match','data' => ['text' => $value,'term' => $term]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('highlight-match'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($value),'term' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($term)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal50233b75799bab718e6b51a0e60721e4)): ?>
<?php $attributes = $__attributesOriginal50233b75799bab718e6b51a0e60721e4; ?>
<?php unset($__attributesOriginal50233b75799bab718e6b51a0e60721e4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal50233b75799bab718e6b51a0e60721e4)): ?>
<?php $component = $__componentOriginal50233b75799bab718e6b51a0e60721e4; ?>
<?php unset($__componentOriginal50233b75799bab718e6b51a0e60721e4); ?>
<?php endif; ?>
    <?php else: ?>
        —
    <?php endif; ?>
</td>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/asset-table-cell.blade.php ENDPATH**/ ?>
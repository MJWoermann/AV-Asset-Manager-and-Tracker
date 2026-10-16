<?php
    $alternate = (bool) ($alternate ?? false);
    $rowClasses = $alternate
        ? 'bg-[#cce8eb] dark:bg-[#33494c] border-l-[3px] border-l-brand'
        : 'bg-[#e5f5f6] dark:bg-[#2b3c3e] border-l-[3px] border-l-brand';
    $otherModels = collect($allModels ?? [])->where('id', '!=', $manufacturerModel->id)->values();
?>
<li class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0">
    <div class="<?php echo e($rowClasses); ?> px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <span class="font-medium"><?php echo e($manufacturerModel->name); ?></span>
            <span class="text-brand-charcoal dark:text-brand-silver">
                — Model (<?php echo e($manufacturerModel->assets_count); ?> asset<?php echo e($manufacturerModel->assets_count === 1 ? '' : 's'); ?>)
            </span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a
                href="<?php echo e(route('assets.index', ['manufacturer_model_id' => $manufacturerModel->id])); ?>"
                class="text-sm text-brand"
            >Assets</a>
            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                <a href="<?php echo e(route('manufacturers.models.edit', [$manufacturer, $manufacturerModel])); ?>" class="text-sm text-brand">Edit</a>
                <?php if($otherModels->isNotEmpty()): ?>
                    <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                    <button
                        type="button"
                        class="text-sm text-red-700"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'delete-model-<?php echo e($manufacturerModel->id); ?>')"
                    >Delete</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
        <?php if($otherModels->isNotEmpty()): ?>
            <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'delete-model-'.e($manufacturerModel->id).'','show' => $errors->has('replacement_manufacturer_model_id') && (int) old('deleting_model_id') === (int) $manufacturerModel->id,'focusable' => true,'maxWidth' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'delete-model-'.e($manufacturerModel->id).'','show' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->has('replacement_manufacturer_model_id') && (int) old('deleting_model_id') === (int) $manufacturerModel->id),'focusable' => true,'maxWidth' => 'md']); ?>
                <form method="POST" action="<?php echo e(route('manufacturers.models.destroy', [$manufacturer, $manufacturerModel])); ?>" class="p-6 space-y-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <input type="hidden" name="deleting_model_id" value="<?php echo e($manufacturerModel->id); ?>">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “<?php echo e($manufacturer->name); ?> / <?php echo e($manufacturerModel->name); ?>”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        <?php if($manufacturerModel->assets_count > 0): ?>
                            This model is used by <?php echo e($manufacturerModel->assets_count); ?> <?php echo e($manufacturerModel->assets_count === 1 ? 'asset' : 'assets'); ?>.
                            Choose a replacement model for those assets, then delete.
                        <?php else: ?>
                            No assets use this model. Choose a replacement model, then delete.
                        <?php endif; ?>
                    </p>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'replacement_manufacturer_model_id_'.e($manufacturerModel->id).'','value' => 'Replacement model']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'replacement_manufacturer_model_id_'.e($manufacturerModel->id).'','value' => 'Replacement model']); ?>
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
                            id="replacement_manufacturer_model_id_<?php echo e($manufacturerModel->id); ?>"
                            name="replacement_manufacturer_model_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            required
                        >
                            <option value="">Select a model…</option>
                            <?php $__currentLoopData = $otherModels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($option['id']); ?>" <?php if((string) old('replacement_manufacturer_model_id') === (string) $option['id'] && (int) old('deleting_model_id') === (int) $manufacturerModel->id): echo 'selected'; endif; ?>>
                                    <?php echo e($option['label']); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('replacement_manufacturer_model_id'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('replacement_manufacturer_model_id')),'class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                    </div>
                    <div class="flex justify-end gap-2">
                        <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','xOn:click' => '$dispatch(\'close\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','x-on:click' => '$dispatch(\'close\')']); ?>Cancel <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.danger-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('danger-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Delete permanently <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $attributes = $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $component = $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
<?php endif; ?>
                    </div>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</li>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/manufacturers/partials/model-node.blade.php ENDPATH**/ ?>
<?php
    $modelsByManufacturer = $modelsByManufacturer ?? [];
    $initialManufacturerId = old('manufacturer_id', $spare->manufacturer_id);
    $initialModelId = old('manufacturer_model_id', $spare->manufacturer_model_id);
    $compatibleSelected = collect(old('compatible_model_ids', $spare->exists ? $spare->compatibleModels->pluck('id')->all() : []));
?>

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
        <h2 class="font-semibold text-xl"><?php echo e($spare->exists ? 'Edit spare' : 'New spare'); ?></h2>
     <?php $__env->endSlot(); ?>

    <div class="max-w-3xl mx-auto px-4 py-4">
        <form method="post" action="<?php echo e($spare->exists ? route('spares.update', $spare) : route('spares.store')); ?>" class="space-y-4 text-sm">
            <?php echo csrf_field(); ?>
            <?php if($spare->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-brand-charcoal dark:text-brand-silver">Code (Luhn)</label>
                    <input name="code" value="<?php echo e(old('code', $spare->code)); ?>" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1 font-mono" required>
                    <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-xs">Kind</label>
                    <select name="kind" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                        <?php $__currentLoopData = \App\Enums\SparePartKind::options(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('kind', $spare->kind?->value) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs">Component / description</label>
                    <input name="component" value="<?php echo e(old('component', $spare->component)); ?>" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1" required>
                </div>

                <div
                    class="sm:col-span-2 grid sm:grid-cols-2 gap-3"
                    x-data="catalogueManufacturerModelFields({
                        manufacturerId: <?php echo \Illuminate\Support\Js::from($initialManufacturerId !== null && $initialManufacturerId !== '' ? (string) $initialManufacturerId : '')->toHtml() ?>,
                        modelId: <?php echo \Illuminate\Support\Js::from($initialModelId !== null && $initialModelId !== '' ? (string) $initialModelId : '')->toHtml() ?>,
                        modelsByManufacturer: <?php echo \Illuminate\Support\Js::from($modelsByManufacturer)->toHtml() ?>,
                    })"
                    @searchable-select-changed="onSearchableChanged($event.detail)"
                >
                    <div>
                        <label class="block text-xs" for="manufacturer_id">Manufacturer</label>
                        <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'manufacturer_id','id' => 'manufacturer_id','options' => $manufacturers->map(fn ($manufacturer) => ['value' => $manufacturer->id, 'label' => $manufacturer->name]),'selected' => $initialManufacturerId,'nullable' => true,'createOption' => ['value' => '__new__', 'label' => '+ Add new manufacturer…']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'manufacturer_id','id' => 'manufacturer_id','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($manufacturers->map(fn ($manufacturer) => ['value' => $manufacturer->id, 'label' => $manufacturer->name])),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($initialManufacturerId),'nullable' => true,'create-option' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['value' => '__new__', 'label' => '+ Add new manufacturer…'])]); ?>
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
                        <?php $__errorArgs = ['manufacturer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <div class="mt-2" x-show="manufacturerId === '__new__'" x-cloak>
                            <label class="block text-xs" for="new_manufacturer">New manufacturer name</label>
                            <input
                                id="new_manufacturer"
                                name="new_manufacturer"
                                value="<?php echo e(old('new_manufacturer')); ?>"
                                class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1"
                            >
                            <?php $__errorArgs = ['new_manufacturer'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs" for="manufacturer_model_id">Model</label>
                        <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['xRef' => 'modelSelect','name' => 'manufacturer_model_id','id' => 'manufacturer_model_id','options' => [],'selected' => $initialModelId,'nullable' => true,'disabled' => blank($initialManufacturerId),'createOption' => ['value' => '__new__', 'label' => '+ Add new model…']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-ref' => 'modelSelect','name' => 'manufacturer_model_id','id' => 'manufacturer_model_id','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([]),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($initialModelId),'nullable' => true,'disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(blank($initialManufacturerId)),'create-option' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['value' => '__new__', 'label' => '+ Add new model…'])]); ?>
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
                        <?php $__errorArgs = ['manufacturer_model_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <div class="mt-2" x-show="modelId === '__new__'" x-cloak>
                            <label class="block text-xs" for="new_manufacturer_model">New model name</label>
                            <input
                                id="new_manufacturer_model"
                                name="new_manufacturer_model"
                                value="<?php echo e(old('new_manufacturer_model')); ?>"
                                class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1"
                            >
                            <?php $__errorArgs = ['new_manufacturer_model'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs">Product code</label>
                    <input name="product_code" value="<?php echo e(old('product_code', $spare->product_code)); ?>" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs">Severity</label>
                    <select name="severity" class="w-full border border-brand-silver dark:border-brand-charcoal px-2 py-1">
                        <?php $__currentLoopData = \App\Enums\SparePartSeverity::options(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('severity', $spare->severity?->value ?? 'moderate') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>

            <fieldset class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-2">
                <legend class="px-1 font-medium">Holdings</legend>
                <div class="grid sm:grid-cols-3 gap-3">
                    <?php if(! $spare->exists): ?>
                        <div>
                            <label class="block text-xs">Opening on hand</label>
                            <input type="number" min="0" name="quantity_on_hand" value="<?php echo e(old('quantity_on_hand', 0)); ?>" class="w-full border px-2 py-1">
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-xs">Deployed (manual)</label>
                        <input type="number" min="0" name="quantity_deployed" value="<?php echo e(old('quantity_deployed', $spare->quantity_deployed)); ?>" class="w-full border px-2 py-1">
                    </div>
                    <div>
                        <label class="block text-xs">Min holding</label>
                        <input type="number" min="0" name="min_holding" value="<?php echo e(old('min_holding', $spare->min_holding)); ?>" class="w-full border px-2 py-1">
                    </div>
                    <div>
                        <label class="block text-xs">Max holding</label>
                        <input type="number" min="0" name="max_holding" value="<?php echo e(old('max_holding', $spare->max_holding)); ?>" class="w-full border px-2 py-1">
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs" for="location_id">Stock location</label>
                        <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'location_id','id' => 'location_id','options' => $locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name]),'selected' => old('location_id', $spare->location_id),'nullable' => true,'placeholder' => 'Search locations…']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'location_id','id' => 'location_id','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('location_id', $spare->location_id)),'nullable' => true,'placeholder' => 'Search locations…']); ?>
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
                        <?php $__errorArgs = ['location_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-xs">Shelf / bin</label>
                        <input name="shelf_location" value="<?php echo e(old('shelf_location', $spare->shelf_location)); ?>" class="w-full border px-2 py-1">
                    </div>
                </div>
            </fieldset>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs">Supplier</label>
                    <input name="supplier" value="<?php echo e(old('supplier', $spare->supplier)); ?>" class="w-full border px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs">Unit cost</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" value="<?php echo e(old('unit_cost', $spare->unit_cost)); ?>" class="w-full border px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs">Lead time (text)</label>
                    <input name="lead_time" value="<?php echo e(old('lead_time', $spare->lead_time)); ?>" class="w-full border px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs">Replacement time (text)</label>
                    <input name="replacement_time" value="<?php echo e(old('replacement_time', $spare->replacement_time)); ?>" class="w-full border px-2 py-1">
                </div>
            </div>

            <div>
                <label class="block text-xs">Notes</label>
                <textarea name="notes" rows="3" class="w-full border px-2 py-1"><?php echo e(old('notes', $spare->notes)); ?></textarea>
            </div>

            <div>
                <label class="block text-xs mb-1" for="compatible_model_ids">Also fits models</label>
                <?php if (isset($component)) { $__componentOriginal227c52c3d00b64b3a49ab671736e16cd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal227c52c3d00b64b3a49ab671736e16cd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-multi-select','data' => ['name' => 'compatible_model_ids[]','id' => 'compatible_model_ids','options' => $models->map(fn ($model) => [
                        'value' => $model->id,
                        'label' => trim(($model->manufacturer?->name ?? '').' — '.$model->name, ' —'),
                    ]),'selected' => $compatibleSelected,'placeholder' => 'Search models…']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-multi-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'compatible_model_ids[]','id' => 'compatible_model_ids','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($models->map(fn ($model) => [
                        'value' => $model->id,
                        'label' => trim(($model->manufacturer?->name ?? '').' — '.$model->name, ' —'),
                    ])),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($compatibleSelected),'placeholder' => 'Search models…']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal227c52c3d00b64b3a49ab671736e16cd)): ?>
<?php $attributes = $__attributesOriginal227c52c3d00b64b3a49ab671736e16cd; ?>
<?php unset($__attributesOriginal227c52c3d00b64b3a49ab671736e16cd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal227c52c3d00b64b3a49ab671736e16cd)): ?>
<?php $component = $__componentOriginal227c52c3d00b64b3a49ab671736e16cd; ?>
<?php unset($__componentOriginal227c52c3d00b64b3a49ab671736e16cd); ?>
<?php endif; ?>
                <?php $__errorArgs = ['compatible_model_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['compatible_model_ids.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                <div>
                    <label class="block text-xs">Preferred label layout</label>
                    <select name="shelf_tag_layout_id" class="w-full border px-2 py-1">
                        <option value="">Default</option>
                        <?php $__currentLoopData = $layouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $layout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($layout->id); ?>" <?php if((string) old('shelf_tag_layout_id', $spare->shelf_tag_layout_id) === (string) $layout->id): echo 'selected'; endif; ?>><?php echo e($layout->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="flex justify-end gap-2">
                <a href="<?php echo e($spare->exists ? route('spares.show', $spare) : route('spares.index')); ?>" class="px-4 py-2 border">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-brand text-white">Save</button>
            </div>
        </form>
    </div>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/spares/form.blade.php ENDPATH**/ ?>
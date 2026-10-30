<?php
    use App\Support\ShelfTagPaperPresets;

    $editorSettingsRaw = old('editor_settings', $layout->editor_settings);
    if (is_string($editorSettingsRaw)) {
        $editorSettingsRaw = json_decode($editorSettingsRaw, true);
    }
    $initial = [
        'name' => old('name', $layout->name),
        'paper_preset' => old('paper_preset', $layout->paper_preset),
        'width_mm' => (float) old('width_mm', $layout->width_mm),
        'height_mm' => (float) old('height_mm', $layout->height_mm),
        'orientation' => old('orientation', $layout->orientation ?: 'portrait'),
        'length_mode' => old('length_mode', $layout->length_mode ?: ShelfTagPaperPresets::LENGTH_MODE_FIXED),
        'continuous_narrow_max_width_mm' => ShelfTagPaperPresets::CONTINUOUS_NARROW_MAX_WIDTH_MM,
        'continuous_length_min_narrow_mm' => ShelfTagPaperPresets::CONTINUOUS_LENGTH_MIN_NARROW_MM,
        'continuous_length_min_wide_mm' => ShelfTagPaperPresets::CONTINUOUS_LENGTH_MIN_WIDE_MM,
        'continuous_length_max_mm' => ShelfTagPaperPresets::CONTINUOUS_LENGTH_MAX_MM,
        'elements' => old('elements')
            ? (is_string(old('elements')) ? json_decode(old('elements'), true) : old('elements'))
            : ($layout->elements ?? []),
        'logo_url' => $layout->logoUrl(),
        'logo_library_url' => route('labels.layouts.logo-library'),
        'editor_settings' => ShelfTagPaperPresets::normalizeEditorSettings(
            is_array($editorSettingsRaw) ? $editorSettingsRaw : null
        ),
    ];
    if (! is_array($initial['elements'])) {
        $initial['elements'] = [];
    }
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
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl"><?php echo e($layout->exists ? 'Edit label layout' : 'New label layout'); ?></h2>
            <div class="flex items-center gap-3">
                <?php if($layout->exists): ?>
                    <form method="POST" action="<?php echo e(route('labels.layouts.duplicate', $layout)); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="text-sm text-brand">Duplicate</button>
                    </form>
                <?php endif; ?>
                <a href="<?php echo e(route('labels.index')); ?>" class="text-sm text-brand-charcoal dark:text-brand-silver">Back to labels</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8"
        x-data="shelfTagDesigner(<?php echo \Illuminate\Support\Js::from($initial)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($presets)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($fieldOptions)->toHtml() ?>)"
        @keydown.window="onKeyDown($event)"
        @keyup.window="onKeyUp($event)"
    >
        <?php if(session('status')): ?>
            <p class="mb-3 text-sm text-brand"><?php echo e(session('status')); ?></p>
        <?php endif; ?>
        <?php if(session('import_warnings')): ?>
            <div class="mb-3 border border-amber-500/40 bg-amber-50 dark:bg-amber-950/30 p-3 text-sm text-amber-900 dark:text-amber-100 space-y-1">
                <p class="font-medium">Import notes</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    <?php $__currentLoopData = (array) session('import_warnings'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warning): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($warning); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="mb-3 text-sm text-red-700 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
            action="<?php echo e($layout->exists ? route('labels.layouts.update', $layout) : route('labels.layouts.store')); ?>"
            class="space-y-4"
            @submit="syncFormFields()"
        >
            <?php echo csrf_field(); ?>
            <?php if($layout->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>
            <input type="hidden" name="elements" x-ref="elementsInput" :value="elementsJson">
            <input type="hidden" name="editor_settings" x-ref="editorSettingsInput" :value="editorSettingsJson">
            <input type="hidden" name="length_mode" :value="lengthMode">

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <div class="xl:col-span-3 space-y-3 bg-white dark:bg-brand-slate border p-3">
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'name','value' => 'Name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'name','value' => 'Name']); ?>
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
                        <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'name','name' => 'name','class' => 'mt-1 block w-full','xModel' => 'name','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'name','name' => 'name','class' => 'mt-1 block w-full','x-model' => 'name','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'paper_preset','value' => 'Paper preset']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'paper_preset','value' => 'Paper preset']); ?>
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
                            id="paper_preset"
                            name="paper_preset"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            x-model="paperPreset"
                            @change="onPresetChange()"
                        >
                            <template x-for="(preset, key) in presets" :key="key">
                                <option :value="key" x-text="preset.label" :selected="paperPreset === key"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-text="presetNotes"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'width_mm','value' => 'Width (mm)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'width_mm','value' => 'Width (mm)']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'width_mm','name' => 'width_mm','type' => 'number','step' => '0.1','min' => '10','class' => 'mt-1 block w-full','xModel.number' => 'widthMm','xOn:change' => 'onWidthManualEdit()','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'width_mm','name' => 'width_mm','type' => 'number','step' => '0.1','min' => '10','class' => 'mt-1 block w-full','x-model.number' => 'widthMm','x-on:change' => 'onWidthManualEdit()','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'height_mm','value' => 'Height / length (mm)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'height_mm','value' => 'Height / length (mm)']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'height_mm','name' => 'height_mm','type' => 'number','step' => '0.1','class' => 'mt-1 block w-full','xModel.number' => 'heightMm','xOn:change' => 'onHeightManualEdit()','xBind:min' => 'isContinuous ? continuousMinMm : 10','xBind:readonly' => 'isContinuous && lengthMode === "auto"','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'height_mm','name' => 'height_mm','type' => 'number','step' => '0.1','class' => 'mt-1 block w-full','x-model.number' => 'heightMm','x-on:change' => 'onHeightManualEdit()','x-bind:min' => 'isContinuous ? continuousMinMm : 10','x-bind:readonly' => 'isContinuous && lengthMode === "auto"','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                        </div>
                    </div>
                    <div x-show="isContinuous" x-cloak>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'length_mode_ui','value' => 'Label length']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'length_mode_ui','value' => 'Label length']); ?>
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
                            id="length_mode_ui"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            x-model="lengthMode"
                            @change="onLengthModeChange()"
                        >
                            <option value="fixed">Fixed</option>
                            <option value="auto">Auto (fit content)</option>
                        </select>
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                            Auto sizes length from the lowest element (+<?php echo e(2); ?> mm pad). Minimum length is <?php echo e(ShelfTagPaperPresets::CONTINUOUS_LENGTH_MIN_NARROW_MM); ?> mm at <?php echo e(ShelfTagPaperPresets::CONTINUOUS_NARROW_MAX_WIDTH_MM); ?> mm wide or narrower, and <?php echo e(ShelfTagPaperPresets::CONTINUOUS_LENGTH_MIN_WIDE_MM); ?> mm when wider (maximum <?php echo e(ShelfTagPaperPresets::CONTINUOUS_LENGTH_MAX_MM); ?> mm).
                            Dragging past the bottom also extends the label.
                        </p>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'orientation','value' => 'Orientation']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'orientation','value' => 'Orientation']); ?>
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
                        <select id="orientation" name="orientation" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" x-model="orientation">
                            <option value="portrait">Portrait</option>
                            <option value="landscape">Landscape</option>
                        </select>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'logo','value' => 'Logo (jpg/png, max 2MB)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'logo','value' => 'Logo (jpg/png, max 2MB)']); ?>
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
                        <input type="hidden" name="logo_file_id" :value="selectedLogoFileId || ''">
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm" x-ref="logoUpload" @change="onLogoUploadChosen()">
                        <button type="button" class="mt-2 text-sm text-brand" @click="openLogoLibrary()">Choose an uploaded image</button>
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Uses an image already in Files. Uploading the same picture again reuses that file.</p>
                        <template x-if="logoUrl">
                            <div class="mt-2 space-y-2">
                                <img :src="logoUrl" alt="Current logo" class="h-16 max-w-full object-contain border bg-white">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded text-brand" x-ref="removeLogo">
                                    Remove current logo
                                </label>
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        In the Brother driver, pick the matching DK size, Actual size, and Cut every for continuous media.
                    </p>
                </div>

                <div class="xl:col-span-7 space-y-3">
                    <div class="bg-white dark:bg-brand-slate border p-3">
                        <div class="flex flex-wrap gap-2 mb-3 text-sm">
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('text')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7V5h16v2M9 19h6M12 5v14" />
                                </svg>
                                Text
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('field')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7h16M4 12h10M4 17h16" />
                                </svg>
                                Field
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('barcode')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6v12M7 6v12M10 6v12M12 6v12M15 6v12M18 6v12M20 6v12" />
                                </svg>
                                Barcode
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('qr')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 3h3v3h-3v-3zm4-3h3v3h-3v-3zm-4 0h3v2h-3v-2zm4 4h3v3h-3v-3z" />
                                </svg>
                                QR
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('rect')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="5" y="6" width="14" height="12" stroke-width="1.75" />
                                </svg>
                                Rect
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('line')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-width="1.75" d="M4 12h16" />
                                </svg>
                                Line
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('logo')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.5-4.5a2 2 0 012.8 0L16 16m-2-2l1.5-1.5a2 2 0 012.8 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Logo
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3 text-sm border-t border-brand-silver dark:border-brand-charcoal pt-3">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" class="rounded text-brand" x-model="showGrid">
                                Show grid
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" class="rounded text-brand" x-model="snapToGrid">
                                Snap to grid
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <span class="text-brand-charcoal dark:text-brand-silver">Grid</span>
                                <select
                                    class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm py-1"
                                    x-model.number="gridSizeMm"
                                >
                                    <option :value="2.5">2.5 mm</option>
                                    <option :value="5">5 mm</option>
                                    <option :value="10">10 mm</option>
                                </select>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" class="rounded text-brand" x-model="showBleed">
                                Show bleed
                            </label>
                            <label class="inline-flex items-center gap-1.5">
                                <span class="text-brand-charcoal dark:text-brand-silver">Top</span>
                                <input type="number" min="0" max="50" step="0.1" class="w-16 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm py-1" x-model.number="bleedTopMm" aria-label="Bleed top">
                                <span class="text-brand-charcoal dark:text-brand-silver">mm</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5">
                                <span class="text-brand-charcoal dark:text-brand-silver">Bottom</span>
                                <input type="number" min="0" max="50" step="0.1" class="w-16 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm py-1" x-model.number="bleedBottomMm" aria-label="Bleed bottom">
                                <span class="text-brand-charcoal dark:text-brand-silver">mm</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5">
                                <span class="text-brand-charcoal dark:text-brand-silver">Left</span>
                                <input type="number" min="0" max="50" step="0.1" class="w-16 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm py-1" x-model.number="bleedLeftMm" aria-label="Bleed left">
                                <span class="text-brand-charcoal dark:text-brand-silver">mm</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5">
                                <span class="text-brand-charcoal dark:text-brand-silver">Right</span>
                                <input type="number" min="0" max="50" step="0.1" class="w-16 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm py-1" x-model.number="bleedRightMm" aria-label="Bleed right">
                                <span class="text-brand-charcoal dark:text-brand-silver">mm</span>
                            </label>
                        </div>

                        <div
                            class="overflow-hidden rounded border-2 border-brand-charcoal dark:border-brand-black bg-brand-charcoal dark:bg-black shadow-inner relative h-[min(70vh,560px)]"
                            x-ref="viewport"
                            :style="{ cursor: viewportCursor }"
                            @wheel="onViewportWheel($event)"
                            @pointerdown="onViewportPointerDown($event)"
                        >
                            <div class="absolute top-0 left-0" :style="viewportTransformStyle">
                                <div
                                    class="relative bg-white shadow-md ring-1 ring-black/20 overflow-visible"
                                    x-ref="canvas"
                                    :style="canvasStyle"
                                    @pointerdown.stop="onCanvasPointerDown($event)"
                                >
                                    <div class="relative" data-label-content-plane :style="labelContentStyle">
                                    <div
                                        x-show="showBleed && bleedFits"
                                        x-cloak
                                        class="shelf-tag-bleed-guide absolute pointer-events-none z-[3] border border-dashed border-brand-plum print:hidden"
                                        :style="bleedGuideStyle"
                                        aria-hidden="true"
                                    ></div>
                                    <div
                                        x-show="showGrid"
                                        class="absolute inset-0 pointer-events-none z-[1]"
                                        :style="gridOverlayStyle"
                                        aria-hidden="true"
                                    >
                                        <div class="absolute top-0 bottom-0 w-px bg-brand" :style="centerCrosshairStyleX"></div>
                                        <div class="absolute left-0 right-0 h-px bg-brand" :style="centerCrosshairStyleY"></div>
                                    </div>
                                    <div
                                        x-show="marquee"
                                        class="absolute z-[4] border border-brand bg-brand/10 pointer-events-none"
                                        :style="marqueeStyle"
                                    ></div>
                                    <template x-for="el in sortedElements" :key="el.id">
                                        <div
                                            class="absolute border border-transparent select-none z-[2]"
                                            :data-el-id="el.id"
                                            :class="isSelected(el) ? 'ring-2 ring-brand border-brand' : 'hover:border-brand-charcoal/40'"
                                            :style="elementStyle(el)"
                                            @pointerdown.stop="startDrag($event, el)"
                                        >
                                            <div class="w-full h-full overflow-hidden text-black" :style="elementInnerStyle(el)">
                                                <template x-if="el.type === 'text' || el.type === 'field'">
                                                    <span x-text="previewText(el)"></span>
                                                </template>
                                                <template x-if="el.type === 'barcode'">
                                                    <div class="w-full h-full flex items-center justify-center overflow-hidden">
                                                        <svg
                                                            class="shelf-tag-designer-barcode"
                                                            x-init="$watch(() => [el.width, el.height, el.props.symbology, el.props.showText, el.props.fieldKey], () => renderBarcode(el, $el)); $nextTick(() => renderBarcode(el, $el))"
                                                        ></svg>
                                                    </div>
                                                </template>
                                                <template x-if="el.type === 'qr'">
                                                    <div class="w-full h-full flex items-center justify-center overflow-hidden">
                                                        <canvas x-init="$watch(() => [el.width, el.height, el.props.fieldKey, el.props.content], () => renderQr(el, $el)); $nextTick(() => renderQr(el, $el))"></canvas>
                                                    </div>
                                                </template>
                                                <template x-if="el.type === 'logo'">
                                                    <template x-if="logoUrl">
                                                        <img :src="logoUrl" alt="Logo" class="w-full h-full object-contain">
                                                    </template>
                                                    <template x-if="!logoUrl">
                                                        <span class="text-[10px] text-brand-charcoal">Logo</span>
                                                    </template>
                                                </template>
                                                <template x-if="el.type === 'rect'">
                                                    <div class="w-full h-full" :style="rectStyle(el)"></div>
                                                </template>
                                                <template x-if="el.type === 'line'">
                                                    <div class="w-full" :style="lineStyle(el)"></div>
                                                </template>
                                            </div>
                                            <div
                                                class="absolute right-0 bottom-0 h-3 w-3 bg-brand cursor-se-resize"
                                                x-show="selectedCount === 1 && isSelected(el)"
                                                @pointerdown.stop="startResize($event, el)"
                                            ></div>
                                        </div>
                                    </template>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="absolute bottom-2 right-2 z-10 group"
                                @pointerdown.stop
                            >
                                <div class="flex items-center gap-0.5 rounded border border-brand-charcoal/30 dark:border-brand-silver/30 bg-white/90 dark:bg-brand-slate/90 px-1 py-0.5 shadow text-[10px] leading-none text-brand-charcoal dark:text-brand-silver backdrop-blur-sm">
                                    <button type="button" class="px-1.5 py-1 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal/60" @click="zoomOut()" title="Zoom out">−</button>
                                    <button type="button" class="px-1.5 py-1 min-w-[2.75rem] hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal/60 tabular-nums" @click="resetZoom()" x-text="zoomPercent + '%'"></button>
                                    <button type="button" class="px-1.5 py-1 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal/60" @click="zoomIn()" title="Zoom in">+</button>
                                    <button type="button" class="px-1.5 py-1 border-l border-brand-charcoal/20 dark:border-brand-silver/20 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal/60" @click="centerView()">Centre</button>
                                </div>
                                <div
                                    class="pointer-events-none absolute bottom-full right-0 mb-1.5 hidden w-52 rounded border border-brand-charcoal/20 bg-white px-2 py-1.5 text-[10px] leading-snug text-brand-charcoal shadow-md group-hover:block dark:border-brand-silver/20 dark:bg-brand-slate dark:text-brand-silver"
                                    role="tooltip"
                                >
                                    Ctrl+wheel to zoom. Space+drag or middle-click to pan. Click % to reset zoom.
                                </div>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-brand-charcoal dark:text-brand-silver">
                            Grid from label centre. Shift-click or drag-marquee to multi-select.
                            <span x-show="snapToGrid">Snapping to <span x-text="gridSizeMm"></span> mm.</span>
                            <span x-show="showGrid && !snapToGrid">Grid guides only.</span>
                            <span x-show="showBleed">Bleed guide is not printed.</span>
                            Barcodes scale to fit their box without cropping.
                        </p>
                    </div>
                </div>

                <div class="xl:col-span-2 space-y-3 bg-white dark:bg-brand-slate border p-3">
                    <h3 class="font-medium text-sm">
                        <template x-if="selectedCount > 1">
                            <span x-text="selectedCount + ' elements'"></span>
                        </template>
                        <template x-if="selectedCount <= 1">
                            <span>Selected element</span>
                        </template>
                    </h3>
                    <template x-if="selectedCount > 1">
                        <div class="space-y-2 text-sm">
                            <p class="text-brand-charcoal dark:text-brand-silver">Use Align tools below. Delete removes all selected.</p>
                            <button type="button" class="px-2 py-1 border text-xs text-red-700" @click="removeSelected()">Delete selected</button>
                        </div>
                    </template>
                    <template x-if="selectedCount === 0">
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">Select an element on the canvas.</p>
                    </template>
                    <template x-if="selected">
                        <div class="space-y-2 text-sm">
                            <div class="text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver" x-text="selected.type"></div>
                            <div class="grid grid-cols-2 gap-2">
                                <label>X
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.x" @change="afterGeometryChange([selected])">
                                </label>
                                <label>Y
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.y" @change="afterGeometryChange([selected])">
                                </label>
                                <label>W
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.width" @change="afterGeometryChange([selected])">
                                </label>
                                <label>H
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.height" @change="afterGeometryChange([selected])">
                                </label>
                            </div>

                            <template x-if="selected.type === 'text'">
                                <label class="block">Content
                                    <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.content">
                                </label>
                            </template>

                            <template x-if="['field','barcode','qr'].includes(selected.type)">
                                <label class="block">Field
                                    <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.fieldKey">
                                        <option value="">—</option>
                                        <template x-for="(label, key) in fieldOptions" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </select>
                                </label>
                            </template>

                            <template x-if="selected.type === 'qr'">
                                <label class="block">Static QR content (if no field)
                                    <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.content">
                                </label>
                            </template>

                            <template x-if="selected.type === 'barcode'">
                                <div class="space-y-2">
                                    <label class="block">Symbology
                                        <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.symbology">
                                            <option value="code128">Code 128</option>
                                            <option value="code39">Code 39</option>
                                        </select>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" class="rounded text-brand" x-model="selected.props.showText">
                                        Show text
                                    </label>
                                </div>
                            </template>

                            <template x-if="['text','field'].includes(selected.type)">
                                <div class="space-y-2">
                                    <label class="block">Font size
                                        <input type="number" min="6" max="72" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.props.fontSize">
                                    </label>
                                    <label class="block">Weight
                                        <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.fontWeight">
                                            <option value="normal">Normal</option>
                                            <option value="bold">Bold</option>
                                        </select>
                                    </label>
                                    <label class="block">Align
                                        <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.align">
                                            <option value="left">Left</option>
                                            <option value="center">Center</option>
                                            <option value="right">Right</option>
                                        </select>
                                    </label>
                                    <label class="block">Color
                                        <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.color">
                                            <option value="#000000">Black</option>
                                            <option value="#444444">Dark gray</option>
                                            <option value="#888888">Gray</option>
                                        </select>
                                    </label>
                                </div>
                            </template>

                            <template x-if="selected.type === 'rect'">
                                <div class="space-y-2">
                                    <label class="block">Stroke
                                        <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.stroke">
                                    </label>
                                    <label class="block">Fill
                                        <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.fill">
                                    </label>
                                </div>
                            </template>

                            <template x-if="selected.type === 'line'">
                                <label class="block">Stroke
                                    <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.stroke">
                                </label>
                            </template>

                            <div class="flex flex-wrap gap-2 pt-2">
                                <button type="button" class="px-2 py-1 border text-xs" @click="nudgeZ(1)">Bring forward</button>
                                <button type="button" class="px-2 py-1 border text-xs" @click="nudgeZ(-1)">Send back</button>
                                <button type="button" class="px-2 py-1 border text-xs text-red-700" @click="removeSelected()">Delete</button>
                            </div>
                        </div>
                    </template>

                    <div class="space-y-2 border-t border-brand-silver dark:border-brand-charcoal pt-3">
                        <h3 class="font-medium text-sm">Align</h3>
                        <div class="inline-flex border overflow-hidden w-full">
                            <button type="button" class="flex-1 px-1 py-1 text-[10px]" :class="alignTarget === 'group' ? 'bg-brand-charcoal text-white' : ''" @click="alignTarget = 'group'">Selection</button>
                            <button type="button" class="flex-1 px-1 py-1 text-[10px] border-l" :class="alignTarget === 'biggest' ? 'bg-brand-charcoal text-white' : ''" @click="alignTarget = 'biggest'">Biggest</button>
                            <button type="button" class="flex-1 px-1 py-1 text-[10px] border-l" :class="alignTarget === 'page' ? 'bg-brand-charcoal text-white' : ''" @click="alignTarget = 'page'">Page</button>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('left')" title="Align left">⇤</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('center')" title="Align centre H">↔</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('right')" title="Align right">⇥</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('top')" title="Align top">⤒</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('middle')" title="Align middle V">↕</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('bottom')" title="Align bottom">⤓</button>
                        </div>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver" x-show="selectedCount > 0" x-text="selectedCount + ' selected'"></p>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver" x-show="alignTarget !== 'page' && selectedCount === 1">Shift-click or marquee for multi-select</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="<?php echo e(route('labels.index')); ?>" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Save layout <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
            </div>

            <div
                x-show="logoLibraryOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0"
                @keydown.escape.window="closeLogoLibrary()"
            >
                <div class="fixed inset-0 bg-gray-500 opacity-75" @click="closeLogoLibrary()"></div>
                <div
                    class="relative w-full sm:max-w-2xl max-h-[calc(100vh-3rem)] overflow-y-auto bg-white dark:bg-brand-slate dark:text-white rounded-lg shadow-xl sm:mx-auto"
                    @click.stop
                >
                    <div class="p-6 space-y-4">
                        <h2 class="text-lg font-medium">Choose an uploaded image</h2>
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">JPEG and PNG files already in Files. Picking one does not upload another copy.</p>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'logo_library_search','value' => 'Search']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'logo_library_search','value' => 'Search']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'logo_library_search','type' => 'search','class' => 'mt-1 block w-full','xModel' => 'logoLibraryQuery','@input.debounce.300ms' => 'searchLogoLibrary()','@keydown.enter.prevent' => 'searchLogoLibrary()','placeholder' => 'Search by title or filename','autocomplete' => 'off']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'logo_library_search','type' => 'search','class' => 'mt-1 block w-full','x-model' => 'logoLibraryQuery','@input.debounce.300ms' => 'searchLogoLibrary()','@keydown.enter.prevent' => 'searchLogoLibrary()','placeholder' => 'Search by title or filename','autocomplete' => 'off']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                            <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-show="logoLibrarySearching" x-cloak>Searching…</p>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" x-show="logoLibraryResults.length" x-cloak>
                            <template x-for="file in logoLibraryResults" :key="file.id">
                                <button type="button" class="border p-1 text-left hover:border-brand" @click="pickLogo(file)">
                                    <img :src="file.url" alt="" class="h-16 w-full object-contain bg-white">
                                    <span class="mt-1 block text-xs truncate" x-text="file.title"></span>
                                </button>
                            </template>
                        </div>
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="!logoLibrarySearching && logoLibraryResults.length === 0" x-cloak>No JPEG or PNG images in Files yet.</p>
                        <div class="flex justify-end">
                            <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','@click' => 'closeLogoLibrary()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','@click' => 'closeLogoLibrary()']); ?>Close <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                        </div>
                    </div>
                </div>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/shelf-tags/layouts/form.blade.php ENDPATH**/ ?>
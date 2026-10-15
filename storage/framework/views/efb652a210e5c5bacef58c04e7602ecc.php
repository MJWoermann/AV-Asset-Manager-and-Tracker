<?php
    $initial = [
        'name' => old('name', $layout->name),
        'paper_preset' => old('paper_preset', $layout->paper_preset),
        'width_mm' => (float) old('width_mm', $layout->width_mm),
        'height_mm' => (float) old('height_mm', $layout->height_mm),
        'orientation' => old('orientation', $layout->orientation ?: 'portrait'),
        'elements' => old('elements')
            ? (is_string(old('elements')) ? json_decode(old('elements'), true) : old('elements'))
            : ($layout->elements ?? []),
        'logo_url' => $layout->logoUrl(),
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
            <h2 class="font-semibold text-xl"><?php echo e($layout->exists ? 'Edit shelf tag layout' : 'New shelf tag layout'); ?></h2>
            <a href="<?php echo e(route('shelf-tags.layouts.index')); ?>" class="text-sm text-brand-charcoal dark:text-brand-silver">Back to layouts</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8"
        x-data="shelfTagDesigner(<?php echo \Illuminate\Support\Js::from($initial)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($presets)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($fieldOptions)->toHtml() ?>)"
    >
        <?php if(session('status')): ?>
            <p class="mb-3 text-sm text-brand"><?php echo e(session('status')); ?></p>
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
            action="<?php echo e($layout->exists ? route('shelf-tags.layouts.update', $layout) : route('shelf-tags.layouts.store')); ?>"
            class="space-y-4"
            @submit="syncElementsJson()"
        >
            <?php echo csrf_field(); ?>
            <?php if($layout->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>
            <input type="hidden" name="elements" x-ref="elementsInput" :value="elementsJson">

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'width_mm','name' => 'width_mm','type' => 'number','step' => '0.1','min' => '10','class' => 'mt-1 block w-full','xModel.number' => 'widthMm','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'width_mm','name' => 'width_mm','type' => 'number','step' => '0.1','min' => '10','class' => 'mt-1 block w-full','x-model.number' => 'widthMm','required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'height_mm','name' => 'height_mm','type' => 'number','step' => '0.1','min' => '10','class' => 'mt-1 block w-full','xModel.number' => 'heightMm','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'height_mm','name' => 'height_mm','type' => 'number','step' => '0.1','min' => '10','class' => 'mt-1 block w-full','x-model.number' => 'heightMm','required' => true]); ?>
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
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm">
                        <template x-if="logoUrl">
                            <label class="mt-2 inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded text-brand">
                                Remove current logo
                            </label>
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
                        </div>

                        <div class="overflow-auto rounded border-2 border-brand-charcoal dark:border-brand-black bg-brand-charcoal dark:bg-black p-5 shadow-inner">
                            <div
                                class="relative bg-white shadow-md mx-auto ring-1 ring-black/20"
                                :style="canvasStyle"
                                @pointerdown.self="selectedId = null"
                            >
                                <div
                                    x-show="showGrid"
                                    class="absolute inset-0 pointer-events-none z-[1]"
                                    :style="gridOverlayStyle"
                                    aria-hidden="true"
                                >
                                    <div class="absolute top-0 bottom-0 w-px bg-brand" :style="centerCrosshairStyleX"></div>
                                    <div class="absolute left-0 right-0 h-px bg-brand" :style="centerCrosshairStyleY"></div>
                                </div>
                                <template x-for="el in sortedElements" :key="el.id">
                                    <div
                                        class="absolute border border-transparent select-none z-[2]"
                                        :data-el-id="el.id"
                                        :class="selectedId === el.id ? 'ring-2 ring-brand border-brand' : 'hover:border-brand-charcoal/40'"
                                        :style="elementStyle(el)"
                                        @pointerdown.stop="startDrag($event, el)"
                                    >
                                        <div class="w-full h-full overflow-hidden text-black" :style="elementInnerStyle(el)">
                                            <template x-if="el.type === 'text' || el.type === 'field'">
                                                <span x-text="previewText(el)"></span>
                                            </template>
                                            <template x-if="el.type === 'barcode'">
                                                <svg class="w-full h-full" x-ref="'barcode-' + el.id" x-init="$nextTick(() => renderBarcode(el, $el))"></svg>
                                            </template>
                                            <template x-if="el.type === 'qr'">
                                                <canvas class="w-full h-full" x-init="$nextTick(() => renderQr(el, $el))"></canvas>
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
                                            x-show="selectedId === el.id"
                                            @pointerdown.stop="startResize($event, el)"
                                        ></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-brand-charcoal dark:text-brand-silver">
                            Canvas scale: <span x-text="scale.toFixed(1)"></span>px per mm.
                            Grid originates from the label centre.
                            Drag to move, corner handle to resize.
                            <span x-show="snapToGrid">Snapping to <span x-text="gridSizeMm"></span> mm grid.</span>
                            <span x-show="showGrid && !snapToGrid">Grid shown as guides only.</span>
                        </p>
                    </div>
                </div>

                <div class="xl:col-span-2 space-y-3 bg-white dark:bg-brand-slate border p-3">
                    <h3 class="font-medium text-sm">Selected element</h3>
                    <template x-if="!selected">
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">Select an element on the canvas.</p>
                    </template>
                    <template x-if="selected">
                        <div class="space-y-2 text-sm">
                            <div class="text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver" x-text="selected.type"></div>
                            <div class="grid grid-cols-2 gap-2">
                                <label>X
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.x">
                                </label>
                                <label>Y
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.y">
                                </label>
                                <label>W
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.width">
                                </label>
                                <label>H
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.height">
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
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
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
                <a href="<?php echo e(route('shelf-tags.layouts.index')); ?>" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
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
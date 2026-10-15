<?php
    $capacity = (int) ($asset->ru_height ?? 0);
    $rearEnabled = (bool) $asset->ru_rear_enabled;
    $placements = $ruPlacementsPayload ?? [];
    $childOptions = $ruChildAssetOptions ?? [];
    $unplacedChildren = $ruUnplacedChildren ?? collect();
    $hasParentConfirmError = $errors->has('confirm_parent_change');
    $oldKind = old('kind', 'asset');
    $openPanel = ($hasParentConfirmError || ($errors->any() && old('start_ru') !== null))
        ? (old('_placement_id') ? 'edit' : 'add')
        : null;
    $editingId = old('_placement_id') ? (int) old('_placement_id') : null;
    $pendingChild = collect($childOptions)->first(
        fn ($option) => (string) ($option['value'] ?? '') === (string) old('child_asset_id')
    );
?>

<div
    class="space-y-6"
    x-data="ruVisualiser(<?php echo \Illuminate\Support\Js::from([
        'capacity' => $capacity,
        'rearEnabled' => $rearEnabled,
        'initialFace' => old('face', 'front'),
        'placements' => $placements,
        'childOptions' => $childOptions,
        'storeUrl' => route('assets.ru-placements.store', $asset),
        'rackId' => (string) $asset->id,
        'showParentConfirm' => $hasParentConfirmError,
        'pendingParentName' => $pendingChild['parent_name'] ?? '',
        'openPanel' => $openPanel,
        'editingId' => $editingId,
        'old' => [
            'face' => old('face', 'front'),
            'start_ru' => old('start_ru'),
            'ru_height' => old('ru_height'),
            'full_depth' => (bool) old('full_depth', false),
            'kind' => $oldKind,
            'child_asset_id' => old('child_asset_id', ''),
            'note' => old('note', ''),
            'confirm_parent_change' => old('confirm_parent_change', false),
        ],
    ])->toHtml() ?>)"
>
    <?php if(session('status')): ?>
        <p class="text-sm text-brand"><?php echo e(session('status')); ?></p>
    <?php endif; ?>

    <?php if($errors->any() && ! $hasParentConfirmError): ?>
        <div class="text-sm text-red-600 space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <p><?php echo e($error); ?></p>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <?php if($capacity < 1): ?>
        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-3">
            <h3 class="font-semibold text-lg">Set RU height</h3>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                This rack needs a capacity before you can place equipment or notes.
            </p>
            <a href="<?php echo e(route('assets.edit', $asset)); ?>" class="inline-flex px-3 py-2 bg-brand text-white text-sm">
                Edit asset to set RU height
            </a>
        </div>
    <?php else: ?>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-lg">RU visualiser</h3>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    <?php echo e($capacity); ?> RU · numbering bottom-up (RU 1 at bottom)
                    <?php if($rearEnabled): ?>
                        · rear face enabled
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="px-3 py-2 text-sm border border-brand-charcoal dark:border-white"
                    :class="face === 'front' ? 'bg-brand text-white border-brand' : ''"
                    @click="face = 'front'"
                >
                    Front
                </button>
                <?php if($rearEnabled): ?>
                    <button
                        type="button"
                        class="px-3 py-2 text-sm border border-brand-charcoal dark:border-white"
                        :class="face === 'rear' ? 'bg-brand text-white border-brand' : ''"
                        @click="face = 'rear'"
                    >
                        Rear
                    </button>
                <?php endif; ?>
                <button
                    type="button"
                    class="px-3 py-2 text-sm bg-brand text-white"
                    @click="openAdd()"
                >
                    Add placement
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-2">
                <?php echo $__env->make('assets.partials.ru-diagram', ['interactive' => true, 'compact' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <div class="space-y-4">
                <div
                    x-show="panel"
                    x-cloak
                    class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold" x-text="panel === 'edit' ? 'Edit placement' : 'Add placement'"></h4>
                        <button type="button" class="text-sm text-brand-charcoal dark:text-brand-silver" @click="closePanel()">Close</button>
                    </div>

                    <form
                        method="POST"
                        x-ref="placementForm"
                        :action="panel === 'edit' && editingPlacement() ? editingPlacement().update_url : storeUrl"
                        @submit="prepareSubmit($event)"
                        class="space-y-3"
                    >
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="_method" value="PUT" x-bind:disabled="panel !== 'edit'">
                        <input type="hidden" name="_placement_id" :value="editingId || ''">
                        <input type="hidden" name="confirm_parent_change" :value="form.confirm_parent_change ? '1' : '0'">

                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'Face']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'Face']); ?>
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
                                name="face"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="form.face"
                                :disabled="form.full_depth && form.kind === 'asset'"
                                required
                            >
                                <option value="front">Front</option>
                                <?php if($rearEnabled): ?>
                                    <option value="rear">Rear</option>
                                <?php endif; ?>
                            </select>
                            <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-show="form.full_depth && form.kind === 'asset'" x-cloak>
                                Full-depth placements are stored on the front face and mirrored on the rear.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'Start RU']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'Start RU']); ?>
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
                                <input
                                    type="number"
                                    name="start_ru"
                                    min="1"
                                    :max="capacity"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                    x-model="form.start_ru"
                                    required
                                >
                            </div>
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'Height (RU)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'Height (RU)']); ?>
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
                                <input
                                    type="number"
                                    name="ru_height"
                                    min="1"
                                    :max="capacity"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                    x-model="form.ru_height"
                                >
                            </div>
                        </div>

                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'Kind']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'Kind']); ?>
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
                                name="kind"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="form.kind"
                                @change="if (form.kind !== 'asset') { form.full_depth = false; }"
                                required
                            >
                                <option value="asset">Child asset</option>
                                <option value="note">Text note</option>
                            </select>
                        </div>

                        <div x-show="form.kind === 'asset'" x-cloak class="space-y-2">
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'Child asset']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'Child asset']); ?>
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
                                name="child_asset_id"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="form.child_asset_id"
                                @change="onChildSelected(form.child_asset_id)"
                                :required="form.kind === 'asset'"
                                :disabled="form.kind !== 'asset'"
                            >
                                <option value="">Select…</option>
                                <template x-for="option in childOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                            <p class="text-xs text-brand-charcoal dark:text-brand-silver" x-show="selectedChild()?.parent_name">
                                Currently parented to <span x-text="selectedChild()?.parent_name"></span>
                            </p>

                            <?php if($rearEnabled): ?>
                                <input
                                    type="hidden"
                                    name="full_depth"
                                    :value="form.kind === 'asset' && form.full_depth ? '1' : '0'"
                                    :disabled="form.kind !== 'asset'"
                                >
                                <label class="flex items-start gap-2 text-sm pt-1">
                                    <input
                                        type="checkbox"
                                        class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand"
                                        x-model="form.full_depth"
                                        @change="if (form.full_depth) { form.face = 'front'; }"
                                    >
                                    <span>
                                        Full depth
                                        <span class="block text-xs text-brand-charcoal dark:text-brand-silver">
                                            Occupies the same RU span on the rear face as well.
                                        </span>
                                    </span>
                                </label>
                            <?php endif; ?>
                        </div>

                        <div x-show="form.kind === 'note'" x-cloak>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'Note']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'Note']); ?>
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
                            <textarea
                                name="note"
                                rows="3"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="form.note"
                                :required="form.kind === 'note'"
                                :disabled="form.kind !== 'note'"
                            ></textarea>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>
                                <span x-text="panel === 'edit' ? 'Save placement' : 'Place'"></span>
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','@click' => 'closePanel()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','@click' => 'closePanel()']); ?>Cancel <?php echo $__env->renderComponent(); ?>
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
                    </form>

                    <form
                        x-show="panel === 'edit' && editingPlacement()"
                        x-cloak
                        method="POST"
                        :action="editingPlacement()?.destroy_url"
                        onsubmit="return confirm('Remove this RU placement? The child asset will stay under this rack.')"
                        class="pt-2 border-t border-brand-silver dark:border-brand-charcoal"
                    >
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="text-sm text-red-600">Remove placement</button>
                    </form>
                </div>

                <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
                    <h4 class="text-sm font-semibold">Unplaced children</h4>
                    <?php $__empty_1 = true; $__currentLoopData = $unplacedChildren; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm border-b border-brand-silver/50 dark:border-brand-charcoal pb-2 last:border-0 last:pb-0">
                            <a href="<?php echo e(route('assets.show', $child)); ?>" class="text-brand"><?php echo e($child->name); ?></a>
                            <button
                                type="button"
                                class="text-sm text-brand"
                                @click="openAddForChild(<?php echo \Illuminate\Support\Js::from((string) $child->id)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(max(1, (int) ($child->ru_height ?? 1)))->toHtml() ?>)"
                            >
                                Place
                            </button>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">No unplaced child assets.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div
        x-show="confirmParent"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
        @keydown.escape.window="confirmParent = false"
    >
        <div class="fixed inset-0 bg-gray-500/75" @click="confirmParent = false"></div>
        <div class="relative w-full max-w-md bg-white dark:bg-brand-slate dark:text-white p-6 space-y-4 shadow-xl">
            <h2 class="text-lg font-medium">
                Change parent?
            </h2>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                This asset already belongs to
                <span class="font-medium" x-text="pendingParentName || 'another asset'"></span>.
                Placing it here will re-parent it under this rack.
            </p>
            <?php if($hasParentConfirmError): ?>
                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('confirm_parent_change')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('confirm_parent_change'))]); ?>
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
            <?php endif; ?>
            <div class="flex justify-end gap-2">
                <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','@click' => 'confirmParent = false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','@click' => 'confirmParent = false']); ?>
                    Cancel
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['type' => 'button','@click' => 'confirmAndSubmit()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','@click' => 'confirmAndSubmit()']); ?>
                    Confirm re-parent
                 <?php echo $__env->renderComponent(); ?>
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
        </div>
    </div>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/partials/rack.blade.php ENDPATH**/ ?>
<?php
    use App\Enums\RepairStatus;
    $isActive = $repair->isActive();
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
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl"><?php echo e($repair->summary); ?></h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    <a href="<?php echo e(route('assets.show', $repair->asset)); ?>" class="text-brand"><?php echo e($repair->asset?->name); ?></a>
                    <?php if($repair->asset?->fmi_ast): ?> · <?php echo e($repair->asset->fmi_ast); ?> <?php endif; ?>
                    · <?php echo e($repair->status?->label()); ?>

                </p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="<?php echo e(route('assets.show', [$repair->asset, 'tab' => 'maintenance'])); ?>" class="px-3 py-2 border">Maintenance</a>
                <a href="<?php echo e(route('repairs.index')); ?>" class="px-3 py-2 border">All repairs</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-3xl mx-auto px-4 space-y-6 text-sm">
        <?php if(session('status')): ?>
            <p class="text-brand"><?php echo e(session('status')); ?></p>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="text-red-600 space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-2">
            <h3 class="font-semibold">Fault</h3>
            <p><?php echo e($repair->maintenanceLog?->summary); ?></p>
            <?php if($repair->maintenanceLog?->body): ?>
                <p class="text-brand-charcoal dark:text-brand-silver whitespace-pre-wrap"><?php echo e($repair->maintenanceLog->body); ?></p>
            <?php endif; ?>
            <dl class="grid sm:grid-cols-2 gap-2 pt-2 text-xs">
                <div><dt class="text-brand-charcoal">Assignee</dt><dd><?php echo e($repair->assignee?->name ?: '—'); ?></dd></div>
                <div><dt class="text-brand-charcoal">Started</dt><dd><?php echo e($repair->started_at?->format('Y-m-d H:i') ?: '—'); ?></dd></div>
                <?php if($repair->procedure): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-brand-charcoal">Procedure</dt>
                        <dd>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('repairs:procedures')): ?>
                                <a href="<?php echo e(route('repair-procedures.show', $repair->procedure)); ?>" class="text-brand"><?php echo e($repair->procedure->title); ?></a>
                            <?php else: ?>
                                <?php echo e($repair->procedure->title); ?>

                            <?php endif; ?>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>
        </section>

        <?php if($repair->procedure?->safety_notes || $repair->procedure?->tools_required): ?>
            <section class="space-y-3">
                <?php if($repair->procedure->safety_notes): ?>
                    <div class="border border-amber-500/50 bg-amber-50 dark:bg-amber-950/20 p-4">
                        <h3 class="font-semibold mb-1">Safety</h3>
                        <p class="whitespace-pre-wrap"><?php echo e($repair->procedure->safety_notes); ?></p>
                    </div>
                <?php endif; ?>
                <?php if($repair->procedure->tools_required): ?>
                    <div class="border border-brand-silver dark:border-brand-charcoal p-4">
                        <h3 class="font-semibold mb-1">Tools</h3>
                        <p class="whitespace-pre-wrap"><?php echo e($repair->procedure->tools_required); ?></p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
            <h3 class="font-semibold">Checklist</h3>
            <?php $__empty_1 = true; $__currentLoopData = $repair->steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="border border-brand-silver/60 p-3 space-y-2">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-medium"><?php echo e($step->title); ?></p>
                            <?php if($step->is_required): ?><span class="text-xs text-brand">Required</span><?php endif; ?>
                            <?php if($step->body): ?><p class="mt-1 text-brand-charcoal dark:text-brand-silver whitespace-pre-wrap"><?php echo e($step->body); ?></p><?php endif; ?>
                            <?php if($step->completed_at): ?>
                                <p class="mt-1 text-xs text-brand-charcoal">Done <?php echo e($step->completed_at->format('Y-m-d H:i')); ?> by <?php echo e($step->completedBy?->name ?? '—'); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if($isActive): ?>
                            <form method="post" action="<?php echo e(route('repairs.steps.update', [$repair, $step])); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <input type="hidden" name="completed" value="<?php echo e($step->completed_at ? '0' : '1'); ?>">
                                <button type="submit" class="px-3 py-1 border text-xs <?php echo e($step->completed_at ? '' : 'bg-brand text-white border-brand'); ?>">
                                    <?php echo e($step->completed_at ? 'Undo' : 'Done'); ?>

                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-brand-charcoal dark:text-brand-silver">No procedure steps — use the blank checklist below and reference docs.</p>
            <?php endif; ?>

            <?php if($isActive): ?>
                <form method="post" action="<?php echo e(route('repairs.steps.store', $repair)); ?>" class="space-y-2 pt-2 border-t border-brand-silver dark:border-brand-charcoal">
                    <?php echo csrf_field(); ?>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'adhoc_title','value' => 'Add checklist step']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'adhoc_title','value' => 'Add checklist step']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'adhoc_title','name' => 'title','class' => 'mt-1 block w-full','required' => true,'maxlength' => '255']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'adhoc_title','name' => 'title','class' => 'mt-1 block w-full','required' => true,'maxlength' => '255']); ?>
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
                    <textarea name="body" rows="2" class="block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white" placeholder="Optional details"></textarea>
                    <label class="inline-flex items-center gap-2 text-xs">
                        <input type="hidden" name="is_required" value="0">
                        <input type="checkbox" name="is_required" value="1" checked>
                        Required
                    </label>
                    <div class="flex justify-end">
                        <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Add step <?php echo $__env->renderComponent(); ?>
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
                </form>
            <?php endif; ?>
        </section>

        <?php if($guidanceFiles->isNotEmpty()): ?>
            <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-2">
                <h3 class="font-semibold">Reference docs</h3>
                <ul class="space-y-1">
                    <?php $__currentLoopData = $guidanceFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex flex-wrap gap-2 items-center">
                            <span><?php echo e($file->title); ?></span>
                            <span class="text-xs text-brand-charcoal">(<?php echo e($file->category?->label()); ?>)</span>
                            <a href="<?php echo e(route('files.download', $file)); ?>" class="text-xs text-brand">Download</a>
                            <?php if($file->isPreviewableImage() || $file->isUrl()): ?>
                                <a href="<?php echo e(route('files.preview', $file)); ?>" class="text-xs text-brand" target="_blank" rel="noopener">Preview</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
            <h3 class="font-semibold">Parts</h3>

            <?php if($repair->procedure?->recommendedSpareParts?->isNotEmpty()): ?>
                <div>
                    <h4 class="text-xs font-medium text-brand-charcoal mb-1">Recommended</h4>
                    <ul class="space-y-1">
                        <?php $__currentLoopData = $repair->procedure->recommendedSpareParts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <a href="<?php echo e(route('spares.show', $part)); ?>" class="text-brand"><?php echo e($part->code); ?></a>
                                — <?php echo e($part->component); ?> × <?php echo e($part->pivot->quantity); ?>

                                (on hand: <?php echo e($part->quantity_on_hand); ?>)
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if($isActive): ?>
                <form method="post" action="<?php echo e(route('repairs.parts.store', $repair)); ?>" class="space-y-2">
                    <?php echo csrf_field(); ?>
                    <div class="grid sm:grid-cols-2 gap-2">
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'spare_part_id','value' => 'Spare']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'spare_part_id','value' => 'Spare']); ?>
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
                            <select id="spare_part_id" name="spare_part_id" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white" required>
                                <option value="">Select…</option>
                                <?php if($compatibleSpares->isNotEmpty()): ?>
                                    <optgroup label="Compatible">
                                        <?php $__currentLoopData = $compatibleSpares; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $spare): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($spare->id); ?>"><?php echo e($spare->code); ?> — <?php echo e($spare->component); ?> (<?php echo e($spare->quantity_on_hand); ?>)</option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </optgroup>
                                <?php endif; ?>
                                <optgroup label="All active">
                                    <?php $__currentLoopData = $spareOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $spare): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($spare->id); ?>"><?php echo e($spare->code); ?> — <?php echo e($spare->component); ?> (<?php echo e($spare->quantity_on_hand); ?>)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'part_type','value' => 'Type']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'part_type','value' => 'Type']); ?>
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
                            <select id="part_type" name="type" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white" required>
                                <?php $__currentLoopData = $movementTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'part_qty','value' => 'Quantity']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'part_qty','value' => 'Quantity']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'part_qty','name' => 'quantity','type' => 'number','min' => '1','value' => '1','class' => 'mt-1 block w-full','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'part_qty','name' => 'quantity','type' => 'number','min' => '1','value' => '1','class' => 'mt-1 block w-full','required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'part_reason','value' => 'Reason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'part_reason','value' => 'Reason']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'part_reason','name' => 'reason','class' => 'mt-1 block w-full','value' => old('reason', $repair->summary),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'part_reason','name' => 'reason','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('reason', $repair->summary)),'required' => true]); ?>
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
                    <div class="flex justify-end">
                        <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Issue spare <?php echo $__env->renderComponent(); ?>
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
                </form>
            <?php endif; ?>

            <div>
                <h4 class="text-xs font-medium text-brand-charcoal mb-1">Issued on this repair</h4>
                <ul class="space-y-1">
                    <?php $__empty_1 = true; $__currentLoopData = $repair->movements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li>
                            <?php echo e($movement->recorded_at?->format('Y-m-d H:i')); ?>

                            — <?php echo e($movement->type?->label()); ?>

                            <?php echo e($movement->sparePart?->code); ?>

                            (<?php echo e($movement->quantity_delta); ?>)
                            — <?php echo e($movement->user?->name); ?>

                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="text-brand-charcoal">No parts issued yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>

        <section class="border border-brand-silver dark:border-brand-charcoal p-4">
            <h3 class="font-semibold mb-2">Photos / notes files</h3>
            <?php if (isset($component)) { $__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.file-attachments','data' => ['linkableType' => 'repair','linkableId' => $repair->id,'rows' => $fileRows,'canMutate' => $isActive,'categories' => $fileCategories]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('file-attachments'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['linkable-type' => 'repair','linkable-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($repair->id),'rows' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fileRows),'can-mutate' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isActive),'categories' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fileCategories)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056)): ?>
<?php $attributes = $__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056; ?>
<?php unset($__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056)): ?>
<?php $component = $__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056; ?>
<?php unset($__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056); ?>
<?php endif; ?>
        </section>

        <?php if($repair->resolution_summary): ?>
            <section class="border border-brand-silver dark:border-brand-charcoal p-4">
                <h3 class="font-semibold mb-1"><?php echo e($repair->status === RepairStatus::Cancelled ? 'Cancellation' : 'Resolution'); ?></h3>
                <p class="whitespace-pre-wrap"><?php echo e($repair->resolution_summary); ?></p>
                <?php if($repair->completed_at): ?>
                    <p class="mt-1 text-xs text-brand-charcoal"><?php echo e($repair->completed_at->format('Y-m-d H:i')); ?> by <?php echo e($repair->completedBy?->name ?? '—'); ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if($isActive): ?>
            <section class="space-y-4" x-data="{ mode: 'complete' }">
                <div class="flex gap-2">
                    <button type="button" @click="mode = 'complete'" class="px-3 py-1 border text-xs" :class="mode === 'complete' && 'bg-brand text-white border-brand'">Complete</button>
                    <?php if (! \Illuminate\Support\Facades\Blade::check('role', 'external_repairer')): ?>
                        <button type="button" @click="mode = 'cancel'" class="px-3 py-1 border text-xs" :class="mode === 'cancel' && 'border-red-600 text-red-600'">Cancel repair</button>
                    <?php endif; ?>
                </div>

                <form x-show="mode === 'complete'" method="post" action="<?php echo e(route('repairs.complete', $repair)); ?>" class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
                    <?php echo csrf_field(); ?>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'resolution_summary','value' => 'Resolution summary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'resolution_summary','value' => 'Resolution summary']); ?>
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
                        <textarea id="resolution_summary" name="resolution_summary" rows="3" required class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white"><?php echo e(old('resolution_summary')); ?></textarea>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'asset_status','value' => 'Set asset status (optional)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'asset_status','value' => 'Set asset status (optional)']); ?>
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
                        <select id="asset_status" name="asset_status" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">
                            <option value="">No change</option>
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('asset_status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span></span>
                        <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Complete repair <?php echo $__env->renderComponent(); ?>
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
                </form>

                <?php if (! \Illuminate\Support\Facades\Blade::check('role', 'external_repairer')): ?>
                <form x-show="mode === 'cancel'" x-cloak method="post" action="<?php echo e(route('repairs.cancel', $repair)); ?>" class="border border-red-600/40 p-4 space-y-3">
                    <?php echo csrf_field(); ?>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'cancel_reason','value' => 'Cancellation reason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'cancel_reason','value' => 'Cancellation reason']); ?>
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
                        <textarea id="cancel_reason" name="reason" rows="3" required class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white"><?php echo e(old('reason')); ?></textarea>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-xs text-brand-charcoal">Stock is not reversed automatically.</span>
                        <button type="submit" class="px-4 py-2 border border-red-600 text-red-600">Cancel repair</button>
                    </div>
                </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/repairs/show.blade.php ENDPATH**/ ?>
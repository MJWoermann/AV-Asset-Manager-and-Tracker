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
            <div>
                <h2 class="font-semibold text-xl font-mono"><?php echo e($spare->code); ?></h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver"><?php echo e($spare->component); ?></p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                    <a href="<?php echo e(route('spares.edit', $spare)); ?>" class="px-3 py-2 border">Edit</a>
                    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                        <a href="<?php echo e(route('spares.label', $spare)); ?>" class="px-3 py-2 border">Print label</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="<?php echo e(route('spares.index')); ?>" class="px-3 py-2 border">Back</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 space-y-6">
        <?php if(session('status')): ?>
            <p class="text-sm text-brand"><?php echo e(session('status')); ?></p>
        <?php endif; ?>

        <?php if($spare->supersededBy): ?>
            <div class="border border-amber-500 bg-amber-50 dark:bg-amber-950/30 px-4 py-3 text-sm">
                Superseded — use
                <a href="<?php echo e(route('spares.show', $spare->supersededBy)); ?>" class="text-brand font-semibold">
                    <?php echo e($spare->supersededBy->code); ?> <?php echo e($spare->supersededBy->component); ?>

                </a>
                instead.
            </div>
        <?php endif; ?>

        <?php if($spare->supersedes->isNotEmpty()): ?>
            <div class="border border-brand-silver dark:border-brand-charcoal px-4 py-3 text-sm">
                <p class="font-medium">Supersedes</p>
                <ul class="mt-1 list-disc list-inside">
                    <?php $__currentLoopData = $spare->supersedes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $older): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><a href="<?php echo e(route('spares.show', $older)); ?>" class="text-brand"><?php echo e($older->code); ?></a> — <?php echo e($older->component); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <dl class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-4">
                <div><dt class="text-xs text-brand-charcoal dark:text-brand-silver">Kind</dt><dd><?php echo e($spare->kind?->label()); ?></dd></div>
                <div><dt class="text-xs">Brand / model</dt><dd><?php echo e($spare->manufacturer?->name); ?> <?php echo e($spare->manufacturerModel?->name); ?></dd></div>
                <div><dt class="text-xs">Product code</dt><dd><?php echo e($spare->product_code ?: '—'); ?></dd></div>
                <div><dt class="text-xs">Severity</dt><dd><?php echo e($spare->severity?->label()); ?></dd></div>
                <div><dt class="text-xs">Lifecycle</dt><dd><?php echo e($spare->lifecycle_status?->label()); ?></dd></div>
                <div><dt class="text-xs">Location</dt><dd><?php echo e($spare->location?->name); ?> <?php if($spare->shelf_location): ?><span class="text-brand-charcoal">/ <?php echo e($spare->shelf_location); ?></span><?php endif; ?></dd></div>
                <?php if($suggestedDeployed !== null): ?>
                    <div><dt class="text-xs">Deployed (sheet)</dt><dd><?php echo e($spare->quantity_deployed); ?> <span class="text-xs text-brand-charcoal">(assets on model: <?php echo e($suggestedDeployed); ?>)</span></dd></div>
                <?php endif; ?>
            </dl>
            <dl class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-4">
                <div><dt class="text-xs">Supplier</dt><dd><?php echo e($spare->supplier ?: '—'); ?></dd></div>
                <div><dt class="text-xs">Unit cost</dt><dd><?php echo e($spare->unit_cost !== null ? number_format((float) $spare->unit_cost, 2) : '—'); ?></dd></div>
                <div><dt class="text-xs">Lead time</dt><dd><?php echo e($spare->lead_time ?: '—'); ?></dd></div>
                <div><dt class="text-xs">Replacement time</dt><dd><?php echo e($spare->replacement_time ?: '—'); ?></dd></div>
                <?php if($spare->notes): ?><div><dt class="text-xs">Notes</dt><dd class="whitespace-pre-wrap"><?php echo e($spare->notes); ?></dd></div><?php endif; ?>
            </dl>
        </div>

        <?php echo $__env->make('spares.partials.stock-panel', ['spare' => $spare, 'chartPayload' => $chartPayload, 'movementAction' => $movementAction, 'assetOptions' => $assetOptions], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm" x-data="{ open: <?php echo e($errors->has('asset_id') || old('summary') ? 'true' : 'false'); ?> }">
            <div class="flex justify-between items-center gap-2">
                <h3 class="font-semibold">Start repair</h3>
                <button type="button" @click="open = !open" class="px-3 py-1 border text-xs" x-text="open ? 'Hide' : 'Start'"></button>
            </div>
            <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Creates a Fault on the asset and opens a guided repair with this spare available to issue.</p>
            <form
                x-show="open"
                x-cloak
                method="post"
                action="<?php echo e(route('spares.repairs.store', $spare)); ?>"
                class="mt-3 space-y-3"
            >
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-xs">Asset</label>
                    <select name="asset_id" required class="mt-1 w-full border px-2 py-1 dark:bg-brand-slate">
                        <option value="">Select asset…</option>
                        <?php $__currentLoopData = ($repairAssetOptions ?? collect())->concat($assetOptions)->unique('id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($asset->id); ?>" <?php if((string) old('asset_id') === (string) $asset->id): echo 'selected'; endif; ?>><?php echo e($asset->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['asset_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-xs">Summary</label>
                    <input name="summary" value="<?php echo e(old('summary', 'Repair: '.$spare->displayLabel())); ?>" class="mt-1 w-full border px-2 py-1" maxlength="255">
                </div>
                <?php if(($suggestedProcedures ?? collect())->isNotEmpty()): ?>
                    <div>
                        <label class="block text-xs">Procedure (optional)</label>
                        <select name="repair_procedure_id" class="mt-1 w-full border px-2 py-1 dark:bg-brand-slate">
                            <option value="">Auto-match / blank checklist</option>
                            <?php $__currentLoopData = $suggestedProcedures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $procedure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($procedure->id); ?>" <?php if((string) old('repair_procedure_id') === (string) $procedure->id): echo 'selected'; endif; ?>><?php echo e($procedure->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs">Notes</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full border px-2 py-1"><?php echo e(old('notes')); ?></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-3 py-2 bg-brand text-white">Start repair</button>
                </div>
            </form>
        </div>

        <div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm">
            <h3 class="font-semibold mb-2">Movement history</h3>
            <table class="min-w-full text-left">
                <thead class="text-xs text-brand-charcoal dark:text-brand-silver">
                    <tr><th class="py-1 pr-2">When</th><th class="py-1 pr-2">Type</th><th class="py-1 pr-2">Qty</th><th class="py-1 pr-2">Asset</th><th class="py-1 pr-2">Reason</th><th class="py-1">Who</th></tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $movementHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-t border-brand-silver/50">
                            <td class="py-1 pr-2"><?php echo e($movement->recorded_at?->format('Y-m-d H:i')); ?></td>
                            <td class="py-1 pr-2"><?php echo e($movement->type?->label()); ?></td>
                            <td class="py-1 pr-2"><?php echo e($movement->quantity_before); ?>→<?php echo e($movement->quantity_after); ?></td>
                            <td class="py-1 pr-2">
                                <?php if($movement->againstAsset): ?>
                                    <a href="<?php echo e(route('assets.show', $movement->againstAsset)); ?>" class="text-brand"><?php echo e($movement->againstAsset->name); ?></a>
                                <?php else: ?> — <?php endif; ?>
                            </td>
                            <td class="py-1 pr-2"><?php echo e($movement->reason ?: '—'); ?></td>
                            <td class="py-1"><?php echo e($movement->user?->name ?: '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="py-2 text-brand-charcoal">No movements yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <h3 class="font-semibold text-sm mb-2">Procedure documents</h3>
            <?php if (isset($component)) { $__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.file-attachments','data' => ['linkableType' => 'spare_part','linkableId' => $spare->id,'rows' => $fileRows,'canMutate' => auth()->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('file-attachments'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['linkable-type' => 'spare_part','linkable-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($spare->id),'rows' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fileRows),'can-mutate' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(auth()->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false)]); ?>
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
        </div>

        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
            <div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm">
                <h3 class="font-semibold">Mark superseded</h3>
                <form method="post" action="<?php echo e(route('spares.supersede', $spare)); ?>" class="mt-2 flex flex-wrap gap-2 items-end">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="block text-xs">Successor spare</label>
                        <select name="superseded_by_id" required class="border px-2 py-1 min-w-[12rem]">
                            <option value="">Select…</option>
                            <?php $__currentLoopData = \App\Models\SparePart::query()->where('id', '!=', $spare->id)->activeLifecycle()->orderBy('code')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($option->id); ?>"><?php echo e($option->code); ?> — <?php echo e($option->component); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <button type="submit" class="px-3 py-2 bg-black text-white">Record supersession</button>
                </form>
            </div>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/spares/show.blade.php ENDPATH**/ ?>
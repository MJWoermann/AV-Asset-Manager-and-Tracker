<?php
    $chart = $chartPayload ?? [];
    $initialAction = old('type', $movementAction ?? 'count');
?>

<div
    class="space-y-6"
    x-data="sparePartPanel(<?php echo \Illuminate\Support\Js::from([
        'chart' => $chart,
        'initialAction' => $initialAction,
    ])->toHtml() ?>)"
>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs text-brand-charcoal dark:text-brand-silver">On hand</p>
            <p class="text-2xl font-semibold"><?php echo e($chart['current_quantity'] ?? $spare->quantity_on_hand); ?></p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs">Min</p>
            <p class="text-2xl font-semibold"><?php echo e($chart['min_holding'] ?? '—'); ?></p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs">Max</p>
            <p class="text-2xl font-semibold"><?php echo e($chart['max_holding'] ?? '—'); ?></p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs">Reorder ETA</p>
            <p class="text-sm font-semibold mt-1"><?php echo e($chart['reorder_eta_label'] ?? '—'); ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 border border-brand-silver dark:border-brand-charcoal p-3 bg-white dark:bg-brand-slate">
            <canvas x-ref="chart" height="220"></canvas>
            <p class="text-xs text-brand-charcoal dark:text-brand-silver mt-2">Click a point for movement details.</p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3 text-sm space-y-2 min-h-[12rem]">
            <h3 class="font-semibold">Movement detail</h3>
            <template x-if="!selected"><p class="text-brand-charcoal dark:text-brand-silver">Select a chart point.</p></template>
            <template x-if="selected">
                <div class="space-y-1">
                    <p><span class="text-brand-charcoal dark:text-brand-silver">When:</span> <span x-text="selected.recorded_at"></span></p>
                    <p><span class="text-brand-charcoal dark:text-brand-silver">Type:</span> <span x-text="selected.type_label"></span></p>
                    <p><span class="text-brand-charcoal dark:text-brand-silver">Who:</span> <span x-text="selected.user_name || '—'"></span></p>
                    <p x-show="selected.against_asset_name"><span class="text-brand-charcoal dark:text-brand-silver">Asset:</span> <span x-text="selected.against_asset_name"></span></p>
                    <p x-show="selected.reason"><span class="text-brand-charcoal dark:text-brand-silver">Reason:</span> <span x-text="selected.reason"></span></p>
                    <p>
                        <span class="text-brand-charcoal dark:text-brand-silver">Qty:</span>
                        <span x-text="selected.quantity_before + ' → ' + selected.quantity_after"></span>
                    </p>
                </div>
            </template>
        </div>
    </div>

    <form method="post" action="<?php echo e(route('spares.movements.store', $spare)); ?>" enctype="multipart/form-data" class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3 text-sm">
        <?php echo csrf_field(); ?>
        <h3 class="font-semibold">Record movement</h3>
        <?php if($spare->isNonActiveLifecycle()): ?>
            <p class="text-amber-700 text-xs">This line is <?php echo e($spare->lifecycle_status?->label()); ?>. Use/replace of remaining stock is allowed; prefer restocking the successor.</p>
        <?php endif; ?>
        <div class="flex flex-wrap gap-2">
            <?php $__currentLoopData = \App\Enums\SparePartMovementType::options(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="inline-flex items-center gap-1 px-2 py-1 border cursor-pointer" :class="action === '<?php echo e($value); ?>' ? 'border-brand bg-brand/10' : 'border-brand-silver'">
                    <input type="radio" name="type" value="<?php echo e($value); ?>" x-model="action" class="sr-only">
                    <?php echo e($label); ?>

                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div x-show="['count','restock','use','replace'].includes(action)">
                <label class="block text-xs">Quantity</label>
                <input type="number" min="1" name="quantity" value="<?php echo e(old('quantity')); ?>" class="w-full border px-2 py-1">
            </div>
            <div x-show="action === 'adjustment'">
                <label class="block text-xs">Quantity delta</label>
                <input type="number" name="quantity_delta" value="<?php echo e(old('quantity_delta')); ?>" class="w-full border px-2 py-1">
            </div>
            <div x-show="['use','replace'].includes(action)">
                <label class="block text-xs">Against asset</label>
                <select name="against_asset_id" class="w-full border px-2 py-1">
                    <option value="">Select asset…</option>
                    <?php $__currentLoopData = $assetOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($asset->id); ?>" <?php if((string) old('against_asset_id') === (string) $asset->id): echo 'selected'; endif; ?>><?php echo e($asset->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div x-show="['use','replace'].includes(action)" class="sm:col-span-2">
                <label class="block text-xs">Reason</label>
                <input name="reason" value="<?php echo e(old('reason')); ?>" class="w-full border px-2 py-1">
            </div>
            <div>
                <label class="block text-xs">Ticket ref</label>
                <input name="ticket_ref" value="<?php echo e(old('ticket_ref')); ?>" class="w-full border px-2 py-1">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs">Notes</label>
                <textarea name="notes" rows="2" class="w-full border px-2 py-1"><?php echo e(old('notes')); ?></textarea>
            </div>
            <div x-show="action === 'restock'">
                <label class="block text-xs">Restock PDFs</label>
                <input type="file" name="pdfs[]" accept="application/pdf" multiple>
            </div>
        </div>
        <?php if($errors->any()): ?>
            <ul class="text-red-600 text-xs space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>
        <button type="submit" class="px-4 py-2 bg-brand text-white">Save movement</button>
    </form>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/spares/partials/stock-panel.blade.php ENDPATH**/ ?>
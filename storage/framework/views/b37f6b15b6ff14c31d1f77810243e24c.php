<div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm space-y-3">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h3 class="font-semibold">Spares register</h3>
            <?php if($listedSpare ?? null): ?>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">This asset is listed in the spares register.</p>
            <?php else: ?>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">List this asset as a replacement unit in the spares register.</p>
            <?php endif; ?>
        </div>
        <?php if($listedSpare ?? null): ?>
            <a href="<?php echo e(route('spares.show', $listedSpare)); ?>" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm self-start sm:self-auto">
                Open <?php echo e($listedSpare->code); ?>

            </a>
        <?php else: ?>
            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                <form method="POST" action="<?php echo e(route('assets.spares.store', $asset)); ?>" class="self-start sm:self-auto">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="px-3 py-2 bg-brand text-white text-sm">List in spares</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm space-y-3">
    <h3 class="font-semibold">Spares used / replacements</h3>
    <p class="text-xs text-brand-charcoal dark:text-brand-silver">Parts consumed on this asset and replacement units logged against it.</p>

    <table class="min-w-full text-left">
        <thead class="text-xs text-brand-charcoal dark:text-brand-silver">
            <tr>
                <th class="py-1 pr-2">When</th>
                <th class="py-1 pr-2">Spare</th>
                <th class="py-1 pr-2">Type</th>
                <th class="py-1 pr-2">Qty Δ</th>
                <th class="py-1 pr-2">Reason</th>
                <th class="py-1">Who</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $spareMovements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="border-t border-brand-silver/50">
                    <td class="py-1 pr-2"><?php echo e($movement->recorded_at?->format('Y-m-d H:i')); ?></td>
                    <td class="py-1 pr-2">
                        <?php if($movement->sparePart): ?>
                            <a href="<?php echo e(route('spares.show', $movement->sparePart)); ?>" class="text-brand font-mono"><?php echo e($movement->sparePart->code); ?></a>
                            <span class="block"><?php echo e($movement->sparePart->component); ?></span>
                        <?php else: ?> — <?php endif; ?>
                    </td>
                    <td class="py-1 pr-2"><?php echo e($movement->type?->label()); ?></td>
                    <td class="py-1 pr-2"><?php echo e($movement->quantity_delta); ?></td>
                    <td class="py-1 pr-2"><?php echo e($movement->reason ?: '—'); ?></td>
                    <td class="py-1"><?php echo e($movement->user?->name ?: '—'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="py-2 text-brand-charcoal">No spare movements logged for this asset.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/partials/spares.blade.php ENDPATH**/ ?>
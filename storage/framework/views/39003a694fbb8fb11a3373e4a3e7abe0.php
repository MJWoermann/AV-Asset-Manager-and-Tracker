<div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3">
    <h3 class="font-semibold">Team log — <?php echo e(\App\Support\RiggingCalculators::label($calculator)); ?></h3>
    <?php if($logs->isEmpty()): ?>
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">No saved calculations yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">When</th>
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">By</th>
                        <th class="px-3 py-2">Rig Event</th>
                        <th class="px-3 py-2">Summary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal align-top">
                            <td class="px-3 py-2 whitespace-nowrap"><?php echo e($log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i')); ?></td>
                            <td class="px-3 py-2"><?php echo e($log->title ?: '—'); ?></td>
                            <td class="px-3 py-2"><?php echo e($log->creator?->name ?? '—'); ?></td>
                            <td class="px-3 py-2">
                                <?php if($log->rigEvent): ?>
                                    <a href="<?php echo e(route('rigging.events.show', $log->rigEvent)); ?>" class="text-brand"><?php echo e($log->rigEvent->name); ?></a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-xs text-brand-charcoal dark:text-brand-silver">
                                <details>
                                    <summary class="cursor-pointer text-brand">Results</summary>
                                    <pre class="mt-1 whitespace-pre-wrap"><?php echo e(json_encode($log->results, JSON_PRETTY_PRINT)); ?></pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/rigging/calculators/partials/team-log.blade.php ENDPATH**/ ?>
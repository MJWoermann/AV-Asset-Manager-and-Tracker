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
     <?php $__env->slot('header', null, []); ?> <h2 class="font-semibold text-xl">Resolve import duplicates</h2> <?php $__env->endSlot(); ?>
    <div class="max-w-5xl mx-auto px-4" x-data="{ bulk: 'update' }">
        <form method="POST" action="<?php echo e(route('import.process')); ?>" class="space-y-4">
            <?php echo csrf_field(); ?>

            <div class="bg-white dark:bg-brand-slate border p-4 space-y-2 text-sm">
                <p>
                    Prepared <span class="font-semibold"><?php echo e($totalCount); ?></span> rows:
                    <span class="font-semibold"><?php echo e($newCount); ?></span> new,
                    <span class="font-semibold"><?php echo e(count($duplicates)); ?></span> matched existing assets
                    (Serial Number, FMI AST#, MAC Address, Rig Tag #, or TP Barcode).
                </p>
                <p class="text-gray-600 dark:text-brand-silver">
                    <span class="font-medium text-brand-black dark:text-white">Update</span> merges mapped fields into the existing asset.
                    <span class="font-medium text-brand-black dark:text-white">Replace</span> clears core fields then applies the import.
                    <span class="font-medium text-brand-black dark:text-white">Skip</span> leaves the existing asset unchanged.
                    <span class="font-medium text-brand-black dark:text-white">Create new (false match)</span> creates a new asset and leaves the matched asset unchanged — use when the compared fields are not unique.
                </p>
            </div>

            <?php if(count($duplicates)): ?>
                <div class="bg-white dark:bg-brand-slate border p-4 space-y-3">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'bulk_action','value' => 'Bulk action for all duplicates']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'bulk_action','value' => 'Bulk action for all duplicates']); ?>
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
                                id="bulk_action"
                                name="bulk_action"
                                x-model="bulk"
                                class="mt-1 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            >
                                <option value="update">Update all</option>
                                <option value="replace">Replace all</option>
                                <option value="skip">Skip all</option>
                                <option value="create">Create all as new (false match)</option>
                            </select>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-2 border text-xs uppercase tracking-widest"
                            @click="document.querySelectorAll('[data-dup-action]').forEach(el => el.value = bulk)"
                        >
                            Apply bulk to each row
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-2 pr-3">Incoming</th>
                                    <th class="py-2 pr-3">Existing</th>
                                    <th class="py-2 pr-3">Matched on</th>
                                    <th class="py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $duplicates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="border-b align-top">
                                        <td class="py-3 pr-3">
                                            <div class="font-medium"><?php echo e($dup['payload']['name'] ?? '—'); ?></div>
                                            <div class="text-xs text-gray-600 dark:text-brand-silver space-y-0.5 mt-1">
                                                <?php $__currentLoopData = ['serial_number','fmi_ast','mac_address','rig_tag','tp_barcode']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if(!empty($dup['payload'][$key])): ?>
                                                        <div><?php echo e($fieldLabels[$key] ?? $key); ?>: <?php echo e($dup['payload'][$key]); ?></div>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <div class="font-medium"><?php echo e($dup['existing_summary']['name'] ?? '—'); ?></div>
                                            <div class="text-xs text-gray-600 dark:text-brand-silver space-y-0.5 mt-1">
                                                <div>ID: <?php echo e($dup['existing_id']); ?></div>
                                                <?php $__currentLoopData = ['serial_number','fmi_ast','mac_address','rig_tag','tp_barcode']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if(!empty($dup['existing_summary'][$key])): ?>
                                                        <div><?php echo e($fieldLabels[$key] ?? $key); ?>: <?php echo e($dup['existing_summary'][$key]); ?></div>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <?php echo e(collect($dup['matched_on'])->map(fn ($k) => $fieldLabels[$k] ?? $k)->implode(', ')); ?>

                                        </td>
                                        <td class="py-3">
                                            <select
                                                name="actions[<?php echo e($dup['index']); ?>]"
                                                data-dup-action
                                                class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                            >
                                                <option value="update" selected>Update</option>
                                                <option value="replace">Replace</option>
                                                <option value="skip">Skip</option>
                                                <option value="create">Create new (false match)</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white dark:bg-brand-slate border p-4 text-sm">
                    No duplicates found. All prepared rows will be created as new assets.
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-brand-slate border p-4 space-y-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="dry_run" value="1" class="rounded text-brand" checked>
                    Dry run (no writes)
                </label>
                <div>
                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Process import <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                    <a href="<?php echo e(route('import.create')); ?>" class="ml-3 text-sm underline">Start over</a>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/import/duplicates.blade.php ENDPATH**/ ?>
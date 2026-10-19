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
    <div
        class="max-w-5xl mx-auto px-4"
        x-data="{
            bulk: 'update',
            listDestination: '<?php echo e(old('list_destination', 'none')); ?>',
            includeUpdated: <?php echo e(old('include_updated_in_list', false) ? 'true' : 'false'); ?>,
        }"
    >
        <form method="POST" action="<?php echo e(route('import.process')); ?>" class="space-y-4">
            <?php echo csrf_field(); ?>

            <div class="bg-white dark:bg-brand-slate border p-4 space-y-2 text-sm">
                <p>
                    Prepared <span class="font-semibold"><?php echo e($totalCount); ?></span> rows:
                    <span class="font-semibold"><?php echo e($newCount); ?></span> new,
                    <span class="font-semibold"><?php echo e($duplicateCount ?? count($duplicates)); ?></span> matched existing assets
                    (Serial Number, FMI AST#, MAC Address, Rig Tag #, or TP Barcode).
                </p>
                <?php if(($duplicatesHiddenCount ?? 0) > 0): ?>
                    <p class="text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 px-3 py-2 rounded">
                        Showing the first <?php echo e(count($duplicates)); ?> of <?php echo e($duplicateCount); ?> matches for review.
                        The bulk action below still applies to <span class="font-semibold">all</span> matched rows
                        (including the <?php echo e($duplicatesHiddenCount); ?> not listed).
                    </p>
                <?php endif; ?>
                <p class="text-gray-600 dark:text-brand-silver">
                    <span class="font-medium text-brand-black dark:text-white">Update</span> merges mapped fields into the existing asset.
                    <span class="font-medium text-brand-black dark:text-white">Replace</span> clears core fields then applies the import.
                    <span class="font-medium text-brand-black dark:text-white">Skip</span> leaves the existing asset unchanged.
                    <span class="font-medium text-brand-black dark:text-white">Create new (false match)</span> creates a new asset and leaves the matched asset unchanged — use when the compared fields are not unique.
                </p>
            </div>

            <?php if(($duplicateCount ?? count($duplicates)) > 0): ?>
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
                                                <?php $__currentLoopData = ['serial_number','fmi_ast','mac_address','rig_tag','tp_barcode','ip_address']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                                                <?php $__currentLoopData = ['serial_number','fmi_ast','mac_address','rig_tag','tp_barcode','ip_address']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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

            <div class="bg-white dark:bg-brand-slate border p-4 space-y-3 text-sm">
                <h3 class="font-semibold text-base">Add imported assets to a list</h3>
                <p class="text-gray-600 dark:text-brand-silver">
                    Optionally add assets from this import to an inventory or event list. New assets are always included when a list is selected.
                </p>

                <div class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="list_destination" value="none" x-model="listDestination" class="text-brand">
                        <span>Do not add to a list</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="list_destination" value="existing" x-model="listDestination" class="text-brand">
                        <span>Add to an existing list</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="list_destination" value="new" x-model="listDestination" class="text-brand">
                        <span>Create a new list from this import</span>
                    </label>
                </div>

                <div x-show="listDestination === 'existing'" x-cloak class="space-y-2 pt-1">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'asset_list_id','value' => 'Existing list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'asset_list_id','value' => 'Existing list']); ?>
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
                        id="asset_list_id"
                        name="asset_list_id"
                        class="mt-1 w-full max-w-md rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                        <option value="">Select a list…</option>
                        <?php $__currentLoopData = $lists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($listOption->id); ?>" <?php if((string) old('asset_list_id') === (string) $listOption->id): echo 'selected'; endif; ?>>
                                <?php echo e($listOption->name); ?> (<?php echo e($listOption->type); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['asset_list_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-sm text-red-600"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div x-show="listDestination === 'new'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 max-w-2xl">
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'new_list_name','value' => 'New list name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'new_list_name','value' => 'New list name']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'new_list_name','name' => 'new_list_name','class' => 'mt-1 block w-full','value' => old('new_list_name'),'placeholder' => 'e.g. Warehouse stocktake import']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'new_list_name','name' => 'new_list_name','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('new_list_name')),'placeholder' => 'e.g. Warehouse stocktake import']); ?>
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
                        <?php $__errorArgs = ['new_list_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'new_list_type','value' => 'List type']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'new_list_type','value' => 'List type']); ?>
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
                            id="new_list_type"
                            name="new_list_type"
                            class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        >
                            <option value="inventory" <?php if(old('new_list_type', 'inventory') === 'inventory'): echo 'selected'; endif; ?>>Inventory</option>
                            <option value="event" <?php if(old('new_list_type') === 'event'): echo 'selected'; endif; ?>>Event</option>
                        </select>
                        <?php $__errorArgs = ['new_list_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <label
                    class="inline-flex items-start gap-2 pt-1"
                    x-show="listDestination !== 'none'"
                    x-cloak
                >
                    <input
                        type="checkbox"
                        name="include_updated_in_list"
                        value="1"
                        class="mt-0.5 rounded text-brand"
                        x-model="includeUpdated"
                    >
                    <span>
                        Also include existing assets that are updated or replaced
                        <span class="block text-gray-600 dark:text-brand-silver">Skipped matches are never added.</span>
                    </span>
                </label>
                <?php $__errorArgs = ['list_destination'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-sm text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="bg-white dark:bg-brand-slate border p-4 space-y-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="dry_run" value="1" class="rounded text-brand" checked>
                    Dry run (no writes)
                </label>
                <div class="flex justify-end gap-3 items-center">
                    <a href="<?php echo e(route('import.create')); ?>" class="text-sm underline">Start over</a>
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
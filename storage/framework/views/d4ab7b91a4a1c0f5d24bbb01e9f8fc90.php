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
        <h2 class="font-semibold text-xl">Scan to event</h2>
     <?php $__env->endSlot(); ?>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <?php if (! ($session)): ?>
            <form method="POST" action="<?php echo e(route('scan.start')); ?>" class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                <?php echo csrf_field(); ?>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Choose the event list to add scanned items to, and optionally an inventory list to check against.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'event_list_id','value' => 'Event list (add items to)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'event_list_id','value' => 'Event list (add items to)']); ?>
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
                        <select id="event_list_id" name="event_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                            <option value="" disabled selected>Select event list…</option>
                            <?php $__currentLoopData = $eventLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($list->id); ?>"><?php echo e($list->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'inventory_list_id','value' => 'Inventory list (search against)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'inventory_list_id','value' => 'Inventory list (search against)']); ?>
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
                        <select id="inventory_list_id" name="inventory_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="">None</option>
                            <?php $__currentLoopData = $inventoryLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($list->id); ?>"><?php echo e($list->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php if (isset($component)) { $__componentOriginal98b0ed2f3ba771387e29466021246ec2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal98b0ed2f3ba771387e29466021246ec2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.location-field','data' => ['locations' => $locations]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.location-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal98b0ed2f3ba771387e29466021246ec2)): ?>
<?php $attributes = $__attributesOriginal98b0ed2f3ba771387e29466021246ec2; ?>
<?php unset($__attributesOriginal98b0ed2f3ba771387e29466021246ec2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal98b0ed2f3ba771387e29466021246ec2)): ?>
<?php $component = $__componentOriginal98b0ed2f3ba771387e29466021246ec2; ?>
<?php unset($__componentOriginal98b0ed2f3ba771387e29466021246ec2); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal62cf2f378266570595baa55e0afc4388 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal62cf2f378266570595baa55e0afc4388 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.asset-status-field','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.asset-status-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal62cf2f378266570595baa55e0afc4388)): ?>
<?php $attributes = $__attributesOriginal62cf2f378266570595baa55e0afc4388; ?>
<?php unset($__attributesOriginal62cf2f378266570595baa55e0afc4388); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal62cf2f378266570595baa55e0afc4388)): ?>
<?php $component = $__componentOriginal62cf2f378266570595baa55e0afc4388; ?>
<?php unset($__componentOriginal62cf2f378266570595baa55e0afc4388); ?>
<?php endif; ?>
                </div>
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Start scanning <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
            </form>
        <?php else: ?>
            <form method="POST" action="<?php echo e(route('scan.lists', $session)); ?>" class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'event_list_id','value' => 'Event list (add items to)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'event_list_id','value' => 'Event list (add items to)']); ?>
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
                        <select id="event_list_id" name="event_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                            <?php $__currentLoopData = $eventLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($list->id); ?>" <?php if($session->event_list_id == $list->id): echo 'selected'; endif; ?>><?php echo e($list->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'inventory_list_id','value' => 'Inventory list (search against)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'inventory_list_id','value' => 'Inventory list (search against)']); ?>
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
                        <select id="inventory_list_id" name="inventory_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="">None</option>
                            <?php $__currentLoopData = $inventoryLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($list->id); ?>" <?php if($session->inventory_list_id == $list->id): echo 'selected'; endif; ?>><?php echo e($list->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php if (isset($component)) { $__componentOriginal98b0ed2f3ba771387e29466021246ec2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal98b0ed2f3ba771387e29466021246ec2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.location-field','data' => ['locations' => $locations,'selected' => $session->location_id]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.location-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['locations' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($locations),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($session->location_id)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal98b0ed2f3ba771387e29466021246ec2)): ?>
<?php $attributes = $__attributesOriginal98b0ed2f3ba771387e29466021246ec2; ?>
<?php unset($__attributesOriginal98b0ed2f3ba771387e29466021246ec2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal98b0ed2f3ba771387e29466021246ec2)): ?>
<?php $component = $__componentOriginal98b0ed2f3ba771387e29466021246ec2; ?>
<?php unset($__componentOriginal98b0ed2f3ba771387e29466021246ec2); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal62cf2f378266570595baa55e0afc4388 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal62cf2f378266570595baa55e0afc4388 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan.asset-status-field','data' => ['selected' => $session->asset_status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan.asset-status-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($session->asset_status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal62cf2f378266570595baa55e0afc4388)): ?>
<?php $attributes = $__attributesOriginal62cf2f378266570595baa55e0afc4388; ?>
<?php unset($__attributesOriginal62cf2f378266570595baa55e0afc4388); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal62cf2f378266570595baa55e0afc4388)): ?>
<?php $component = $__componentOriginal62cf2f378266570595baa55e0afc4388; ?>
<?php unset($__componentOriginal62cf2f378266570595baa55e0afc4388); ?>
<?php endif; ?>
                </div>
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Update lists <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
            </form>

            <?php
                $initialEventItems = $eventListItems->map(fn ($item) => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'is_child_expand' => (bool) $item->is_child_expand,
                    'asset' => [
                        'id' => $item->asset?->id,
                        'name' => $item->asset?->name,
                        'tp_barcode' => $item->asset?->tp_barcode,
                        'fmi_ast' => $item->asset?->fmi_ast,
                        'rig_tag' => $item->asset?->rig_tag,
                        'serial_number' => $item->asset?->serial_number,
                        'item_type' => $item->asset?->itemType?->name,
                    ],
                ])->values();

                $initialUnknownScans = $unknownScans->map(fn ($scan) => [
                    'id' => $scan->id,
                    'scanned_code' => $scan->scanned_code,
                    'quantity' => $scan->quantity,
                    'note' => $scan->note,
                    'location' => $scan->location?->name,
                    'resolved' => $scan->isResolved(),
                    'create_url' => route('assets.create', [
                        'tp_barcode' => $scan->scanned_code,
                        'location_id' => $scan->location_id,
                        'unknown_scan_id' => $scan->id,
                        'status' => $scan->asset_status?->value,
                    ]),
                    'time' => $scan->created_at?->timezone(config('app.timezone'))->format('g:i:s A'),
                ])->values();
            ?>

            <div
                x-data="barcodeScanner(<?php echo \Illuminate\Support\Js::from(route('scan.scan', $session))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($session->event_list_id)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($initialEventItems)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(route('unknown-scans.update', ['unknownScan' => '__ID__']))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($initialUnknownScans)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($clearScanRecent ?? false)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(auth()->user()?->wantsScanNoise() ?? false)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(auth()->user()?->wantsScanVibrate() ?? false)->toHtml() ?>)"
                class="space-y-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-text="inputModeStatus" data-scan-input-mode-status></p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="useCamera"
                            x-show="!scanning"
                            class="px-4 py-2 bg-brand text-white text-sm"
                        >Use camera</button>
                        <button
                            type="button"
                            @click="stopCamera"
                            x-show="scanning"
                            x-cloak
                            class="px-4 py-2 bg-black text-white text-sm"
                        >Stop camera</button>
                        <button
                            type="button"
                            @click="useHardware"
                            x-show="showHardwareSwitch"
                            x-cloak
                            class="px-4 py-2 border border-brand-charcoal dark:border-brand-silver text-sm"
                        >Use hardware scanner</button>
                    </div>
                </div>
                <div
                    id="qr-reader"
                    class="scan-qr-reader w-full max-w-xl mx-auto max-h-[28vh] sm:max-h-[min(50vh,28rem)] overflow-hidden"
                    x-show="scanning"
                    x-cloak
                ></div>

                <form @submit.prevent="submitCode" class="flex flex-col sm:flex-row gap-2">
                    <input
                        type="text"
                        x-ref="codeInput"
                        x-model="code"
                        @keydown.enter.prevent="submitCode"
                        placeholder="Scan or type barcode / ID"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        class="flex-1 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                    <input type="number" min="1" x-model="quantity" class="w-24 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" aria-label="Quantity">
                    <button type="submit" class="px-4 py-2 bg-black text-white dark:bg-brand text-sm" :disabled="busy && queue.length === 0">Add</button>
                </form>

                <p class="text-sm text-brand" x-text="message" x-show="message" x-cloak></p>
                <p class="text-sm text-red-600" x-text="error" x-show="error" x-cloak></p>

                <div
                    x-show="warning"
                    x-cloak
                    class="border border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100 text-sm px-3 py-2"
                    role="alert"
                >
                    <p class="font-medium">Not on inventory list</p>
                    <p x-text="warning"></p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold">Added this session</h3>
                        <div class="flex items-center gap-2">
                            <a href="<?php echo e(route('reports.unknown-scans')); ?>" class="text-xs text-brand underline">Unknown scan report</a>
                            <button
                                type="button"
                                @click="resetRecent"
                                x-show="recent.length > 0"
                                x-cloak
                                class="px-2 py-1 border border-brand-charcoal dark:border-brand-silver text-xs"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="recent.length === 0" x-cloak>No items added yet.</p>
                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal border border-brand-silver dark:border-brand-charcoal max-h-40 sm:max-h-64 overflow-y-auto" x-show="recent.length > 0">
                        <template x-for="entry in recent" :key="entry.key">
                            <li
                                class="px-3 py-2 text-sm flex flex-col gap-1"
                                :class="{
                                    'bg-amber-50 dark:bg-amber-950/30': entry.inventory_status === 'not_on_inventory',
                                    'bg-red-50 dark:bg-red-950/30': entry.unknown,
                                }"
                            >
                                <div class="flex justify-between gap-2">
                                    <span class="font-medium" x-text="entry.unknown ? ('Not in system: ' + (entry.unknown_scan?.scanned_code || '')) : (entry.asset?.name || 'Unknown asset')"></span>
                                    <span class="text-xs text-brand-charcoal dark:text-brand-silver whitespace-nowrap" x-text="entry.time"></span>
                                </div>
                                <div class="text-xs text-brand-charcoal dark:text-brand-silver flex flex-wrap items-center gap-x-3 gap-y-0.5">
                                    <span x-show="!entry.unknown && entry.asset?.tp_barcode" x-text="'TP: ' + entry.asset.tp_barcode"></span>
                                    <span x-show="!entry.unknown && entry.asset?.fmi_ast" x-text="'FMI: ' + entry.asset.fmi_ast"></span>
                                    <span x-show="!entry.unknown && entry.asset?.item_type" x-text="entry.asset.item_type"></span>
                                    <span x-show="!entry.unknown && entry.is_child_expand">Child</span>
                                    <span x-show="!entry.unknown && entry.duplicate" class="text-amber-700 dark:text-amber-300">Already on list</span>
                                    <span x-show="!entry.unknown && entry.inventory_status === 'not_on_inventory'" class="text-amber-700 dark:text-amber-300">Not on inventory</span>
                                    <span x-show="!entry.unknown && entry.inventory_status === 'matched'" class="text-brand">On inventory</span>
                                    <span x-show="entry.unknown" class="text-red-700 dark:text-red-300">Missing asset</span>
                                    <span x-show="entry.unknown && entry.unknown_scan?.location" x-text="'Location: ' + entry.unknown_scan.location"></span>
                                    <span x-show="entry.unknown && entry.unknown_scan?.note" class="truncate max-w-[14rem]" x-text="'Note: ' + entry.unknown_scan.note"></span>
                                </div>
                                <div class="flex items-center gap-2" x-show="entry.unknown && entry.unknown_scan" x-cloak>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center p-1 border border-brand-charcoal dark:border-brand-silver"
                                        title="Add note"
                                        aria-label="Add note"
                                        @click="toggleNoteEditor(entry)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                        </svg>
                                    </button>
                                    <a
                                        x-show="!entry.unknown_scan.resolved"
                                        :href="entry.unknown_scan.create_url"
                                        class="inline-flex items-center justify-center p-1 border border-brand-charcoal dark:border-brand-silver"
                                        title="Create asset"
                                        aria-label="Create asset"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                        </svg>
                                    </a>
                                </div>
                                <div class="space-y-1" x-show="entry.editingNote" x-cloak>
                                    <textarea
                                        x-model="entry.noteDraft"
                                        rows="2"
                                        class="w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm"
                                        placeholder="Note to help identify this item later…"
                                    ></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="px-2 py-1 border text-xs" @click="entry.editingNote = false">Cancel</button>
                                        <button type="button" class="px-2 py-1 bg-black text-white dark:bg-brand text-xs" @click="saveNote(entry)" :disabled="entry.savingNote">Save note</button>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold">On event list</h3>
                        <div class="flex items-center gap-2">
                            <a
                                href="<?php echo e(route('lists.show', $session->event_list_id)); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-xs text-brand underline"
                            >Open list</a>
                            <span class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="eventItems.length + ' item' + (eventItems.length === 1 ? '' : 's')"></span>
                        </div>
                    </div>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="eventItems.length === 0" x-cloak>No items on this event list yet.</p>
                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal border border-brand-silver dark:border-brand-charcoal max-h-72 overflow-y-auto" x-show="eventItems.length > 0">
                        <template x-for="entry in eventItems" :key="'event-' + entry.id">
                            <li class="px-3 py-1.5 text-sm flex items-baseline justify-between gap-2" :class="entry.is_child_expand ? 'pl-6' : ''">
                                <span class="truncate" x-text="entry.asset?.name || 'Unknown asset'"></span>
                                <span class="text-xs text-brand-charcoal dark:text-brand-silver whitespace-nowrap shrink-0">
                                    <span x-show="entry.asset?.tp_barcode" x-text="entry.asset.tp_barcode"></span>
                                    <span x-show="!entry.asset?.tp_barcode && entry.asset?.fmi_ast" x-text="entry.asset.fmi_ast"></span>
                                </span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="<?php echo e(route('reports.compare')); ?>?scanned_list_id=<?php echo e($session->event_list_id); ?>&inventory_list_id=<?php echo e($session->inventory_list_id); ?>" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Compare report</a>
                <form method="POST" action="<?php echo e(route('scan.close', $session)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Save &amp; close <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/scan/index.blade.php ENDPATH**/ ?>
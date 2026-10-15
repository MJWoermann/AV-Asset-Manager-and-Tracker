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
            <h2 class="font-semibold text-xl"><?php echo e($asset->name); ?></h2>
            <div class="flex flex-wrap gap-2">
                <?php if($asset->ip_address): ?>
                    <a href="http://<?php echo e($asset->ip_address); ?>" target="_blank" rel="noopener" class="px-3 py-2 bg-brand text-white text-sm">Open IP</a>
                <?php endif; ?>
                <a href="<?php echo e(route('assets.edit', $asset)); ?>" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Edit</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex gap-1 border-b border-brand-silver dark:border-brand-charcoal" role="tablist">
            <a
                href="<?php echo e(route('assets.show', $asset)); ?>"
                role="tab"
                aria-selected="<?php echo e($tab === 'details' ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'details' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                Details
            </a>
            <a
                href="<?php echo e(route('assets.show', [$asset, 'tab' => 'files'])); ?>"
                role="tab"
                aria-selected="<?php echo e($tab === 'files' ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'files' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                Files
            </a>
            <?php if($connectionsEnabled ?? false): ?>
                <a
                    href="<?php echo e(route('assets.show', [$asset, 'tab' => 'connections'])); ?>"
                    role="tab"
                    aria-selected="<?php echo e($tab === 'connections' ? 'true' : 'false'); ?>"
                    class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'connections' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
                >
                    Connections
                </a>
            <?php endif; ?>
            <?php if($ruVisualiserEnabled ?? false): ?>
                <a
                    href="<?php echo e(route('assets.show', [$asset, 'tab' => 'rack'])); ?>"
                    role="tab"
                    aria-selected="<?php echo e($tab === 'rack' ? 'true' : 'false'); ?>"
                    class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'rack' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
                >
                    Rack
                </a>
            <?php endif; ?>
            <?php if($stockEnabled ?? false): ?>
                <a
                    href="<?php echo e(route('assets.show', [$asset, 'tab' => 'stock'])); ?>"
                    role="tab"
                    aria-selected="<?php echo e($tab === 'stock' ? 'true' : 'false'); ?>"
                    class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'stock' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
                >
                    Stock
                </a>
            <?php endif; ?>
            <a
                href="<?php echo e(route('assets.show', [$asset, 'tab' => 'maintenance'])); ?>"
                role="tab"
                aria-selected="<?php echo e($tab === 'maintenance' ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'maintenance' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                Maintenance
            </a>
            <a
                href="<?php echo e(route('assets.show', [$asset, 'tab' => 'history'])); ?>"
                role="tab"
                aria-selected="<?php echo e($tab === 'history' ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'history' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                History
            </a>
        </div>

        <?php if($tab === 'rack'): ?>
            <?php echo $__env->make('assets.partials.rack', [
                'asset' => $asset,
                'ruPlacementsPayload' => $ruPlacementsPayload ?? [],
                'ruChildAssetOptions' => $ruChildAssetOptions ?? [],
                'ruUnplacedChildren' => $ruUnplacedChildren ?? collect(),
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($tab === 'stock'): ?>
            <?php echo $__env->make('assets.partials.stock', [
                'asset' => $asset,
                'stockChartPayload' => $stockChartPayload ?? [],
                'stockTokens' => $stockTokens ?? collect(),
                'stockReorderMeta' => $stockReorderMeta ?? [],
                'stockAction' => $stockAction ?? null,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($tab === 'connections'): ?>
            <?php echo $__env->make('assets.partials.connections', [
                'asset' => $asset,
                'connectionPeers' => $connectionPeers ?? collect(),
                'connectionTypes' => $connectionTypes ?? collect(),
                'peerAssetOptions' => $peerAssetOptions ?? [],
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($tab === 'files'): ?>
            <?php if (isset($component)) { $__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.file-attachments','data' => ['linkableType' => 'asset','linkableId' => $asset->id,'rows' => $fileRows,'canMutate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('file-attachments'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['linkable-type' => 'asset','linkable-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($asset->id),'rows' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fileRows),'can-mutate' => true]); ?>
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
        <?php elseif($tab === 'maintenance'): ?>
            <?php echo $__env->make('assets.partials.maintenance', [
                'asset' => $asset,
                'maintenanceLogs' => $maintenanceLogs ?? collect(),
                'maintenanceUserOptions' => $maintenanceUserOptions ?? collect(),
                'maintenanceLogTypes' => $maintenanceLogTypes ?? [],
                'maintenanceStatuses' => $maintenanceStatuses ?? [],
                'canDeleteMaintenanceLogs' => $canDeleteMaintenanceLogs ?? false,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($tab === 'history'): ?>
            <?php
                $historyFiltersActive = \App\Support\AssetHistoryFilters::hasActiveFilters(request());
            ?>
            <form
                method="GET"
                action="<?php echo e(route('assets.show', $asset)); ?>"
                class="flex flex-col sm:flex-row flex-wrap gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4"
            >
                <input type="hidden" name="tab" value="history">
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'date_from','value' => 'From']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'date_from','value' => 'From']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'date_from','name' => 'date_from','type' => 'date','class' => 'mt-1 block w-full','value' => request('date_from')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'date_from','name' => 'date_from','type' => 'date','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('date_from'))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'date_to','value' => 'To']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'date_to','value' => 'To']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'date_to','name' => 'date_to','type' => 'date','class' => 'mt-1 block w-full','value' => request('date_to')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'date_to','name' => 'date_to','type' => 'date','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('date_to'))]); ?>
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
                <div class="min-w-[10rem]">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'history_action','value' => 'Action']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'history_action','value' => 'Action']); ?>
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
                    <select id="history_action" name="action" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">All</option>
                        <?php $__currentLoopData = $historyActionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(request('action') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="min-w-[10rem]">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'history_user_id','value' => 'User']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'history_user_id','value' => 'User']); ?>
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
                    <select id="history_user_id" name="user_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">All</option>
                        <?php $__currentLoopData = $historyUserOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option['value']); ?>" <?php if((string) request('user_id') === (string) $option['value']): echo 'selected'; endif; ?>><?php echo e($option['label']); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="flex items-end gap-2 flex-wrap">
                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Filter <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                    <?php if($historyFiltersActive): ?>
                        <a href="<?php echo e(route('assets.show', [$asset, 'tab' => 'history'])); ?>" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                <table class="min-w-full text-sm">
                    <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                        <tr>
                            <th class="px-3 py-2">When</th>
                            <th class="px-3 py-2">Action</th>
                            <th class="px-3 py-2">User</th>
                            <th class="px-3 py-2">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="border-t border-brand-silver dark:border-brand-charcoal align-top">
                                <td class="px-3 py-2 whitespace-nowrap"><?php echo e($log->created_at?->format('Y-m-d H:i')); ?></td>
                                <td class="px-3 py-2 capitalize"><?php echo e(str_replace('_', ' ', $log->action)); ?></td>
                                <td class="px-3 py-2"><?php echo e($log->user?->name ?? 'System'); ?></td>
                                <td class="px-3 py-2">
                                    <?php if($log->action === 'created'): ?>
                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset created</span>
                                    <?php elseif($log->action === 'deleted'): ?>
                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset deleted</span>
                                    <?php elseif($log->action === 'file_linked'): ?>
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Linked file: <?php echo e($log->new_values['title'] ?? '—'); ?>

                                            <?php if(! empty($log->new_values['category'])): ?>
                                                (<?php echo e($log->new_values['category']); ?>)
                                            <?php endif; ?>
                                        </span>
                                    <?php elseif($log->action === 'file_unlinked'): ?>
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Unlinked file: <?php echo e($log->old_values['title'] ?? '—'); ?>

                                        </span>
                                    <?php else: ?>
                                        <?php $keys = $log->changedAttributeKeys(); ?>
                                        <?php if($keys === []): ?>
                                            <span class="text-brand-charcoal dark:text-brand-silver">—</span>
                                        <?php else: ?>
                                            <ul class="space-y-1">
                                                <?php $__currentLoopData = $keys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li>
                                                        <span class="text-brand-charcoal dark:text-brand-silver"><?php echo e(str_replace('_', ' ', $key)); ?>:</span>
                                                        <span class="font-mono"><?php echo e($log->formatValue($log->old_values[$key] ?? null, 40)); ?></span>
                                                        →
                                                        <span class="font-mono"><?php echo e($log->formatValue($log->new_values[$key] ?? null, 40)); ?></span>
                                                    </li>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </ul>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">
                                    <?php echo e($historyFiltersActive ? 'No history matches these filters.' : 'No history recorded yet.'); ?>

                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div><?php echo e($auditLogs->links()); ?></div>
        <?php else: ?>
            <?php $ruDetailsPreview = $ruDetailsPreview ?? null; ?>
            <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'grid gap-4 items-start',
                'grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(12rem,16rem)]' => $ruDetailsPreview,
            ]); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 text-sm">
                    <div class="md:col-span-2"><span class="text-brand-charcoal dark:text-brand-silver">Description</span><div><?php echo e($asset->description ?: '—'); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Type</span><div><?php echo e($asset->itemType?->name); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Status</span><div><?php echo e($asset->status?->label()); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Manufacturer / Model</span><div><?php echo e($asset->manufacturer); ?> <?php echo e($asset->model); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Location</span><div><?php echo e($asset->location?->breadcrumb() ?? '—'); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">FMI AST#</span><div class="font-mono"><?php echo e($asset->fmi_ast ?: '—'); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">TP Barcode</span><div class="font-mono"><?php echo e($asset->tp_barcode ?: '—'); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">RIG Tag #</span><div class="font-mono"><?php echo e($asset->rig_tag ?: '—'); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Serial Number</span><div class="font-mono"><?php echo e($asset->serial_number ?: '—'); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">IP / MAC</span><div class="font-mono"><?php echo e($asset->ip_address ?: '—'); ?> / <?php echo e($asset->mac_address ?: '—'); ?></div></div>
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Test &amp; Tag</span><div><?php echo e($asset->test_tag_expiry?->format('Y-m-d') ?: '—'); ?></div></div>
                    <?php if($asset->parent): ?>
                        <div class="md:col-span-2"><span class="text-brand-charcoal dark:text-brand-silver">Parent</span><div><a class="text-brand" href="<?php echo e(route('assets.show', $asset->parent)); ?>"><?php echo e($asset->parent->name); ?></a></div></div>
                    <?php endif; ?>
                </div>

                <?php if($ruDetailsPreview): ?>
                    <div
                        class="space-y-2"
                        x-data="ruRackPreview(<?php echo \Illuminate\Support\Js::from($ruDetailsPreview)->toHtml() ?>)"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold">Rack front</h3>
                            <a href="<?php echo e(route('assets.show', [$asset, 'tab' => 'rack'])); ?>" class="text-xs text-brand">Open Rack</a>
                        </div>
                        <?php echo $__env->make('assets.partials.ru-diagram', ['interactive' => false, 'compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($asset->itemType?->fieldSets->isNotEmpty()): ?>
                <?php
                    $customValues = $asset->customFieldValues->keyBy('custom_field_definition_id');
                ?>
                <?php $__currentLoopData = $asset->itemType->fieldSets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $set): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($set->definitions->isEmpty()) continue; ?>
                    <div>
                        <h3 class="font-semibold mb-2"><?php echo e($set->name); ?></h3>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 text-sm grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php $__currentLoopData = $set->definitions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $def): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($def->type === 'divider'): ?>
                                    <div class="sm:col-span-2 py-1">
                                        <?php if($def->name !== '' && strcasecmp($def->name, 'Divider') !== 0): ?>
                                            <div class="font-medium text-brand-charcoal dark:text-brand-silver mb-2"><?php echo e($def->name); ?></div>
                                        <?php endif; ?>
                                        <hr class="border-brand-silver dark:border-brand-charcoal">
                                    </div>
                                <?php elseif($def->type === 'spacer'): ?>
                                    <div class="min-h-[1.5rem]" aria-hidden="true"></div>
                                <?php else: ?>
                                    <div class="flex justify-between gap-4 border-b border-brand-silver/50 dark:border-brand-charcoal pb-1">
                                        <span><?php echo e($def->name); ?></span>
                                        <span class="font-mono"><?php echo e($customValues[$def->id]->value ?? '—'); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php elseif($asset->customFieldValues->isNotEmpty()): ?>
                <div>
                    <h3 class="font-semibold mb-2">Custom fields</h3>
                    <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 text-sm space-y-2">
                        <?php $__currentLoopData = $asset->customFieldValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex justify-between gap-4 border-b border-brand-silver/50 dark:border-brand-charcoal pb-1">
                                <span><?php echo e($value->definition?->name); ?></span>
                                <span class="font-mono"><?php echo e($value->value); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($asset->children->isNotEmpty()): ?>
                <div>
                    <h3 class="font-semibold mb-2">Child assets</h3>
                    <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal divide-y divide-brand-silver dark:divide-brand-charcoal">
                        <?php $__currentLoopData = $asset->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="px-4 py-2 pl-8 text-sm">
                                <a class="text-brand" href="<?php echo e(route('assets.show', $child)); ?>"><?php echo e($child->name); ?></a>
                                <span class="text-brand-charcoal dark:text-brand-silver"> — <?php echo e($child->tp_barcode); ?></span>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/show.blade.php ENDPATH**/ ?>
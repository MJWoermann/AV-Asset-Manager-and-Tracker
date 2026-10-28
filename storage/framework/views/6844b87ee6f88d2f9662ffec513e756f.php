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
                <?php if(! ($isExternalRepairer ?? false) && $asset->ip_address): ?>
                    <a href="http://<?php echo e($asset->ip_address); ?>" target="_blank" rel="noopener" class="px-3 py-2 bg-brand text-white text-sm">Open IP</a>
                <?php endif; ?>
                <?php if (! ($isExternalRepairer ?? false)): ?>
                    <?php
                        $openableCustomUrls = collect();
                        $customValuesByDefinition = $asset->customFieldValues->keyBy('custom_field_definition_id');
                        foreach ($asset->itemType?->fieldSets ?? [] as $urlFieldSet) {
                            foreach ($urlFieldSet->definitions as $urlDef) {
                                if ($urlDef->type !== 'url') {
                                    continue;
                                }
                                $href = \App\Models\CustomFieldDefinition::openableHttpUrl(
                                    $customValuesByDefinition[$urlDef->id]->value ?? null
                                );
                                if ($href !== null) {
                                    $openableCustomUrls->push([
                                        'url' => $href,
                                        'name' => $urlDef->name,
                                    ]);
                                }
                            }
                        }
                    ?>
                    <?php $__currentLoopData = $openableCustomUrls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $openableCustomUrl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a
                            href="<?php echo e($openableCustomUrl['url']); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            title="<?php echo e($openableCustomUrl['name']); ?>"
                            class="px-3 py-2 bg-brand text-white text-sm"
                        >Open URL</a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <form method="POST" action="<?php echo e(route('scan.prepare-parent', $asset)); ?>" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Scan children</button>
                    </form>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('spares:manage')): ?>
                        <?php if($listedSpare ?? null): ?>
                            <a href="<?php echo e(route('spares.show', $listedSpare)); ?>" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">In spares register</a>
                        <?php else: ?>
                            <form method="POST" action="<?php echo e(route('assets.spares.store', $asset)); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">List in spares</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="<?php echo e(route('assets.edit', $asset)); ?>" data-history-roundtrip class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Edit</a>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('labels:read')): ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'shelf_tag_generator')): ?>
                            <a href="<?php echo e(route('assets.label', $asset)); ?>" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Print</a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
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
            <?php if (! ($isExternalRepairer ?? false)): ?>
            <a
                href="<?php echo e(route('assets.show', [$asset, 'tab' => 'files'])); ?>"
                role="tab"
                aria-selected="<?php echo e($tab === 'files' ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'files' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                Files
            </a>
            <?php endif; ?>
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
            <?php if (! ($isExternalRepairer ?? false)): ?>
            <a
                href="<?php echo e(route('assets.show', [$asset, 'tab' => 'checkout'])); ?>"
                role="tab"
                aria-selected="<?php echo e($tab === 'checkout' ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'checkout' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                Checkout
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
            <?php if (! ($isExternalRepairer ?? false)): ?>
            <a
                href="<?php echo e(route('assets.show', [$asset, 'tab' => 'spares'])); ?>"
                role="tab"
                aria-selected="<?php echo e($tab === 'spares' ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'spares' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                Spares
            </a>
            <a
                href="<?php echo e(route('assets.show', [$asset, 'tab' => 'history'])); ?>"
                role="tab"
                aria-selected="<?php echo e($tab === 'history' ? 'true' : 'false'); ?>"
                class="px-4 py-2 text-sm border-b-2 -mb-px <?php echo e($tab === 'history' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver hover:text-black dark:hover:text-white'); ?>"
            >
                History
            </a>
            <?php endif; ?>
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
        <?php elseif($tab === 'spares'): ?>
            <?php echo $__env->make('assets.partials.spares', ['spareMovements' => $spareMovements ?? collect()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($tab === 'connections'): ?>
            <?php echo $__env->make('assets.partials.connections', [
                'asset' => $asset,
                'connectionPeers' => $connectionPeers ?? collect(),
                'busMemberships' => $busMemberships ?? collect(),
                'connectionBusesByType' => $connectionBusesByType ?? [],
                'connectionTypes' => $connectionTypes ?? collect(),
                'peerAssetOptions' => $peerAssetOptions ?? [],
                'canApplyInterfaceTemplates' => $canApplyInterfaceTemplates ?? false,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($tab === 'checkout'): ?>
            <?php echo $__env->make('assets.partials.checkout', [
                'asset' => $asset,
                'openCheckout' => $openCheckout ?? null,
                'checkoutHistory' => $checkoutHistory ?? null,
                'canManageCheckouts' => $canManageCheckouts ?? false,
                'checkoutPurposes' => $checkoutPurposes ?? [],
                'checkoutAssigneeOptions' => $checkoutAssigneeOptions ?? collect(),
                'checkoutOpenRepairs' => $checkoutOpenRepairs ?? collect(),
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
                'repairAssigneeOptions' => $repairAssigneeOptions ?? collect(),
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'date_from','name' => 'date_from','type' => 'date','class' => 'mt-1 block w-full','value' => request('date_from'),'onchange' => 'this.form.requestSubmit()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'date_from','name' => 'date_from','type' => 'date','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('date_from')),'onchange' => 'this.form.requestSubmit()']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'date_to','name' => 'date_to','type' => 'date','class' => 'mt-1 block w-full','value' => request('date_to'),'onchange' => 'this.form.requestSubmit()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'date_to','name' => 'date_to','type' => 'date','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('date_to')),'onchange' => 'this.form.requestSubmit()']); ?>
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
                    <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'action','id' => 'history_action','options' => collect($historyActionOptions)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),'selected' => request('action'),'placeholder' => 'All','nullable' => true,'autoSubmit' => true,'ariaLabel' => 'Filter by action']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'action','id' => 'history_action','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(collect($historyActionOptions)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('action')),'placeholder' => 'All','nullable' => true,'auto-submit' => true,'aria-label' => 'Filter by action']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $attributes = $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $component = $__componentOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
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
                    <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'user_id','id' => 'history_user_id','options' => $historyUserOptions,'selected' => request('user_id'),'placeholder' => 'All','nullable' => true,'autoSubmit' => true,'ariaLabel' => 'Filter by user']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user_id','id' => 'history_user_id','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($historyUserOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('user_id')),'placeholder' => 'All','nullable' => true,'auto-submit' => true,'aria-label' => 'Filter by user']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $attributes = $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $component = $__componentOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
                </div>
                <div class="flex items-end gap-2 flex-wrap">
                    <?php if (isset($component)) { $__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.clear-filters','data' => ['href' => route('assets.show', [$asset, 'tab' => 'history']),'active' => $historyFiltersActive]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('clear-filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('assets.show', [$asset, 'tab' => 'history'])),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($historyFiltersActive)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113)): ?>
<?php $attributes = $__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113; ?>
<?php unset($__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113)): ?>
<?php $component = $__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113; ?>
<?php unset($__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113); ?>
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
                                    <?php elseif($log->action === 'connection_created'): ?>
                                        <?php $vals = $log->new_values ?? []; ?>
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Connected to <?php echo e($vals['peer_asset'] ?? '—'); ?>:
                                            <?php echo e($vals['local_interface'] ?? '—'); ?> ↔ <?php echo e($vals['peer_interface'] ?? '—'); ?>

                                            <?php if(! empty($vals['connection_type'])): ?>
                                                (<?php echo e($vals['connection_type']); ?><?php if(! empty($vals['protocol'])): ?> · <?php echo e($vals['protocol']); ?><?php endif; ?>)
                                            <?php endif; ?>
                                            <?php if(! empty($vals['label'])): ?>
                                                — <?php echo e($vals['label']); ?>

                                            <?php endif; ?>
                                        </span>
                                    <?php elseif($log->action === 'connection_removed'): ?>
                                        <?php $vals = $log->old_values ?? []; ?>
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Disconnected from <?php echo e($vals['peer_asset'] ?? '—'); ?>:
                                            <?php echo e($vals['local_interface'] ?? '—'); ?> ↔ <?php echo e($vals['peer_interface'] ?? '—'); ?>

                                            <?php if(! empty($vals['connection_type'])): ?>
                                                (<?php echo e($vals['connection_type']); ?><?php if(! empty($vals['protocol'])): ?> · <?php echo e($vals['protocol']); ?><?php endif; ?>)
                                            <?php endif; ?>
                                            <?php if(! empty($vals['label'])): ?>
                                                — <?php echo e($vals['label']); ?>

                                            <?php endif; ?>
                                        </span>
                                    <?php elseif($log->action === 'bus_joined'): ?>
                                        <?php $vals = $log->new_values ?? []; ?>
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Joined bus <?php echo e($vals['bus_name'] ?? '—'); ?>

                                            via <?php echo e($vals['local_interface'] ?? '—'); ?>

                                            <?php if(! empty($vals['connection_type'])): ?>
                                                (<?php echo e($vals['connection_type']); ?>)
                                            <?php endif; ?>
                                        </span>
                                    <?php elseif($log->action === 'bus_left'): ?>
                                        <?php $vals = $log->old_values ?? []; ?>
                                        <span class="text-brand-charcoal dark:text-brand-silver">
                                            Left bus <?php echo e($vals['bus_name'] ?? '—'); ?>

                                            via <?php echo e($vals['local_interface'] ?? '—'); ?>

                                            <?php if(! empty($vals['connection_type'])): ?>
                                                (<?php echo e($vals['connection_type']); ?>)
                                            <?php endif; ?>
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
                    <div><span class="text-brand-charcoal dark:text-brand-silver">Manufacturer / Model</span><div><?php echo e(trim(($asset->manufacturer?->name ?? '').' '.($asset->manufacturerModel?->name ?? '')) ?: '—'); ?></div></div>
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
                                    <?php
                                        $customValue = $customValues[$def->id]->value ?? null;
                                        $openableUrl = $def->type === 'url'
                                            ? \App\Models\CustomFieldDefinition::openableHttpUrl($customValue)
                                            : null;
                                    ?>
                                    <div class="flex justify-between gap-4 border-b border-brand-silver/50 dark:border-brand-charcoal pb-1">
                                        <span><?php echo e($def->name); ?></span>
                                        <span class="font-mono text-right inline-flex flex-col items-end gap-1 sm:flex-row sm:items-center">
                                            <span><?php echo e($customValue ?: '—'); ?></span>
                                            <?php if($openableUrl): ?>
                                                <a
                                                    href="<?php echo e($openableUrl); ?>"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex items-center px-2 py-0.5 text-xs bg-brand text-white no-underline font-sans"
                                                >Open URL</a>
                                            <?php endif; ?>
                                        </span>
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
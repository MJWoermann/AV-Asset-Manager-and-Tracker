<?php
    $allowsProtocolByType = $connectionTypes->mapWithKeys(fn ($type) => [(string) $type->id => (bool) $type->allows_protocol])->all();
    $allowsBusByType = $connectionTypes->mapWithKeys(fn ($type) => [(string) $type->id => (bool) $type->allows_bus])->all();
    $localInterfacesPayload = $asset->interfaces->map(fn ($interface) => [
        'id' => (string) $interface->id,
        'name' => $interface->name,
        'connection_type_id' => $interface->connection_type_id ? (string) $interface->connection_type_id : null,
        'on_bus' => $interface->currentBus() !== null,
    ])->values()->all();
    $busCapableInterfaces = $asset->interfaces->filter(
        fn ($interface) => $interface->connectionType?->allows_bus && $interface->currentBus() === null
    );
?>

<div class="space-y-8" x-data="assetConnectionsForm(<?php echo \Illuminate\Support\Js::from([
    'peerInterfacesUrl' => route('assets.peer-interfaces', [$asset, 'peer' => '__PEER__']),
    'allowsProtocolByType' => $allowsProtocolByType,
    'allowsBusByType' => $allowsBusByType,
    'busesByType' => $connectionBusesByType ?? [],
    'localInterfaces' => $localInterfacesPayload,
    'peerAssets' => $peerAssetOptions,
    'old' => [
        'local_interface_id' => old('local_interface_id') ? (string) old('local_interface_id') : '',
        'peer_asset_id' => old('peer_asset_id') ? (string) old('peer_asset_id') : '',
        'peer_interface_id' => old('peer_interface_id') ? (string) old('peer_interface_id') : '',
        'new_peer_interface_name' => old('new_peer_interface_name', ''),
        'create_peer_interface' => old('new_peer_interface_name') ? true : false,
        'bus_local_interface_id' => old('local_interface_id') && old('new_bus_name', old('connection_bus_id'))
            ? (string) old('local_interface_id')
            : '',
        'connection_bus_id' => old('connection_bus_id') ? (string) old('connection_bus_id') : '',
        'new_bus_name' => old('new_bus_name', ''),
        'create_new_bus' => old('new_bus_name') ? true : false,
    ],
])->toHtml() ?>)">
    <?php if(session('status')): ?>
        <p class="text-sm text-brand"><?php echo e(session('status')); ?></p>
    <?php endif; ?>
    <?php if($errors->any()): ?>
        <div class="text-sm text-red-600 space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <p><?php echo e($error); ?></p>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-lg">Interfaces</h3>
            <?php if($canApplyInterfaceTemplates ?? false): ?>
                <form method="POST" action="<?php echo e(route('assets.interfaces.apply-templates', $asset)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>Apply missing templates <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                </form>
            <?php endif; ?>
        </div>

        <div class="space-y-2">
            <?php $__empty_1 = true; $__currentLoopData = $asset->interfaces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $interface): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="bg-white dark:bg-brand-slate border px-3 py-2">
                    <form method="POST" action="<?php echo e(route('assets.interfaces.update', [$asset, $interface])); ?>" class="flex flex-wrap items-center gap-2">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <div class="min-w-[8rem] flex-1">
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'name','class' => 'block w-full','value' => old('name', $interface->name),'required' => true,'placeholder' => 'Name','ariaLabel' => 'Name','title' => 'Name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','class' => 'block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('name', $interface->name)),'required' => true,'placeholder' => 'Name','aria-label' => 'Name','title' => 'Name']); ?>
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
                        <div class="min-w-[9rem] w-44">
                            <select name="connection_type_id" class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required aria-label="Connection type" title="Connection type">
                                <option value="">Type…</option>
                                <?php $__currentLoopData = $connectionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($type->id); ?>" <?php if((string) old('connection_type_id', $interface->connection_type_id) === (string) $type->id): echo 'selected'; endif; ?>><?php echo e($type->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="w-20">
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'sort_order','type' => 'number','class' => 'block w-full','value' => old('sort_order', $interface->sort_order),'placeholder' => 'Sort','ariaLabel' => 'Sort','title' => 'Sort']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'sort_order','type' => 'number','class' => 'block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('sort_order', $interface->sort_order)),'placeholder' => 'Sort','aria-label' => 'Sort','title' => 'Sort']); ?>
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
                        <div class="min-w-[8rem] flex-1">
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'notes','class' => 'block w-full','value' => old('notes', $interface->notes),'placeholder' => 'Notes','ariaLabel' => 'Notes','title' => 'Notes']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'notes','class' => 'block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('notes', $interface->notes)),'placeholder' => 'Notes','aria-label' => 'Notes','title' => 'Notes']); ?>
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
                        <div class="flex items-center gap-2 shrink-0">
                            <?php if($interface->isFromItemTypeTemplate()): ?>
                                <span class="text-xs text-brand whitespace-nowrap" title="From item type template">Type template</span>
                            <?php elseif($interface->isFromManufacturerModelTemplate()): ?>
                                <span class="text-xs text-brand whitespace-nowrap" title="From manufacturer model template">Model template</span>
                            <?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>Save <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                            <button
                                type="submit"
                                form="destroy-interface-<?php echo e($interface->id); ?>"
                                class="text-sm text-red-600 whitespace-nowrap"
                                onclick="return confirm('Remove this interface and its connections?')"
                            >Remove</button>
                        </div>
                    </form>
                    <form id="destroy-interface-<?php echo e($interface->id); ?>" method="POST" action="<?php echo e(route('assets.interfaces.destroy', [$asset, $interface])); ?>" class="hidden">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                    </form>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">No interfaces yet. Add one below or apply missing templates from the item type and/or manufacturer model.</p>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?php echo e(route('assets.interfaces.store', $asset)); ?>" class="bg-white dark:bg-brand-slate border px-3 py-2 flex flex-wrap items-center gap-2">
            <?php echo csrf_field(); ?>
            <div class="min-w-[8rem] flex-1">
                <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'new_interface_name','name' => 'name','class' => 'block w-full','required' => true,'placeholder' => 'Name','ariaLabel' => 'Interface name','title' => 'Name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'new_interface_name','name' => 'name','class' => 'block w-full','required' => true,'placeholder' => 'Name','aria-label' => 'Interface name','title' => 'Name']); ?>
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
            <div class="min-w-[9rem] w-44">
                <select id="new_interface_type" name="connection_type_id" class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required aria-label="Connection type" title="Connection type">
                    <option value="">Type…</option>
                    <?php $__currentLoopData = $connectionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($type->id); ?>"><?php echo e($type->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="min-w-[8rem] flex-1">
                <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'notes','class' => 'block w-full','placeholder' => 'Notes','ariaLabel' => 'Notes','title' => 'Notes']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'notes','class' => 'block w-full','placeholder' => 'Notes','aria-label' => 'Notes','title' => 'Notes']); ?>
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
            <div class="shrink-0">
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Add <?php echo $__env->renderComponent(); ?>
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
    </section>

    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-lg">Connections</h3>
            <?php if($connectionPeers->isNotEmpty() || ($busMemberships ?? collect())->isNotEmpty()): ?>
                <a
                    href="<?php echo e(route('reports.connection-map', ['asset_id' => $asset->id])); ?>"
                    class="text-sm text-brand font-medium"
                >View connection map</a>
            <?php endif; ?>
        </div>

        <div class="space-y-2">
            <?php $__empty_1 = true; $__currentLoopData = $connectionPeers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    /** @var \App\Models\AssetConnection $connection */
                    $connection = $row['connection'];
                    $local = $row['local_interface'];
                    $peerInterface = $row['peer_interface'];
                    $peerAsset = $row['peer_asset'];
                    $type = $connection->connectionType;
                ?>
                <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-start">
                    <div class="min-w-0 space-y-1">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="inline-block h-3 w-3 rounded-sm" style="background-color: <?php echo e($type?->color ?? '#64748b'); ?>"></span>
                            <?php if (isset($component)) { $__componentOriginalfea741d9327fe7b55315608624860d68 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfea741d9327fe7b55315608624860d68 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.connection-type-icon','data' => ['icon' => $type?->icon ?? 'generic','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('connection-type-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type?->icon ?? 'generic'),'class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfea741d9327fe7b55315608624860d68)): ?>
<?php $attributes = $__attributesOriginalfea741d9327fe7b55315608624860d68; ?>
<?php unset($__attributesOriginalfea741d9327fe7b55315608624860d68); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfea741d9327fe7b55315608624860d68)): ?>
<?php $component = $__componentOriginalfea741d9327fe7b55315608624860d68; ?>
<?php unset($__componentOriginalfea741d9327fe7b55315608624860d68); ?>
<?php endif; ?>
                            <span class="font-medium"><?php echo e($type?->name ?? 'Connection'); ?></span>
                            <?php if($connection->protocol): ?>
                                <span class="text-brand-charcoal dark:text-brand-silver">(<?php echo e($connection->protocol); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm">
                            <?php echo e($local->name); ?>

                            →
                            <a href="<?php echo e(route('assets.show', [$peerAsset, 'tab' => 'connections'])); ?>" class="text-brand font-medium"><?php echo e($peerAsset->name); ?></a>
                            / <?php echo e($peerInterface->name); ?>

                        </p>
                        <?php if($connection->label || $connection->notes): ?>
                            <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                                <?php echo e($connection->label); ?>

                                <?php if($connection->label && $connection->notes): ?> · <?php endif; ?>
                                <?php echo e($connection->notes); ?>

                            </p>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="<?php echo e(route('assets.connections.destroy', [$asset, $connection])); ?>" onsubmit="return confirm('Remove this connection?')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="text-sm text-red-600">Remove</button>
                    </form>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">No connections yet.</p>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?php echo e(route('assets.connections.store', $asset)); ?>" class="bg-white dark:bg-brand-slate border p-4 space-y-3">
            <?php echo csrf_field(); ?>
            <h4 class="text-sm font-semibold">Add connection</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'local_interface_id','value' => 'Local interface']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'local_interface_id','value' => 'Local interface']); ?>
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
                        id="local_interface_id"
                        name="local_interface_id"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        required
                        x-model="localInterfaceId"
                        @change="onLocalInterfaceChange()"
                    >
                        <option value="">Select…</option>
                        <?php $__currentLoopData = $asset->interfaces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $interface): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($interface->id); ?>">
                                <?php echo e($interface->name); ?><?php if($interface->connectionType): ?> (<?php echo e($interface->connectionType->name); ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'peer_asset_id','value' => 'Peer asset']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'peer_asset_id','value' => 'Peer asset']); ?>
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
                    <div
                        class="relative mt-1"
                        x-ref="peerSelect"
                        @keydown="onPeerKeydown($event)"
                        @click.outside="closePeerList()"
                    >
                        <input type="hidden" name="peer_asset_id" id="peer_asset_id" x-model="peerAssetId" required>

                        <div class="flex gap-1">
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 items-center justify-between gap-2 rounded border border-gray-300 bg-white px-3 py-2 text-left text-sm dark:border-brand-charcoal dark:bg-black dark:text-white"
                                @click="peerOpen ? closePeerList() : openPeerList()"
                                :aria-expanded="peerOpen.toString()"
                                aria-haspopup="listbox"
                                aria-controls="peer_asset_id-listbox"
                            >
                                <span class="truncate" :class="peerAssetId ? '' : 'text-brand-charcoal dark:text-brand-silver'" x-text="peerSelectedLabel"></span>
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div
                            x-show="peerOpen"
                            x-cloak
                            class="absolute z-20 mt-1 w-full border border-brand-silver bg-white shadow-sm dark:border-brand-charcoal dark:bg-brand-slate"
                            id="peer_asset_id-listbox"
                            role="listbox"
                        >
                            <div class="border-b border-brand-silver p-2 dark:border-brand-charcoal">
                                <input
                                    type="search"
                                    x-ref="peerSearch"
                                    x-model="peerQuery"
                                    placeholder="Search…"
                                    class="block w-full rounded border-gray-300 text-sm dark:border-brand-charcoal dark:bg-black dark:text-white"
                                    autocomplete="off"
                                >
                            </div>
                            <ul class="max-h-60 overflow-y-auto py-1 text-sm">
                                <template x-if="filteredPeerAssets.length === 0">
                                    <li class="px-3 py-2 text-brand-charcoal dark:text-brand-silver">No matches</li>
                                </template>
                                <template x-for="(option, index) in filteredPeerAssets" :key="option.value">
                                    <li>
                                        <button
                                            type="button"
                                            class="block w-full px-3 py-2 text-left hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal"
                                            :class="{
                                                'bg-brand-silver/40 dark:bg-brand-charcoal': index === peerHighlight || option.value === peerAssetId,
                                            }"
                                            role="option"
                                            :aria-selected="(option.value === peerAssetId).toString()"
                                            @click="selectPeerAsset(option)"
                                            @mouseenter="peerHighlight = index"
                                        >
                                            <span x-html="highlightedPeerLabel(option.label)"></span>
                                            <span
                                                class="ml-1 text-xs text-brand"
                                                x-show="option.matches_type"
                                                x-cloak
                                            >matching type</span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'peer_interface_id','value' => 'Peer interface']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'peer_interface_id','value' => 'Peer interface']); ?>
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
                        id="peer_interface_id"
                        name="peer_interface_id"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        x-model="peerInterfaceId"
                        :disabled="!peerAssetId || createPeerInterface"
                        :required="Boolean(peerAssetId) && !createPeerInterface"
                    >
                        <option value="">Select peer asset first…</option>
                        <template x-for="iface in peerInterfaces" :key="iface.id">
                            <option :value="iface.id" x-text="iface.matches_type ? iface.name + ' ★' : iface.name"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-show="peerLoading">Loading interfaces…</p>
                    <label class="mt-2 flex items-center gap-2 text-sm" x-show="peerAssetId" x-cloak>
                        <input type="checkbox" x-model="createPeerInterface" @change="onCreatePeerToggle()">
                        <span>Create new peer interface</span>
                    </label>
                    <div class="mt-2" x-show="createPeerInterface" x-cloak>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'new_peer_interface_name','value' => 'New peer interface name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'new_peer_interface_name','value' => 'New peer interface name']); ?>
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
                        <input
                            type="text"
                            id="new_peer_interface_name"
                            name="new_peer_interface_name"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            x-model="newPeerInterfaceName"
                            :required="createPeerInterface"
                            :disabled="!createPeerInterface"
                        >
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                            Uses the local interface connection type.
                        </p>
                    </div>
                </div>
                <div x-show="needsProtocol" x-cloak>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'protocol','value' => 'Protocol']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'protocol','value' => 'Protocol']); ?>
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
                        id="protocol"
                        name="protocol"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        :required="needsProtocol"
                    >
                        <option value="">Select protocol…</option>
                        <?php $__currentLoopData = $connectionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($type->name); ?>" <?php if(old('protocol') === $type->name): echo 'selected'; endif; ?>><?php echo e($type->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('protocol'),'class' => 'mt-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('protocol')),'class' => 'mt-1']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'label','value' => 'Label']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'label','value' => 'Label']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'label','name' => 'label','class' => 'mt-1 block w-full','value' => old('label')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'label','name' => 'label','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('label'))]); ?>
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
                <div class="sm:col-span-2">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'notes','value' => 'Notes']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'notes','value' => 'Notes']); ?>
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
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"><?php echo e(old('notes')); ?></textarea>
                </div>
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
<?php $component->withAttributes([]); ?>Create connection <?php echo $__env->renderComponent(); ?>
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
    </section>

    <?php if($connectionTypes->contains(fn ($type) => $type->allows_bus)): ?>
        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-lg">Device buses</h3>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    1-to-many shared buses for types like Dante — join several interfaces onto one bus.
                </p>
            </div>

            <div class="space-y-2">
                <?php $__empty_1 = true; $__currentLoopData = $busMemberships ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        /** @var \App\Models\ConnectionBus $bus */
                        $bus = $row['bus'];
                        $local = $row['local_interface'];
                        $members = $row['members'];
                        $type = $bus->connectionType;
                    ?>
                    <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-start">
                        <div class="min-w-0 space-y-1">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="inline-block h-3 w-3 rounded-sm" style="background-color: <?php echo e($type?->color ?? '#64748b'); ?>"></span>
                                <?php if (isset($component)) { $__componentOriginalfea741d9327fe7b55315608624860d68 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfea741d9327fe7b55315608624860d68 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.connection-type-icon','data' => ['icon' => $type?->icon ?? 'generic','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('connection-type-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type?->icon ?? 'generic'),'class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfea741d9327fe7b55315608624860d68)): ?>
<?php $attributes = $__attributesOriginalfea741d9327fe7b55315608624860d68; ?>
<?php unset($__attributesOriginalfea741d9327fe7b55315608624860d68); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfea741d9327fe7b55315608624860d68)): ?>
<?php $component = $__componentOriginalfea741d9327fe7b55315608624860d68; ?>
<?php unset($__componentOriginalfea741d9327fe7b55315608624860d68); ?>
<?php endif; ?>
                                <span class="font-medium"><?php echo e($bus->name); ?></span>
                                <span class="text-brand-charcoal dark:text-brand-silver">(<?php echo e($type?->name ?? 'Bus'); ?>)</span>
                            </div>
                            <p class="text-sm">
                                Local: <?php echo e($local->name); ?>

                            </p>
                            <?php if($members->isNotEmpty()): ?>
                                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                                    Also on this bus:
                                    <?php $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($member->asset): ?>
                                            <a href="<?php echo e(route('assets.show', [$member->asset, 'tab' => 'connections'])); ?>" class="text-brand font-medium"><?php echo e($member->asset->name); ?></a>/<?php echo e($member->name); ?><?php if(! $loop->last): ?>, <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-xs text-brand-charcoal dark:text-brand-silver">No other members yet.</p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="<?php echo e(route('assets.interfaces.bus.destroy', [$asset, $local])); ?>" onsubmit="return confirm('Leave this device bus?')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="text-sm text-red-600">Leave</button>
                        </form>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">Not on any device buses yet.</p>
                <?php endif; ?>
            </div>

            <?php if($busCapableInterfaces->isNotEmpty()): ?>
                <form method="POST" action="<?php echo e(route('assets.buses.store', $asset)); ?>" class="bg-white dark:bg-brand-slate border p-4 space-y-3">
                    <?php echo csrf_field(); ?>
                    <h4 class="text-sm font-semibold">Join device bus</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'bus_local_interface_id','value' => 'Local interface']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'bus_local_interface_id','value' => 'Local interface']); ?>
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
                                id="bus_local_interface_id"
                                name="local_interface_id"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                required
                                x-model="busLocalInterfaceId"
                                @change="onBusLocalInterfaceChange()"
                            >
                                <option value="">Select…</option>
                                <?php $__currentLoopData = $busCapableInterfaces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $interface): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($interface->id); ?>">
                                        <?php echo e($interface->name); ?><?php if($interface->connectionType): ?> (<?php echo e($interface->connectionType->name); ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div x-show="busLocalInterfaceId" x-cloak>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'connection_bus_id','value' => 'Existing bus']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'connection_bus_id','value' => 'Existing bus']); ?>
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
                                id="connection_bus_id"
                                name="connection_bus_id"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="connectionBusId"
                                :disabled="createNewBus"
                                :required="Boolean(busLocalInterfaceId) && !createNewBus"
                            >
                                <option value="">Select…</option>
                                <template x-for="bus in availableBuses" :key="bus.id">
                                    <option :value="bus.id" x-text="bus.name + ' (' + bus.member_count + ')'"></option>
                                </template>
                            </select>
                            <label class="mt-2 flex items-center gap-2 text-sm">
                                <input type="checkbox" x-model="createNewBus" @change="onCreateNewBusToggle()">
                                <span>Create new bus</span>
                            </label>
                            <div class="mt-2" x-show="createNewBus" x-cloak>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'new_bus_name','value' => 'New bus name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'new_bus_name','value' => 'New bus name']); ?>
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
                                <input
                                    type="text"
                                    id="new_bus_name"
                                    name="new_bus_name"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                    x-model="newBusName"
                                    :required="createNewBus"
                                    :disabled="!createNewBus"
                                    placeholder="e.g. FOH Dante"
                                >
                            </div>
                        </div>
                        <div class="sm:col-span-2" x-show="createNewBus" x-cloak>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'bus_notes','value' => 'Notes (optional)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'bus_notes','value' => 'Notes (optional)']); ?>
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
                            <textarea id="bus_notes" name="notes" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"><?php echo e(old('notes')); ?></textarea>
                        </div>
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
<?php $component->withAttributes([]); ?>Join bus <?php echo $__env->renderComponent(); ?>
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
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/partials/connections.blade.php ENDPATH**/ ?>
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
        <h2 class="font-semibold text-xl">Connection map</h2>
     <?php $__env->endSlot(); ?>

    <div
        class="px-4 sm:px-6 lg:px-8"
        x-data="connectionMap(<?php echo \Illuminate\Support\Js::from($graph)->toHtml() ?>)"
    >
        <form method="GET" action="<?php echo e(route('reports.connection-map')); ?>" class="space-y-2">
            <?php if($filters['asset_id'] ?? null): ?>
                <input type="hidden" name="asset_id" value="<?php echo e($filters['asset_id']); ?>">
            <?php endif; ?>

            <?php if($focusAsset): ?>
                <div class="flex flex-wrap items-center gap-2 text-sm bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal px-3 py-2">
                    <span>
                        Focused on
                        <a href="<?php echo e(route('assets.show', [$focusAsset, 'tab' => 'connections'])); ?>" class="text-brand font-medium"><?php echo e($focusAsset->name); ?></a>
                        — connected assets fan out from the centre.
                    </span>
                    <a
                        href="<?php echo e(route('reports.connection-map', $clearFocusQuery)); ?>"
                        class="text-brand-charcoal dark:text-brand-silver underline"
                    >Show all</a>
                </div>
            <?php endif; ?>

            <div class="flex flex-wrap gap-2 items-end">
                <div class="min-w-[10rem] flex-1 sm:flex-none sm:w-52">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'search','value' => 'Search','class' => 'sr-only']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'search','value' => 'Search','class' => 'sr-only']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'search','name' => 'search','class' => 'block w-full text-sm','value' => $filters['search'] ?? '','placeholder' => 'Search asset / barcode']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'search','name' => 'search','class' => 'block w-full text-sm','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['search'] ?? ''),'placeholder' => 'Search asset / barcode']); ?>
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
                <div class="min-w-[8rem] flex-1 sm:flex-none sm:w-44">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'location_id','value' => 'Location','class' => 'sr-only']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'location_id','value' => 'Location','class' => 'sr-only']); ?>
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
                    <select id="location_id" name="location_id" class="block w-full rounded border-gray-300 text-sm dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">All locations</option>
                        <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($location->id); ?>" <?php if((string) ($filters['location_id'] ?? '') === (string) $location->id): echo 'selected'; endif; ?>><?php echo e($location->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="min-w-[8rem] flex-1 sm:flex-none sm:w-44">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'item_type_id','value' => 'Item type','class' => 'sr-only']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'item_type_id','value' => 'Item type','class' => 'sr-only']); ?>
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
                    <select id="item_type_id" name="item_type_id" class="block w-full rounded border-gray-300 text-sm dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">All item types</option>
                        <?php $__currentLoopData = $itemTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($itemType->id); ?>" <?php if((string) ($filters['item_type_id'] ?? '') === (string) $itemType->id): echo 'selected'; endif; ?>><?php echo e($itemType->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['class' => 'text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-sm']); ?>Apply <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                <p class="ms-auto text-xs text-brand-charcoal dark:text-brand-silver self-center">
                    <?php echo e($graph['asset_count'] ?? count($graph['nodes'])); ?> assets · <?php echo e(count($graph['edges'])); ?> connections
                </p>
            </div>

            <div class="relative border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate h-[calc(100vh-11.5rem)] min-h-[22rem]">
                <div class="absolute inset-x-0 top-0 z-20 pointer-events-none">
                    <div class="pointer-events-auto flex flex-wrap items-start gap-x-3 gap-y-1.5 px-2 py-1.5 bg-white/90 dark:bg-brand-slate/90 border-b border-brand-silver/80 dark:border-brand-charcoal/80 backdrop-blur-sm">
                        <?php $__currentLoopData = $connectionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    name="connection_type_ids[]"
                                    value="<?php echo e($type->id); ?>"
                                    class="rounded text-brand h-3.5 w-3.5"
                                    <?php if(in_array((string) $type->id, array_map('strval', $filters['connection_type_ids'] ?? []), true)): echo 'checked'; endif; ?>
                                    onchange="this.form.requestSubmit()"
                                >
                                <span class="inline-block h-2.5 w-2.5 rounded-sm shrink-0" style="background-color: <?php echo e($type->color); ?>"></span>
                                <span><?php echo e($type->name); ?></span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <div class="ms-auto relative" @click.outside="displayOpen = false">
                            <button
                                type="button"
                                class="text-xs px-2 py-1 border border-brand-charcoal/40 dark:border-brand-silver/40 bg-white dark:bg-brand-slate"
                                @click="displayOpen = ! displayOpen"
                                :aria-expanded="displayOpen.toString()"
                            >
                                Display
                            </button>
                            <div
                                x-show="displayOpen"
                                x-cloak
                                class="absolute end-0 mt-1 w-64 max-h-[min(70vh,28rem)] overflow-y-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal shadow-lg p-3 space-y-3 text-xs z-40"
                                style="display: none;"
                            >
                                <div>
                                    <p class="font-semibold text-sm mb-1.5">On map</p>
                                    <div class="space-y-1">
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.mapName"> Asset name</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.mapItemType"> Item type</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.mapLocation"> Location</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.mapTypeName"> Type name on nodes</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.showEdgeLabels"> Connection type names on links</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.showTypeIcons"> Connection type icons on nodes</label>
                                    </div>
                                </div>
                                <div class="border-t border-brand-silver dark:border-brand-charcoal pt-2">
                                    <p class="font-semibold text-sm mb-1.5">In details popup</p>
                                    <div class="space-y-1">
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupItemType"> Item type</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupLocation"> Location</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupTypeName"> Connection type</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupProtocol"> Protocol</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupConnectionLabel"> Connection label</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupInterfaces"> Interfaces</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupNotes"> Notes</label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-brand h-3.5 w-3.5" x-model="display.popupLink"> Open asset link</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-ref="cy" class="absolute inset-0"></div>

                <div
                    x-show="selection"
                    x-cloak
                    x-transition.opacity.duration.150ms
                    class="absolute z-30 bottom-3 right-3 left-3 sm:left-auto sm:w-80 max-h-[50%] overflow-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal shadow-lg p-3 space-y-2"
                >
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-sm">Details</h3>
                        <button
                            type="button"
                            class="text-brand-charcoal dark:text-brand-silver hover:text-brand-black dark:hover:text-white text-lg leading-none px-1"
                            @click="selection = null"
                            aria-label="Close details"
                        >&times;</button>
                    </div>

                    <template x-if="selection && selection.kind === 'node'">
                        <div class="space-y-2 text-sm">
                            <p class="font-medium" x-text="selection.name || selection.label"></p>
                            <p
                                x-show="selection.node_kind === 'bus'"
                                class="text-xs text-brand-charcoal dark:text-brand-silver"
                            >Device bus</p>
                            <p
                                x-show="selection.node_kind === 'bus' && selection.member_count != null"
                                class="text-brand-charcoal dark:text-brand-silver"
                            >
                                Members: <span x-text="selection.member_count"></span>
                            </p>
                            <p
                                x-show="display.popupTypeName && selection.type_name"
                                class="text-brand-charcoal dark:text-brand-silver"
                            >
                                Type: <span x-text="selection.type_name"></span>
                            </p>
                            <p
                                x-show="selection.node_kind !== 'bus' && (display.popupItemType || display.popupLocation)"
                                class="text-brand-charcoal dark:text-brand-silver"
                            >
                                <span x-show="display.popupItemType" x-text="selection.item_type || '—'"></span>
                                <span x-show="display.popupItemType && display.popupLocation"> · </span>
                                <span x-show="display.popupLocation" x-text="selection.location || 'No location'"></span>
                            </p>
                            <a
                                x-show="display.popupLink && selection.url"
                                :href="selection.url"
                                class="inline-block text-brand"
                            >Open Connections tab</a>
                        </div>
                    </template>

                    <template x-if="selection && selection.kind === 'edge'">
                        <div class="space-y-2 text-sm">
                            <p
                                x-show="display.popupTypeName"
                                class="font-medium"
                                x-text="selection.label"
                            ></p>
                            <p x-show="display.popupProtocol && selection.protocol" class="text-brand-charcoal dark:text-brand-silver">
                                Protocol: <span x-text="selection.protocol"></span>
                            </p>
                            <p
                                x-show="display.popupConnectionLabel && selection.connection_label"
                                class="text-brand-charcoal dark:text-brand-silver"
                                x-text="selection.connection_label"
                            ></p>
                            <template x-if="display.popupInterfaces">
                                <div class="space-y-1">
                                    <p>
                                        <a :href="selection.asset_a_url" class="text-brand" x-text="selection.asset_a_name"></a>
                                        / <span x-text="selection.interface_a"></span>
                                    </p>
                                    <p x-show="selection.is_bus">
                                        Bus: <span x-text="selection.interface_b"></span>
                                    </p>
                                    <p x-show="!selection.is_bus">
                                        <a :href="selection.asset_b_url" class="text-brand" x-text="selection.asset_b_name"></a>
                                        / <span x-text="selection.interface_b"></span>
                                    </p>
                                </div>
                            </template>
                            <p x-show="display.popupNotes && selection.notes" class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="selection.notes"></p>
                        </div>
                    </template>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/reports/connection-map.blade.php ENDPATH**/ ?>
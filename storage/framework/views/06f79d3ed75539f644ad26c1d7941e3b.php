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
            <h2 class="font-semibold text-xl text-brand-black dark:text-white">Dashboard</h2>
            <div
                class="relative"
                x-data="dashboardModules(<?php echo \Illuminate\Support\Js::from($modules)->toHtml() ?>)"
                @click.outside="open = false"
            >
                <button
                    type="button"
                    class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver"
                    @click="open = ! open"
                    :aria-expanded="open.toString()"
                >
                    Customize modules
                </button>

                <div
                    x-show="open"
                    x-cloak
                    class="absolute z-40 mt-2 end-0 w-80 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-3 shadow-lg"
                    style="display: none;"
                >
                    <form method="POST" action="<?php echo e(route('preferences.dashboard-modules')); ?>" class="space-y-3">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                            Enable, disable, and reorder modules. Saved to your profile.
                        </p>
                        <ul class="space-y-2 max-h-80 overflow-y-auto">
                            <template x-for="(module, index) in modules" :key="module.id">
                                <li class="flex items-center gap-2 text-sm border border-brand-silver dark:border-brand-charcoal px-2 py-1.5">
                                    <input type="hidden" :name="'order[' + index + ']'" :value="module.id">
                                    <label class="flex items-center gap-2 flex-1 min-w-0">
                                        <input
                                            type="checkbox"
                                            name="enabled[]"
                                            :value="module.id"
                                            class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                                            :checked="module.enabled"
                                            @change="module.enabled = $event.target.checked"
                                        >
                                        <span class="truncate" x-text="module.label"></span>
                                    </label>
                                    <div class="flex shrink-0 gap-1">
                                        <button
                                            type="button"
                                            class="px-1.5 py-0.5 border border-brand-charcoal/30 dark:border-brand-silver text-xs disabled:opacity-40"
                                            @click="moveUp(index)"
                                            :disabled="index === 0"
                                            :aria-label="'Move ' + module.label + ' up'"
                                        >↑</button>
                                        <button
                                            type="button"
                                            class="px-1.5 py-0.5 border border-brand-charcoal/30 dark:border-brand-silver text-xs disabled:opacity-40"
                                            @click="moveDown(index)"
                                            :disabled="index === modules.length - 1"
                                            :aria-label="'Move ' + module.label + ' down'"
                                        >↓</button>
                                    </div>
                                </li>
                            </template>
                        </ul>
                        <button type="submit" class="w-full px-3 py-2 bg-black text-white dark:bg-brand text-sm">
                            Save modules
                        </button>
                    </form>
                </div>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <?php if(session('status')): ?>
            <div class="text-sm text-brand"><?php echo e(session('status')); ?></div>
        <?php endif; ?>

        <?php
            $enabledModuleIds = collect($modules)->where('enabled', true)->pluck('id')->all();
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(! ($module['enabled'] ?? false)) continue; ?>

                <?php switch($module['id']):
                    case ('quick_links'): ?>
                        <div class="lg:col-span-2 xl:col-span-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php $__currentLoopData = $quickLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a
                                    href="<?php echo e($link['url']); ?>"
                                    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                        'block p-5 border transition',
                                        'bg-black text-white border-brand-charcoal hover:bg-brand-slate hover:border-brand' => $link['primary'],
                                        'bg-white dark:bg-brand-slate border-brand-silver dark:border-brand-charcoal hover:border-brand dark:hover:bg-brand-charcoal' => ! $link['primary'],
                                    ]); ?>"
                                >
                                    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                        'text-sm uppercase tracking-wide',
                                        'text-brand' => $link['primary'],
                                        'text-brand' => ! $link['primary'],
                                    ]); ?>"><?php echo e($link['eyebrow']); ?></div>
                                    <div class="mt-2 text-lg font-semibold"><?php echo e($link['label']); ?></div>
                                    <p class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                        'mt-1 text-sm',
                                        'text-brand-silver' => $link['primary'],
                                        'text-brand-charcoal dark:text-brand-silver' => ! $link['primary'],
                                    ]); ?>"><?php echo e($link['description']); ?></p>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php break; ?>

                    <?php case ('stocktake_pulse'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Stocktake pulse</h3>
                                <?php if($stocktakePulse['resumeUrl']): ?>
                                    <a href="<?php echo e($stocktakePulse['resumeUrl']); ?>" class="text-xs text-brand hover:underline">Resume session</a>
                                <?php endif; ?>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $stocktakePulse['buckets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => $row['key'] === 'open_unknowns' && $row['count'] > 0]); ?>">
                                                <?php echo e($row['label']); ?>

                                            </span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('assets_needing_attention'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets needing attention</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $assetsNeedingAttention; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => $row['count'] > 0]); ?>"><?php echo e($row['label']); ?></span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('assets_by_location'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets by location</h3>
                            </div>
                            <?php if($assetsByLocation === []): ?>
                                <p class="px-4 py-6 text-sm text-center text-brand-charcoal dark:text-brand-silver">No assets yet.</p>
                            <?php else: ?>
                                <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm max-h-80 overflow-y-auto">
                                    <?php $__currentLoopData = $assetsByLocation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li>
                                            <a
                                                href="<?php echo e($row['url']); ?>"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span><?php echo e($row['label']); ?></span>
                                                <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <?php break; ?>

                    <?php case ('data_quality'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Catalogue completeness</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $dataQuality; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => $row['count'] > 0]); ?>"><?php echo e($row['label']); ?></span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('list_activity'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Lists snapshot</h3>
                                <a href="<?php echo e(route('lists.index')); ?>" class="text-xs text-brand hover:underline">All lists</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $listActivity['counts']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span><?php echo e($row['label']); ?></span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                            <div class="border-t border-brand-silver dark:border-brand-charcoal">
                                <p class="px-4 pt-3 pb-1 text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Recent activity</p>
                                <?php if($listActivity['recent'] === []): ?>
                                    <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No lists yet.</p>
                                <?php else: ?>
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        <?php $__currentLoopData = $listActivity['recent']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>
                                                <a
                                                    href="<?php echo e($list['url']); ?>"
                                                    class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                                >
                                                    <span class="font-medium"><?php echo e($list['name']); ?></span>
                                                    <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                        <?php echo e(ucfirst($list['type'])); ?> — <?php echo e($list['items_count']); ?> items
                                                        <?php if($list['last_activity']): ?>
                                                            — <?php echo e($list['last_activity']); ?>

                                                        <?php endif; ?>
                                                    </span>
                                                </a>
                                            </li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case ('my_repairs'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">My repairs</h3>
                                <a href="<?php echo e(route('repairs.index', ['assignee' => 'me'])); ?>" class="text-xs text-brand hover:underline">View all</a>
                            </div>
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal text-sm flex items-center justify-between">
                                <span>Open / in progress</span>
                                <span class="font-mono tabular-nums"><?php echo e($myRepairs['count']); ?></span>
                            </div>
                            <?php if($myRepairs['recent']->isEmpty()): ?>
                                <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No active repairs assigned to you.</p>
                            <?php else: ?>
                                <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                    <?php $__currentLoopData = $myRepairs['recent']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $repair): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li>
                                            <a
                                                href="<?php echo e(route('repairs.show', $repair)); ?>"
                                                class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span class="font-medium"><?php echo e($repair->summary); ?></span>
                                                <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                    <?php echo e($repair->asset?->name ?? 'Unknown asset'); ?>

                                                    — <?php echo e($repair->status?->label()); ?>

                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <?php break; ?>

                    <?php case ('spare_movements_recent'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Recent spare usage</h3>
                                <a href="<?php echo e(route('spares.index')); ?>" class="text-xs text-brand hover:underline">Register</a>
                            </div>
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal text-sm flex items-center justify-between">
                                <span>Usage events (7 days)</span>
                                <span class="font-mono tabular-nums"><?php echo e($spareMovementsRecent['usage7Days']); ?></span>
                            </div>
                            <?php if($spareMovementsRecent['recent']->isEmpty()): ?>
                                <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No spare movements yet.</p>
                            <?php else: ?>
                                <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                    <?php $__currentLoopData = $spareMovementsRecent['recent']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="px-4 py-2.5">
                                            <span class="font-medium"><?php echo e($movement->sparePart?->component ?? 'Spare part'); ?></span>
                                            <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                <?php echo e($movement->recorded_at?->format('Y-m-d H:i')); ?>

                                                — Δ <?php echo e($movement->quantity_delta); ?>

                                                <?php if($movement->againstAsset): ?>
                                                    — <?php echo e($movement->againstAsset->name); ?>

                                                <?php endif; ?>
                                            </span>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <?php break; ?>

                    <?php case ('consumables_stats'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Consumables statistics</h3>
                                <a href="<?php echo e(route('assets.index', ['stock_tracked' => 1])); ?>" class="text-xs text-brand hover:underline">Browse</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $consumablesStats['buckets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <?php if($row['url']): ?>
                                            <a
                                                href="<?php echo e($row['url']); ?>"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => in_array($row['key'], ['below_min', 'zero_on_hand', 'breakages_30'], true) && $row['count'] > 0]); ?>">
                                                    <?php echo e($row['label']); ?>

                                                </span>
                                                <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                                <span><?php echo e($row['label']); ?></span>
                                                <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                            <div class="border-t border-brand-silver dark:border-brand-charcoal">
                                <p class="px-4 pt-3 pb-1 text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Recent restocks</p>
                                <?php if($consumablesStats['recentRestocks']->isEmpty()): ?>
                                    <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No restocks recorded.</p>
                                <?php else: ?>
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        <?php $__currentLoopData = $consumablesStats['recentRestocks']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>
                                                <a
                                                    href="<?php echo e($movement->asset ? route('assets.show', [$movement->asset, 'tab' => 'stock']) : route('assets.index', ['stock_tracked' => 1])); ?>"
                                                    class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                                >
                                                    <span class="font-medium"><?php echo e($movement->asset?->name ?? 'Asset'); ?></span>
                                                    <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                        <?php echo e($movement->recorded_at?->format('Y-m-d H:i')); ?>

                                                        — +<?php echo e($movement->quantity_delta); ?>

                                                        <?php if($movement->external_po_number): ?>
                                                            — PO <?php echo e($movement->external_po_number); ?>

                                                        <?php endif; ?>
                                                    </span>
                                                </a>
                                            </li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case ('connection_coverage'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Connection coverage</h3>
                                <a href="<?php echo e(route('reports.connection-map')); ?>" class="text-xs text-brand hover:underline">Map</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $connectionCoverage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => $row['count'] > 0]); ?>"><?php echo e($row['label']); ?></span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('rigging_upcoming'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Rigging events</h3>
                                <a href="<?php echo e(route('rigging.events.index')); ?>" class="text-xs text-brand hover:underline">All events</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $riggingUpcoming; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span><?php echo e($row['label']); ?></span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('warranty_expiry'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Warranty expiry</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $warrantyExpiry; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => $row['key'] === 'overdue' && $row['count'] > 0]); ?>">
                                                <?php echo e($row['label']); ?>

                                            </span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('test_tag_expiry'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Test &amp; Tag expiry</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $testTagExpiry; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => $row['key'] === 'overdue' && $row['count'] > 0]); ?>">
                                                <?php echo e($row['label']); ?>

                                            </span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('assets_by_status'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets by status</h3>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $byStatus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e(route('assets.index', ['status' => $row['value']])); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span><?php echo e($row['label']); ?></span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('items_users_by_department'): ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
                            <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                                <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Items &amp; users by department</h3>
                                </div>
                                <?php if(($itemsUsersByDepartment ?? []) === []): ?>
                                    <p class="px-4 py-6 text-sm text-center text-brand-charcoal dark:text-brand-silver">No departments yet.</p>
                                <?php else: ?>
                                    <div class="overflow-x-auto max-h-80 overflow-y-auto">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left sticky top-0">
                                                <tr>
                                                    <th class="px-3 py-2 w-8"></th>
                                                    <th class="px-3 py-2">Department</th>
                                                    <th class="px-3 py-2 text-right">Items</th>
                                                    <th class="px-3 py-2 text-right">Users</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $itemsUsersByDepartment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                                                        <td class="px-3 py-2">
                                                            <?php if($row['color']): ?>
                                                                <span
                                                                    class="inline-block h-3.5 w-3.5 rounded border border-brand-charcoal/30 dark:border-brand-silver/40"
                                                                    style="background-color: <?php echo e($row['color']); ?>"
                                                                    title="<?php echo e($row['color']); ?>"
                                                                ></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <a href="<?php echo e($row['url']); ?>" class="hover:text-brand <?php if(! $row['is_active']): ?> text-brand-charcoal dark:text-brand-silver <?php endif; ?>">
                                                                <?php echo e($row['name']); ?>

                                                                <?php if (! ($row['is_active'])): ?>
                                                                    <span class="text-xs">(inactive)</span>
                                                                <?php endif; ?>
                                                            </a>
                                                        </td>
                                                        <td class="px-3 py-2 text-right font-mono tabular-nums"><?php echo e($row['item_count']); ?></td>
                                                        <td class="px-3 py-2 text-right font-mono tabular-nums"><?php echo e($row['user_count']); ?></td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case ('assets_by_type'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Assets by type</h3>
                            </div>
                            <?php if($byType === []): ?>
                                <p class="px-4 py-6 text-sm text-center text-brand-charcoal dark:text-brand-silver">No assets yet.</p>
                            <?php else: ?>
                                <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm max-h-80 overflow-y-auto">
                                    <?php $__currentLoopData = $byType; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li>
                                            <a
                                                href="<?php echo e(route('assets.index', ['item_type_id' => $row['id']])); ?>"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span><?php echo e($row['name']); ?></span>
                                                <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <?php break; ?>

                    <?php case ('maintenance_activities'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Maintenance activities</h3>
                                <a href="<?php echo e(route('maintenance.index')); ?>" class="text-xs text-brand hover:underline">Hub</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $maintenanceActivities['buckets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a
                                            href="<?php echo e($row['url']); ?>"
                                            class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                        >
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => in_array($row['key'], ['open', 'in_progress', 'assets_maintenance'], true) && $row['count'] > 0]); ?>">
                                                <?php echo e($row['label']); ?>

                                            </span>
                                            <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                            <div class="border-t border-brand-silver dark:border-brand-charcoal">
                                <p class="px-4 pt-3 pb-1 text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver">Active repairs</p>
                                <?php if($maintenanceActivities['recent']->isEmpty()): ?>
                                    <p class="px-4 py-3 text-sm text-brand-charcoal dark:text-brand-silver">No open repairs.</p>
                                <?php else: ?>
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        <?php $__currentLoopData = $maintenanceActivities['recent']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $repair): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>
                                                <a
                                                    href="<?php echo e(route('repairs.show', $repair)); ?>"
                                                    class="block px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                                >
                                                    <span class="font-medium"><?php echo e($repair->summary); ?></span>
                                                    <span class="mt-0.5 block text-xs text-brand-charcoal dark:text-brand-silver">
                                                        <?php echo e($repair->asset?->name ?? 'Unknown asset'); ?>

                                                        — <?php echo e($repair->status?->label()); ?>

                                                    </span>
                                                </a>
                                            </li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case ('spares_stats'): ?>
                        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Spares statistics</h3>
                                <a href="<?php echo e(route('spares.index')); ?>" class="text-xs text-brand hover:underline">Register</a>
                            </div>
                            <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                <?php $__currentLoopData = $sparesStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <?php if($row['url']): ?>
                                            <a
                                                href="<?php echo e($row['url']); ?>"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-700 dark:text-red-400' => in_array($row['key'], ['below_min', 'quarantine', 'critical'], true) && $row['count'] > 0]); ?>">
                                                    <?php echo e($row['label']); ?>

                                                </span>
                                                <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                                <span><?php echo e($row['label']); ?></span>
                                                <span class="font-mono tabular-nums"><?php echo e($row['count']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php break; ?>

                    <?php case ('inbound_emails'): ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'email_integration')): ?>
                            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                                <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                                    <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Inbound emails</h3>
                                    </div>
                                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                                        <li>
                                            <a
                                                href="<?php echo e(route('admin.email.inbound.index', ['status' => 'pending'])); ?>"
                                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal transition"
                                            >
                                                <span>Pending review</span>
                                                <span class="font-mono tabular-nums"><?php echo e($pendingInboundEmails ?? 0); ?></span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php break; ?>

                    <?php case ('latest_changes'): ?>
                        <div class="lg:col-span-2 xl:col-span-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                            <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand">Latest changes</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                                        <tr>
                                            <th class="px-3 py-2">When</th>
                                            <th class="px-3 py-2">Action</th>
                                            <th class="px-3 py-2">Asset</th>
                                            <th class="px-3 py-2">User</th>
                                            <th class="px-3 py-2">Changes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $latestChanges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr class="border-t border-brand-silver dark:border-brand-charcoal align-top">
                                                <td class="px-3 py-2 whitespace-nowrap"><?php echo e($log->created_at?->format('Y-m-d H:i')); ?></td>
                                                <td class="px-3 py-2 capitalize"><?php echo e($log->action); ?></td>
                                                <td class="px-3 py-2">
                                                    <?php if($log->auditable): ?>
                                                        <a
                                                            href="<?php echo e(route('assets.show', [$log->auditable, 'tab' => 'history'])); ?>"
                                                            class="text-brand hover:underline"
                                                        ><?php echo e($log->auditable->name); ?></a>
                                                    <?php else: ?>
                                                        <span class="text-brand-charcoal dark:text-brand-silver">Deleted asset #<?php echo e($log->auditable_id); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-3 py-2"><?php echo e($log->user?->name ?? 'System'); ?></td>
                                                <td class="px-3 py-2">
                                                    <?php if($log->action === 'created'): ?>
                                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset created</span>
                                                    <?php elseif($log->action === 'deleted'): ?>
                                                        <span class="text-brand-charcoal dark:text-brand-silver">Asset deleted</span>
                                                    <?php else: ?>
                                                        <?php $keys = $log->changedAttributeKeys(); ?>
                                                        <?php if($keys === []): ?>
                                                            <span class="text-brand-charcoal dark:text-brand-silver">—</span>
                                                        <?php else: ?>
                                                            <ul class="space-y-1">
                                                                <?php $__currentLoopData = array_slice($keys, 0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <li>
                                                                        <span class="text-brand-charcoal dark:text-brand-silver"><?php echo e(str_replace('_', ' ', $key)); ?>:</span>
                                                                        <span class="font-mono"><?php echo e($log->formatValue($log->old_values[$key] ?? null, 40)); ?></span>
                                                                        →
                                                                        <span class="font-mono"><?php echo e($log->formatValue($log->new_values[$key] ?? null, 40)); ?></span>
                                                                    </li>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                <?php if(count($keys) > 3): ?>
                                                                    <li class="text-brand-charcoal dark:text-brand-silver">+<?php echo e(count($keys) - 3); ?> more</li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr>
                                                <td colspan="5" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No recent changes.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php break; ?>
                <?php endswitch; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php if($enabledModuleIds === []): ?>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                No dashboard modules enabled. Use Customize modules to show widgets.
            </p>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/dashboard.blade.php ENDPATH**/ ?>
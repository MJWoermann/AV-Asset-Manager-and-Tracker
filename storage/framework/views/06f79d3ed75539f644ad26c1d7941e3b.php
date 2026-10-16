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
        <p class="text-brand-charcoal dark:text-brand-silver">AV equipment stocktake and asset management.</p>

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
                            <a href="<?php echo e(route('scan.index')); ?>" class="block p-5 bg-black text-white border border-brand-charcoal hover:bg-brand-slate hover:border-brand transition">
                                <div class="text-brand text-sm uppercase tracking-wide">Primary</div>
                                <div class="mt-2 text-lg font-semibold">Scan to event</div>
                                <p class="mt-1 text-sm text-brand-silver">Camera barcode scan with inventory compare</p>
                            </a>
                            <a href="<?php echo e(route('assets.index')); ?>" class="block p-5 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal hover:border-brand dark:hover:bg-brand-charcoal transition">
                                <div class="text-brand text-sm uppercase tracking-wide">Inventory</div>
                                <div class="mt-2 text-lg font-semibold">Assets</div>
                                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Search and edit equipment</p>
                            </a>
                            <a href="<?php echo e(route('reports.compare')); ?>" class="block p-5 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal hover:border-brand dark:hover:bg-brand-charcoal transition">
                                <div class="text-brand text-sm uppercase tracking-wide">Reports</div>
                                <div class="mt-2 text-lg font-semibold">Compare lists</div>
                                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Matched / missing / extras</p>
                            </a>
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
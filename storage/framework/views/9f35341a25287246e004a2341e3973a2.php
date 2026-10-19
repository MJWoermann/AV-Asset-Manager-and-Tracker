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
        <h2 class="font-semibold text-xl">Unknown barcode scans</h2>
     <?php $__env->endSlot(); ?>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
        x-data="{
            searchUrl: <?php echo \Illuminate\Support\Js::from(route('assets.search'))->toHtml() ?>,
            linkUrlTemplate: <?php echo \Illuminate\Support\Js::from(route('unknown-scans.link', ['unknownScan' => '__ID__']))->toHtml() ?>,
            scanId: null,
            scannedCode: '',
            hasEventList: false,
            hasLocation: false,
            identifierField: '',
            query: '',
            results: [],
            selectedId: null,
            selectedLabel: '',
            selectedAsset: null,
            searching: false,
            searchTimer: null,
            linkAction() {
                return this.linkUrlTemplate.replace('__ID__', String(this.scanId ?? ''));
            },
            openLink(id, code, hasEventList, hasLocation) {
                this.scanId = id;
                this.scannedCode = code;
                this.hasEventList = Boolean(hasEventList);
                this.hasLocation = Boolean(hasLocation);
                this.identifierField = '';
                this.query = '';
                this.results = [];
                this.selectedId = null;
                this.selectedLabel = '';
                this.selectedAsset = null;
                this.searching = false;
                this.$dispatch('open-modal', 'link-unknown-scan');
                this.$nextTick(() => this.$refs.linkSearch?.focus());
            },
            scheduleSearch() {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => this.search(), 250);
            },
            async search() {
                const term = this.query.trim();
                if (term.length < 1) {
                    this.results = [];
                    return;
                }
                this.searching = true;
                try {
                    const response = await fetch(this.searchUrl + '?q=' + encodeURIComponent(term), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const json = await response.json();
                    this.results = json.data || [];
                } finally {
                    this.searching = false;
                }
            },
            pick(asset) {
                this.selectedId = asset.id;
                this.selectedLabel = asset.label || asset.name;
                this.selectedAsset = asset;
                this.query = asset.label || asset.name;
                this.results = [];
            },
            selectedFieldCurrentValue() {
                if (!this.identifierField || !this.selectedAsset) {
                    return null;
                }
                const value = this.selectedAsset[this.identifierField];
                return value == null || value === '' ? null : String(value);
            },
        }"
    >
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Codes scanned during stocktake that were not found in the asset register. Export this report to identify and add missing assets.
        </p>

        <form method="GET" action="<?php echo e(route('reports.unknown-scans')); ?>" class="flex flex-col sm:flex-row flex-wrap gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
            <div class="flex-1 min-w-[12rem]">
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'unknown-scans-q','value' => 'Search code / note']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'unknown-scans-q','value' => 'Search code / note']); ?>
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
                <?php if (isset($component)) { $__componentOriginal6ba2bee0210b38146ba04d5bb3b73030 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scannable-search','data' => ['id' => 'unknown-scans-q','name' => 'q','value' => $q,'placeholder' => 'Barcode or note…','class' => 'mt-1','scanAriaLabel' => 'Scan barcode into unknown-scan search']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scannable-search'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'unknown-scans-q','name' => 'q','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($q),'placeholder' => 'Barcode or note…','class' => 'mt-1','scan-aria-label' => 'Scan barcode into unknown-scan search']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030)): ?>
<?php $attributes = $__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030; ?>
<?php unset($__attributesOriginal6ba2bee0210b38146ba04d5bb3b73030); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6ba2bee0210b38146ba04d5bb3b73030)): ?>
<?php $component = $__componentOriginal6ba2bee0210b38146ba04d5bb3b73030; ?>
<?php unset($__componentOriginal6ba2bee0210b38146ba04d5bb3b73030); ?>
<?php endif; ?>
            </div>
            <div class="min-w-[10rem]">
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'status','value' => 'Status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'status','value' => 'Status']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['name' => 'status','id' => 'status','options' => [
                        ['value' => 'open', 'label' => 'Open'],
                        ['value' => 'resolved', 'label' => 'Resolved'],
                        ['value' => 'all', 'label' => 'All'],
                    ],'selected' => $status,'autoSubmit' => true,'ariaLabel' => 'Filter by status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'status','id' => 'status','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                        ['value' => 'open', 'label' => 'Open'],
                        ['value' => 'resolved', 'label' => 'Resolved'],
                        ['value' => 'all', 'label' => 'All'],
                    ]),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status),'auto-submit' => true,'aria-label' => 'Filter by status']); ?>
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
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Search <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3713bcf4ead06ff5169b19e8fd8f7113 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.clear-filters','data' => ['href' => route('reports.unknown-scans'),'active' => filled($q) || $status !== 'open']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('clear-filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('reports.unknown-scans')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(filled($q) || $status !== 'open')]); ?>
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
                <a href="<?php echo e(route('reports.unknown-scans.export', ['format' => 'xlsx', 'status' => $status, 'q' => $q])); ?>" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Export XLSX</a>
                <a href="<?php echo e(route('reports.unknown-scans.export', ['format' => 'csv', 'status' => $status, 'q' => $q])); ?>" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Export CSV</a>
            </div>
        </form>

        <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin')): ?>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Admin: remove duplicate codes (keep the latest scan of each) or clear the entire unknown scans table.
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <form
                        method="POST"
                        action="<?php echo e(route('reports.unknown-scans.deduplicate')); ?>"
                        onsubmit="return confirm('Remove duplicate unknown scans, keeping only the latest scan of each code?')"
                    >
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
<?php $component->withAttributes(['type' => 'submit']); ?>Remove duplicates <?php echo $__env->renderComponent(); ?>
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
                    <form
                        method="POST"
                        action="<?php echo e(route('reports.unknown-scans.destroy-all')); ?>"
                        onsubmit="return confirm('Clear the entire unknown scans table? This cannot be undone.')"
                    >
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <?php if (isset($component)) { $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.danger-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('danger-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Clear all <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $attributes = $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $component = $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
<?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver/40 dark:bg-brand-charcoal/40 text-left">
                    <tr>
                        <th class="px-3 py-2 font-semibold">Scanned at</th>
                        <th class="px-3 py-2 font-semibold">Code</th>
                        <th class="px-3 py-2 font-semibold">User</th>
                        <th class="px-3 py-2 font-semibold">Location</th>
                        <th class="px-3 py-2 font-semibold">Event list</th>
                        <th class="px-3 py-2 font-semibold">Note</th>
                        <th class="px-3 py-2 font-semibold">Status</th>
                        <th class="px-3 py-2 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-silver dark:divide-brand-charcoal">
                    <?php $__empty_1 = true; $__currentLoopData = $scans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap"><?php echo e($scan->created_at?->format('Y-m-d H:i')); ?></td>
                            <td class="px-3 py-2 font-medium"><?php echo e($scan->scanned_code); ?></td>
                            <td class="px-3 py-2"><?php echo e($scan->user?->name); ?></td>
                            <td class="px-3 py-2"><?php echo e($scan->location?->breadcrumb() ?? '—'); ?></td>
                            <td class="px-3 py-2"><?php echo e($scan->eventList?->name ?? '—'); ?></td>
                            <td class="px-3 py-2 max-w-xs truncate" title="<?php echo e($scan->note); ?>"><?php echo e($scan->note ?: '—'); ?></td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <?php if($scan->isResolved()): ?>
                                    <a href="<?php echo e(route('assets.show', $scan->resolvedAsset)); ?>" class="text-brand underline">
                                        <?php echo e($scan->resolvedAsset?->name ?? 'Resolved'); ?>

                                    </a>
                                <?php else: ?>
                                    <span class="text-amber-700 dark:text-amber-300">Open</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                <?php if (! ($scan->isResolved())): ?>
                                    <div class="inline-flex items-center gap-3">
                                        <a
                                            href="<?php echo e(route('assets.create', [
                                                'tp_barcode' => $scan->scanned_code,
                                                'location_id' => $scan->location_id,
                                                'unknown_scan_id' => $scan->id,
                                                'status' => $scan->asset_status?->value,
                                                'return_to' => request()->getRequestUri(),
                                            ])); ?>"
                                            class="text-brand underline text-xs"
                                        >Add asset</a>
                                        <button
                                            type="button"
                                            class="text-brand underline text-xs"
                                            @click="openLink(<?php echo e($scan->id); ?>, <?php echo \Illuminate\Support\Js::from($scan->scanned_code)->toHtml() ?>, <?php echo e($scan->event_list_id ? 'true' : 'false'); ?>, <?php echo e($scan->location_id ? 'true' : 'false'); ?>)"
                                        >Link existing</button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">
                                No unknown scans match this filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <?php echo e($scans->links()); ?>

        </div>

        <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'link-unknown-scan','show' => $errors->has('asset_id') || $errors->has('identifier_field'),'focusable' => true,'maxWidth' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'link-unknown-scan','show' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->has('asset_id') || $errors->has('identifier_field')),'focusable' => true,'maxWidth' => 'md']); ?>
            <form method="POST" :action="linkAction()" class="p-6 space-y-4" @submit="if (!selectedId) { $event.preventDefault(); }">
                <?php echo csrf_field(); ?>

                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Link to existing asset
                </h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Resolve scanned code
                    <span class="font-medium text-gray-900 dark:text-white" x-text="scannedCode"></span>
                    by attaching it to an asset that already exists.
                </p>

                <div class="relative">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'link-asset-search','value' => 'Search assets']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'link-asset-search','value' => 'Search assets']); ?>
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
                    <input type="hidden" name="asset_id" :value="selectedId ?? ''" required>
                    <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'link-asset-search','xRef' => 'linkSearch','type' => 'search','class' => 'mt-1 block w-full','xModel' => 'query','@input' => 'scheduleSearch()','placeholder' => 'Name, barcode, FMI, serial…','autocomplete' => 'off']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'link-asset-search','x-ref' => 'linkSearch','type' => 'search','class' => 'mt-1 block w-full','x-model' => 'query','@input' => 'scheduleSearch()','placeholder' => 'Name, barcode, FMI, serial…','autocomplete' => 'off']); ?>
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
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-show="searching" x-cloak>Searching…</p>
                    <p class="mt-1 text-xs text-brand" x-show="selectedId" x-cloak>
                        Selected: <span x-text="selectedLabel"></span>
                    </p>
                    <ul
                        class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto border border-brand-silver bg-white shadow-sm dark:border-brand-charcoal dark:bg-brand-slate"
                        x-show="results.length > 0"
                        x-cloak
                    >
                        <template x-for="asset in results" :key="asset.id">
                            <li>
                                <button
                                    type="button"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal"
                                    @click="pick(asset)"
                                >
                                    <span x-text="asset.label || asset.name"></span>
                                    <span
                                        class="ml-1 text-xs text-brand-charcoal dark:text-brand-silver"
                                        x-show="asset.item_type"
                                        x-text="asset.item_type"
                                    ></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('asset_id'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('asset_id')),'class' => 'mt-2']); ?>
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

                <label class="flex items-start gap-2 text-sm" x-show="hasEventList" x-cloak>
                    <input type="checkbox" name="add_to_event_list" value="1" class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand dark:border-brand-charcoal dark:bg-black">
                    <span>Add asset to the event list from this scan (skip if already on the list)</span>
                </label>

                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'link-identifier-field','value' => 'Write scanned code to']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'link-identifier-field','value' => 'Write scanned code to']); ?>
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
                        id="link-identifier-field"
                        name="identifier_field"
                        x-model="identifierField"
                        class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-black dark:text-white focus:border-brand focus:ring-brand rounded-md shadow-sm text-sm"
                    >
                        <option value="">Don’t update an identifier</option>
                        <?php $__currentLoopData = \App\Support\ScannableIdentifierFields::linkTargets(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($field); ?>"><?php echo e(\App\Support\ScannableIdentifierFields::label($field)); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                        Replaces the chosen field on the linked asset. Skipped if another asset already uses this code.
                    </p>
                    <p
                        class="mt-1 text-xs text-amber-700 dark:text-amber-300"
                        x-show="identifierField && selectedFieldCurrentValue()"
                        x-cloak
                    >
                        Current value will be overwritten:
                        <span class="font-medium" x-text="selectedFieldCurrentValue()"></span>
                    </p>
                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('identifier_field'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('identifier_field')),'class' => 'mt-2']); ?>
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

                <p class="text-sm text-brand-charcoal dark:text-brand-silver" x-show="hasLocation" x-cloak>
                    This scan has a location — the linked asset’s location will be updated (child assets keep their parent’s location).
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','xOn:click' => '$dispatch(\'close\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','x-on:click' => '$dispatch(\'close\')']); ?>
                        Cancel
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['xBind:disabled' => '!selectedId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-bind:disabled' => '!selectedId']); ?>
                        Link asset
                     <?php echo $__env->renderComponent(); ?>
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
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/reports/unknown-scans.blade.php ENDPATH**/ ?>
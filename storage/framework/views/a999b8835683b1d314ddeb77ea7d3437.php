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
            <div>
                <h2 class="font-semibold text-xl">Print label</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver"><?php echo e($asset->name); ?></p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                <?php if($layout): ?>
                    <button type="button" onclick="window.print()" class="px-3 py-2 bg-brand text-white text-sm">Print</button>
                    <a href="<?php echo e(route('assets.label.pdf', [$asset, 'layout' => $layout->id])); ?>" class="px-3 py-2 border text-sm">Download PDF</a>
                <?php endif; ?>
                <a href="<?php echo e(route('assets.show', $asset)); ?>" class="px-3 py-2 border text-sm">Back to asset</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 print:max-w-none print:px-0 space-y-6">
        <div class="print:hidden bg-white dark:bg-brand-slate border p-4 max-w-md">
            <?php if($layouts->isEmpty()): ?>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Create a <a href="<?php echo e(route('labels.layouts.create')); ?>" class="text-brand underline">label layout</a> before printing.
                </p>
            <?php else: ?>
                <form method="GET" action="<?php echo e(route('assets.label', $asset)); ?>">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'layout','value' => 'Layout']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'layout','value' => 'Layout']); ?>
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
                        id="layout"
                        name="layout"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <?php $__currentLoopData = $layouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option->id); ?>" <?php if($layout && $layout->id == $option->id): echo 'selected'; endif; ?>>
                                <?php echo e($option->name); ?> (<?php echo e($option->width_mm); ?>×<?php echo e($option->height_mm); ?> mm)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>

        <?php if($layout && count($labels) > 0): ?>
            <div
                class="print:max-w-none"
                x-data="shelfTagPrintPreview(<?php echo \Illuminate\Support\Js::from($labels)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($layout->displayWidthMm())->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($layout->displayHeightMm())->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($layout->logoUrl())->toHtml() ?>)"
                x-init="renderAll()"
            >
                <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver print:hidden">
                    Select the matching Brother DK size, Actual size, Cut every for continuous.
                    <?php if($layout->isLandscape()): ?>
                        Landscape · <?php echo e($layout->displayWidthMm()); ?>×<?php echo e($layout->displayHeightMm()); ?> mm.
                    <?php endif; ?>
                </p>

                <div class="space-y-6 print:space-y-0">
                    <?php $__currentLoopData = $labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div
                            class="label-sheet mx-auto border border-brand-silver bg-white shadow-sm print:border-0 print:shadow-none"
                            style="width: <?php echo e($label['width_mm']); ?>mm; height: <?php echo e($label['height_mm']); ?>mm;"
                            data-label-index="<?php echo e($index); ?>"
                            data-orientation="<?php echo e($label['orientation'] ?? $layout->orientation); ?>"
                        >
                            <div class="relative w-full h-full overflow-hidden">
                                <?php $__currentLoopData = $label['elements']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php echo $__env->make('shelf-tags.print.partials.element', ['element' => $element, 'logoUrl' => $layout->logoUrl(), 'forPdf' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php elseif($layouts->isNotEmpty()): ?>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver print:hidden">Choose a layout to preview this asset’s label.</p>
        <?php endif; ?>
    </div>

    <?php if($layout): ?>
        <style>
            @media print {
                body * { visibility: hidden; }
                .label-sheet, .label-sheet * { visibility: visible; }
                .label-sheet {
                    position: relative;
                    page-break-after: always;
                    break-after: page;
                    margin: 0 !important;
                    box-shadow: none !important;
                }
                .label-sheet:last-child {
                    page-break-after: auto;
                    break-after: auto;
                }
                @page {
                    size: <?php echo e($layout->displayWidthMm()); ?>mm <?php echo e($layout->displayHeightMm()); ?>mm;
                    margin: 0;
                }
                nav, header, .print\\:hidden { display: none !important; }
            }
        </style>
    <?php endif; ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/shelf-tag-print.blade.php ENDPATH**/ ?>
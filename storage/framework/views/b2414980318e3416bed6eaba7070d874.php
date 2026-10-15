<?php
    $previewLabels = array_slice($labels, 0, $previewLimit);
?>

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
                <h2 class="font-semibold text-xl">Shelf tag preview</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    <?php echo e($count); ?> label<?php echo e($count === 1 ? '' : 's'); ?>

                    · <?php echo e($layout->name); ?>

                    · <?php echo e($list->name); ?>

                </p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                <button type="button" onclick="window.print()" class="px-3 py-2 bg-brand text-white text-sm">Print</button>
                <a href="<?php echo e(route('shelf-tags.print.pdf')); ?>" class="px-3 py-2 border text-sm">Download PDF</a>
                <a href="<?php echo e(route('shelf-tags.index', ['asset_list_id' => $list->id, 'shelf_tag_layout_id' => $layout->id])); ?>" class="px-3 py-2 border text-sm">Back</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="max-w-7xl mx-auto px-4 print:max-w-none print:px-0"
        x-data="shelfTagPrintPreview(<?php echo \Illuminate\Support\Js::from($labels)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from((float) $layout->width_mm)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from((float) $layout->height_mm)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($layout->logoUrl())->toHtml() ?>)"
        x-init="renderAll()"
    >
        <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver print:hidden">
            Showing first <?php echo e(count($previewLabels)); ?> of <?php echo e($count); ?> on screen. Print/PDF includes the full batch.
            Select the matching Brother DK size, Actual size, Cut every for continuous.
        </p>

        <div class="space-y-6 print:space-y-0">
            <?php $__currentLoopData = $labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div
                    class="label-sheet mx-auto border border-brand-silver bg-white shadow-sm print:border-0 print:shadow-none <?php echo e($index >= $previewLimit ? 'hidden print:block' : ''); ?>"
                    style="width: <?php echo e($label['width_mm']); ?>mm; height: <?php echo e($label['height_mm']); ?>mm;"
                    data-label-index="<?php echo e($index); ?>"
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
                size: <?php echo e($layout->width_mm); ?>mm <?php echo e($layout->height_mm); ?>mm;
                margin: 0;
            }
            nav, header, .print\\:hidden { display: none !important; }
        }
    </style>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/shelf-tags/print/preview.blade.php ENDPATH**/ ?>
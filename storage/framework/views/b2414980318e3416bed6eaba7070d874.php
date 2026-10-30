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
                <h2 class="font-semibold text-xl">Label preview</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    <?php echo e($count); ?> label<?php echo e($count === 1 ? '' : 's'); ?>

                    · <?php echo e($layout->name); ?>

                    · <?php echo e($list->name); ?>

                </p>
            </div>
            <div class="flex flex-wrap justify-end gap-2 print:hidden">
                <a href="<?php echo e(route('labels.index', ['asset_list_id' => $list->id, 'shelf_tag_layout_id' => $layout->id])); ?>" class="px-3 py-2 border text-sm">Back</a>
                <a href="<?php echo e(route('labels.print.pdf')); ?>" class="px-3 py-2 border text-sm">Download PDF</a>
                <button type="button" onclick="window.print()" class="px-3 py-2 bg-brand text-white text-sm">Print</button>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="max-w-7xl mx-auto px-4 print:max-w-none print:px-0"
        x-data="shelfTagPrintPreview(<?php echo \Illuminate\Support\Js::from($labels)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($layout->displayWidthMm())->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($layout->displayHeightMm())->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($layout->logoUrl())->toHtml() ?>)"
        x-init="renderAll()"
    >
        <p class="no-print mb-4 text-sm text-brand-charcoal dark:text-brand-silver print:hidden">
            Showing first <?php echo e(count($previewLabels)); ?> of <?php echo e($count); ?> on screen. Print/PDF includes the full batch.
            Select the matching Brother DK size, Actual size, Cut every for continuous.
            <?php if($layout->isLandscape()): ?>
                Landscape layout · <?php echo e($layout->displayWidthMm()); ?>×<?php echo e($layout->displayHeightMm()); ?> mm.
            <?php endif; ?>
        </p>

        <div class="label-print-root space-y-6 print:space-y-0">
            <?php $__currentLoopData = $labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div
                    class="label-sheet mx-auto border border-brand-silver bg-white shadow-sm print:border-0 print:shadow-none <?php echo e($index >= $previewLimit ? 'hidden print:block' : ''); ?>"
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

    <style>
        @media print {
            @page {
                size: <?php echo e($layout->displayWidthMm()); ?>mm <?php echo e($layout->displayHeightMm()); ?>mm;
                margin: 0;
            }
            html, body, body > div, main, .max-w-7xl, .label-print-root {
                width: <?php echo e($layout->displayWidthMm()); ?>mm !important;
                max-width: <?php echo e($layout->displayWidthMm()); ?>mm !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .no-print {
                display: none !important;
            }
            .label-sheet {
                position: relative;
                break-inside: avoid;
                page-break-inside: avoid;
                break-after: page;
                page-break-after: always;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .label-sheet:last-child {
                break-after: auto;
                page-break-after: auto;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const started = Date.now();
            const timer = window.setInterval(() => {
                const nodes = [...document.querySelectorAll('.shelf-tag-barcode')];
                const valued = nodes.filter((node) => (node.getAttribute('data-value') || '') !== '');
                const drawn = valued.filter((node) => node.getAttribute('viewBox'));
                if (valued.length > 0 && drawn.length < valued.length && Date.now() - started < 8000) {
                    return;
                }
                window.clearInterval(timer);

                const rows = nodes.map((node, index) => {
                    const sheet = node.closest('.label-sheet');
                    const parent = node.parentElement;
                    let bbox = null;
                    try {
                        const box = node.getBBox();
                        bbox = { w: Math.round(box.width * 100) / 100, h: Math.round(box.height * 100) / 100 };
                    } catch (e) {
                        bbox = null;
                    }

                    return {
                        index,
                        display: sheet ? getComputedStyle(sheet).display : null,
                        hiddenClass: !!(sheet && sheet.classList.contains('hidden')),
                        parentW: parent ? parent.clientWidth : 0,
                        parentH: parent ? parent.clientHeight : 0,
                        showText: node.getAttribute('data-show-text'),
                        viewBox: node.getAttribute('viewBox'),
                        bbox,
                    };
                });
                const textSample = [...document.querySelectorAll('.label-sheet')].slice(0, 2).map((sheet) => {
                    return [...sheet.querySelectorAll('.el')]
                        .filter((el) => !el.querySelector('svg, canvas, img'))
                        .map((el) => {
                            const inner = el.firstElementChild;
                            const style = inner ? getComputedStyle(inner) : null;

                            return {
                                fontSize: style ? style.fontSize : null,
                                fontFamily: style ? style.fontFamily : null,
                                inline: inner ? inner.getAttribute('style') : null,
                            };
                        });
                });

                // #region agent log
                fetch('http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'c42c70'},body:JSON.stringify({sessionId:'c42c70',runId:'post-fix',hypothesisId:'A',location:'preview.blade.php:barcode-measure',message:'print preview barcode boxes',data:{count:rows.length,zeroParent:rows.filter((row)=>row.parentW===0).length,hidden:rows.filter((row)=>row.hiddenClass).length,sample:rows.filter((row)=>row.index<8||row.index===rows.length-1)},timestamp:Date.now()})}).catch(()=>{});
                fetch('http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'c42c70'},body:JSON.stringify({sessionId:'c42c70',runId:'post-fix',hypothesisId:'E',location:'preview.blade.php:text-measure',message:'print preview text metrics',data:{textSample},timestamp:Date.now()})}).catch(()=>{});
                // #endregion
            }, 300);
        });
    </script>
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
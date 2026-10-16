<?php
    $sizeLabel = $file->size_bytes !== null
        ? number_format($file->size_bytes / 1024, $file->size_bytes >= 1024 * 100 ? 0 : 1).' KB'
        : null;
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
                <h2 class="font-semibold text-xl"><?php echo e($file->title); ?></h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver mt-1">
                    <a href="<?php echo e(route('files.index')); ?>" class="text-brand hover:underline">Back to library</a>
                </p>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
        x-data="{
            previewOpen: false,
            previewUrl: '',
            previewDownloadUrl: '',
            previewKind: 'image',
            previewTitle: '',
            openPreview(url, downloadUrl, kind, title) {
                this.previewUrl = url;
                this.previewDownloadUrl = downloadUrl;
                this.previewKind = kind;
                this.previewTitle = title;
                this.previewOpen = true;
            },
            closePreview() {
                this.previewOpen = false;
                this.previewUrl = '';
                this.previewDownloadUrl = '';
            }
        }"
    >
        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <?php if($file->hasThumbnail()): ?>
                        <img
                            src="<?php echo e(route('files.thumbnail', $file)); ?>"
                            alt=""
                            class="h-16 w-16 object-cover border border-brand-silver dark:border-brand-charcoal shrink-0"
                        >
                    <?php endif; ?>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm min-w-0">
                        <div>
                            <dt class="text-brand-charcoal dark:text-brand-silver">Category</dt>
                            <dd><?php echo e($file->category->label()); ?></dd>
                        </div>
                        <div>
                            <dt class="text-brand-charcoal dark:text-brand-silver">Source</dt>
                            <dd><?php echo e($file->source_type->label()); ?></dd>
                        </div>
                        <?php if($file->mime_type): ?>
                            <div>
                                <dt class="text-brand-charcoal dark:text-brand-silver">MIME</dt>
                                <dd class="font-mono text-xs"><?php echo e($file->mime_type); ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if($sizeLabel): ?>
                            <div>
                                <dt class="text-brand-charcoal dark:text-brand-silver">Size</dt>
                                <dd><?php echo e($sizeLabel); ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if($file->original_filename): ?>
                            <div class="sm:col-span-2">
                                <dt class="text-brand-charcoal dark:text-brand-silver">Filename</dt>
                                <dd class="font-mono text-xs break-all"><?php echo e($file->original_filename); ?></dd>
                            </div>
                        <?php endif; ?>
                        <div>
                            <dt class="text-brand-charcoal dark:text-brand-silver">Uploaded by</dt>
                            <dd><?php echo e($file->uploader?->name ?? '—'); ?></dd>
                        </div>
                    </dl>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <?php if($file->isPreviewable()): ?>
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 text-sm border border-brand text-brand"
                            @click="openPreview(<?php echo \Illuminate\Support\Js::from(route('files.preview', $file))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(route('files.download', $file))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($file->isPreviewablePdf() ? 'pdf' : 'image')->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($file->title)->toHtml() ?>)"
                        >Preview</button>
                    <?php endif; ?>
                    <?php if($file->isUrl()): ?>
                        <a href="<?php echo e($file->external_url); ?>" target="_blank" rel="noopener" class="inline-flex items-center px-3 py-1.5 text-sm border border-brand text-brand">Open URL</a>
                    <?php else: ?>
                        <a href="<?php echo e(route('files.download', $file)); ?>" class="inline-flex items-center px-3 py-1.5 text-sm border border-brand text-brand">Download</a>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo e(route('files.destroy', $file)); ?>" onsubmit="return confirm('Delete this file from the library? This removes all links.')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm border border-red-600 text-red-600 dark:border-red-400 dark:text-red-400">Delete</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <div class="px-3 py-2 border-b border-brand-silver dark:border-brand-charcoal">
                <h3 class="font-medium text-sm">Linked items</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Note</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2"><?php echo e($link->typeLabel()); ?></td>
                            <td class="px-3 py-2"><?php echo e($link->displayName()); ?></td>
                            <td class="px-3 py-2 text-brand-charcoal dark:text-brand-silver"><?php echo e($link->note ?: '—'); ?></td>
                            <td class="px-3 py-2 text-right">
                                <?php if($url = $link->destinationUrl()): ?>
                                    <a href="<?php echo e($url); ?>" class="text-brand">View</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">Not linked to any records</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php echo $__env->make('files.partials.preview-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/files/show.blade.php ENDPATH**/ ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'linkableType',
    'linkableId',
    'rows' => [],
    'canMutate' => true,
    'categories' => [],
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'linkableType',
    'linkableId',
    'rows' => [],
    'canMutate' => true,
    'categories' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $categories = $categories ?: \App\Enums\FileCategory::options();
    $composerShouldOpen = $canMutate
        && $errors->any()
        && (string) old('linkable_type') === (string) $linkableType
        && (string) old('linkable_id') === (string) $linkableId;
    $initialTab = old('external_url') !== null ? 'url' : 'upload';
?>

<div class="space-y-4" x-data="{
    composerOpen: <?php echo \Illuminate\Support\Js::from($composerShouldOpen)->toHtml() ?>,
    tab: <?php echo \Illuminate\Support\Js::from($initialTab)->toHtml() ?>,
    query: '',
    results: [],
    selectedId: null,
    selectedTitle: '',
    searching: false,
    previewOpen: false,
    previewUrl: '',
    previewDownloadUrl: '',
    previewKind: 'image',
    previewTitle: '',
    async search() {
        this.searching = true;
        try {
            const response = await fetch(<?php echo \Illuminate\Support\Js::from(route('files.search'))->toHtml() ?> + '?q=' + encodeURIComponent(this.query), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await response.json();
            this.results = json.data || [];
        } finally {
            this.searching = false;
        }
    },
    pick(file) {
        this.selectedId = file.id;
        this.selectedTitle = file.title;
        this.results = [];
        this.query = file.title;
    },
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
}">
    <div class="relative bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
        <?php if($canMutate): ?>
            <button
                type="button"
                class="absolute top-1.5 right-1.5 z-10 inline-flex items-center justify-center p-1 border border-brand-charcoal dark:border-brand-silver bg-white dark:bg-brand-slate text-brand-charcoal dark:text-brand-silver hover:border-brand hover:text-brand"
                title="Add file"
                aria-label="Add file"
                :aria-expanded="composerOpen.toString()"
                @click="composerOpen = !composerOpen"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
            </button>
        <?php endif; ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">Category</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Note</th>
                        <th class="px-3 py-2 <?php echo e($canMutate ? 'pr-10' : ''); ?>"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $file = $row['file'];
                            $inherited = $row['inherited'] ?? false;
                            $linkId = $row['link_id'] ?? null;
                            $note = $row['note'] ?? null;
                        ?>
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal align-top">
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <?php if($file->hasThumbnail()): ?>
                                        <img
                                            src="<?php echo e(route('files.thumbnail', $file)); ?>"
                                            alt=""
                                            class="h-10 w-10 object-cover border border-brand-silver dark:border-brand-charcoal shrink-0"
                                        >
                                    <?php elseif($file->isPreviewableImage() || $file->isPreviewablePdf()): ?>
                                        <span class="inline-flex h-10 w-10 items-center justify-center text-xs font-medium bg-brand-silver/50 dark:bg-brand-charcoal border border-brand-silver dark:border-brand-charcoal shrink-0">
                                            <?php echo e($file->isPreviewablePdf() ? 'PDF' : 'IMG'); ?>

                                        </span>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-medium"><?php echo e($file->title); ?></div>
                                        <?php if($inherited): ?>
                                            <div class="text-xs text-brand-charcoal dark:text-brand-silver">Via item type</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2"><?php echo e($file->category->label()); ?></td>
                            <td class="px-3 py-2">
                                <?php if($file->isUrl()): ?>
                                    URL
                                <?php else: ?>
                                    <?php echo e(strtoupper(pathinfo($file->original_filename ?? '', PATHINFO_EXTENSION) ?: 'file')); ?>

                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-brand-charcoal dark:text-brand-silver"><?php echo e($note ?: '—'); ?></td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <?php if($file->isPreviewable()): ?>
                                    <button
                                        type="button"
                                        class="text-brand text-sm"
                                        @click="openPreview(<?php echo \Illuminate\Support\Js::from(route('files.preview', $file))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(route('files.download', $file))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($file->isPreviewablePdf() ? 'pdf' : 'image')->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($file->title)->toHtml() ?>)"
                                    >Preview</button>
                                <?php endif; ?>
                                <?php if($file->isUrl()): ?>
                                    <a href="<?php echo e($file->external_url); ?>" target="_blank" rel="noopener" class="text-brand text-sm <?php echo e($file->isPreviewable() ? 'ms-2' : ''); ?>">Open</a>
                                <?php else: ?>
                                    <a href="<?php echo e(route('files.download', $file)); ?>" class="text-brand text-sm <?php echo e($file->isPreviewable() ? 'ms-2' : ''); ?>">Download</a>
                                <?php endif; ?>
                                <?php if($canMutate && ! $inherited && $linkId): ?>
                                    <form method="POST" action="<?php echo e(route('files.unlink', $linkId)); ?>" class="inline ms-2" onsubmit="return confirm('Unlink this file?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-sm text-red-600 dark:text-red-400">Unlink</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No files attached yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($canMutate): ?>
        <div
            x-show="composerOpen"
            x-cloak
            class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap gap-2 text-sm" role="tablist">
                    <button type="button" @click="tab = 'upload'" :class="tab === 'upload' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">Upload</button>
                    <button type="button" @click="tab = 'url'" :class="tab === 'url' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">URL</button>
                    <button type="button" @click="tab = 'library'" :class="tab === 'library' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">From library</button>
                </div>
                <button type="button" class="text-sm text-brand-charcoal dark:text-brand-silver" @click="composerOpen = false">Close</button>
            </div>

            <div x-show="tab === 'upload'">
                <?php if (isset($component)) { $__componentOriginal596b101141b3866ee004cecd5b647dc0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal596b101141b3866ee004cecd5b647dc0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.file-bulk-upload','data' => ['categories' => $categories,'linkableType' => $linkableType,'linkableId' => $linkableId,'idPrefix' => 'attach-'.$linkableType.'-'.$linkableId]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('file-bulk-upload'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['categories' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($categories),'linkable-type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($linkableType),'linkable-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($linkableId),'id-prefix' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('attach-'.$linkableType.'-'.$linkableId)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal596b101141b3866ee004cecd5b647dc0)): ?>
<?php $attributes = $__attributesOriginal596b101141b3866ee004cecd5b647dc0; ?>
<?php unset($__attributesOriginal596b101141b3866ee004cecd5b647dc0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal596b101141b3866ee004cecd5b647dc0)): ?>
<?php $component = $__componentOriginal596b101141b3866ee004cecd5b647dc0; ?>
<?php unset($__componentOriginal596b101141b3866ee004cecd5b647dc0); ?>
<?php endif; ?>
            </div>

            <form x-show="tab === 'url'" method="POST" action="<?php echo e(route('files.url')); ?>" class="space-y-3" style="display: none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="linkable_type" value="<?php echo e($linkableType); ?>">
                <input type="hidden" name="linkable_id" value="<?php echo e($linkableId); ?>">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'url_title','value' => 'Title']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'url_title','value' => 'Title']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'url_title','name' => 'title','class' => 'mt-1 block w-full','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'url_title','name' => 'title','class' => 'mt-1 block w-full','required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'url_category','value' => 'Category']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'url_category','value' => 'Category']); ?>
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
                        <select id="url_category" name="category" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'external_url','value' => 'URL']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'external_url','value' => 'URL']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'external_url','name' => 'external_url','type' => 'url','class' => 'mt-1 block w-full','required' => true,'placeholder' => 'https://']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'external_url','name' => 'external_url','type' => 'url','class' => 'mt-1 block w-full','required' => true,'placeholder' => 'https://']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'url_note','value' => 'Note (optional)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'url_note','value' => 'Note (optional)']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'url_note','name' => 'note','class' => 'mt-1 block w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'url_note','name' => 'note','class' => 'mt-1 block w-full']); ?>
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
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Attach URL <?php echo $__env->renderComponent(); ?>
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

            <form
                x-show="tab === 'library'"
                method="POST"
                :action="selectedId ? <?php echo \Illuminate\Support\Js::from(route('files.link', ['file' => 0]))->toHtml() ?>.replace(/\/0\/link$/, '/' + selectedId + '/link') : '#'"
                class="space-y-3"
                style="display: none;"
                @submit="if (!selectedId) { $event.preventDefault(); }"
            >
                <?php echo csrf_field(); ?>
                <input type="hidden" name="linkable_type" value="<?php echo e($linkableType); ?>">
                <input type="hidden" name="linkable_id" value="<?php echo e($linkableId); ?>">
                <div class="relative">
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'library_search','value' => 'Search library']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'library_search','value' => 'Search library']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'library_search','class' => 'mt-1 block w-full','xModel' => 'query','@input.debounce.300ms' => 'search()','placeholder' => 'Search by title…','autocomplete' => 'off']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'library_search','class' => 'mt-1 block w-full','x-model' => 'query','@input.debounce.300ms' => 'search()','placeholder' => 'Search by title…','autocomplete' => 'off']); ?>
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
                    <ul x-show="results.length" class="absolute z-10 mt-1 w-full border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate max-h-48 overflow-auto text-sm">
                        <template x-for="file in results" :key="file.id">
                            <li>
                                <button type="button" class="w-full text-left px-3 py-2 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal" @click="pick(file)">
                                    <span x-text="file.title"></span>
                                    <span class="text-brand-charcoal dark:text-brand-silver" x-text="' — ' + file.category"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <p x-show="selectedId" class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Selected: <span x-text="selectedTitle"></span></p>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'library_note','value' => 'Note (optional)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'library_note','value' => 'Note (optional)']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'library_note','name' => 'note','class' => 'mt-1 block w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'library_note','name' => 'note','class' => 'mt-1 block w-full']); ?>
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
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['xBind:disabled' => '!selectedId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-bind:disabled' => '!selectedId']); ?>Link file <?php echo $__env->renderComponent(); ?>
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
        </div>
    <?php endif; ?>

    <?php echo $__env->make('files.partials.preview-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/file-attachments.blade.php ENDPATH**/ ?>
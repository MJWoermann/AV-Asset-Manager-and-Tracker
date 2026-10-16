<?php
    $initialInterfaceTemplates = collect($interfaceTemplates ?? [])->map(fn ($row) => [
        'id' => $row['id'] ?? null,
        'name' => $row['name'] ?? '',
        'notes' => $row['notes'] ?? '',
        'connection_type_id' => $row['connection_type_id'] ?? null,
    ])->values()->all();
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
     <?php $__env->slot('header', null, []); ?> <h2 class="font-semibold text-xl"><?php echo e($itemType->exists ? 'Edit type' : 'New type'); ?></h2> <?php $__env->endSlot(); ?>
    <div class="max-w-3xl mx-auto px-4">
        <?php if(session('status')): ?>
            <p class="mb-3 text-sm text-brand"><?php echo e(session('status')); ?></p>
        <?php endif; ?>

        <form
            method="POST"
            action="<?php echo e($itemType->exists ? route('item-types.update', $itemType) : route('item-types.store')); ?>"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'asset_connections')): ?>
                x-data="interfaceTemplateForm(<?php echo \Illuminate\Support\Js::from($initialInterfaceTemplates)->toHtml() ?>)"
            <?php endif; ?>
        >
            <?php echo csrf_field(); ?>
            <?php if($itemType->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>
            <div>
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'name','value' => 'Name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'name','value' => 'Name']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'name','name' => 'name','class' => 'mt-1 block w-full','value' => old('name', $itemType->name),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'name','name' => 'name','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('name', $itemType->name)),'required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'description','value' => 'Description']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'description','value' => 'Description']); ?>
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
                <textarea id="description" name="description" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" rows="2"><?php echo e(old('description', $itemType->description)); ?></textarea>
            </div>

            <?php if (\Illuminate\Support\Facades\Blade::check('developerFeature', 'ru_visualiser')): ?>
                <label for="supports_ru_visualiser" class="flex items-start gap-2 text-sm">
                    <input type="hidden" name="supports_ru_visualiser" value="0">
                    <input
                        id="supports_ru_visualiser"
                        type="checkbox"
                        name="supports_ru_visualiser"
                        value="1"
                        class="rounded text-brand mt-0.5"
                        <?php if(old('supports_ru_visualiser', $itemType->supports_ru_visualiser)): echo 'checked'; endif; ?>
                    >
                    <span>
                        <span class="font-medium">Supports RU visualiser</span>
                        <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-0.5">
                            Enables a Rack tab on assets of this type for front/rear unit placements.
                        </span>
                    </span>
                </label>
            <?php endif; ?>

            <?php if (\Illuminate\Support\Facades\Blade::check('developerFeature', 'stock_consumables')): ?>
                <label for="supports_stock_tracking" class="flex items-start gap-2 text-sm">
                    <input type="hidden" name="supports_stock_tracking" value="0">
                    <input
                        id="supports_stock_tracking"
                        type="checkbox"
                        name="supports_stock_tracking"
                        value="1"
                        class="rounded text-brand mt-0.5"
                        <?php if(old('supports_stock_tracking', $itemType->supports_stock_tracking)): echo 'checked'; endif; ?>
                    >
                    <span>
                        <span class="font-medium">Supports stock tracking</span>
                        <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-0.5">
                            Enables a Stock tab with movements, reorder levels, and Kanban QR tags.
                        </span>
                    </span>
                </label>
            <?php endif; ?>

            <?php if (\Illuminate\Support\Facades\Blade::check('developerFeature', 'rigging')): ?>
                <div class="space-y-3 border border-brand-silver dark:border-brand-charcoal p-3">
                    <label for="supports_rigging_calculator" class="flex items-start gap-2 text-sm">
                        <input type="hidden" name="supports_rigging_calculator" value="0">
                        <input
                            id="supports_rigging_calculator"
                            type="checkbox"
                            name="supports_rigging_calculator"
                            value="1"
                            class="rounded text-brand mt-0.5"
                            <?php if(old('supports_rigging_calculator', $itemType->supports_rigging_calculator)): echo 'checked'; endif; ?>
                        >
                        <span>
                            <span class="font-medium">Supports Rigging calculator</span>
                            <span class="block text-xs text-brand-charcoal dark:text-brand-silver mt-0.5">
                                Makes this type available as a catalogue weight line in UDL and related calculators.
                            </span>
                        </span>
                    </label>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'rigging_weight_kg','value' => 'Catalogue weight (kg)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'rigging_weight_kg','value' => 'Catalogue weight (kg)']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'rigging_weight_kg','name' => 'rigging_weight_kg','type' => 'number','step' => '0.001','min' => '0','class' => 'mt-1 block w-full','value' => old('rigging_weight_kg', $itemType->rigging_weight_kg)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'rigging_weight_kg','name' => 'rigging_weight_kg','type' => 'number','step' => '0.001','min' => '0','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('rigging_weight_kg', $itemType->rigging_weight_kg))]); ?>
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
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                            Reference weight for calculators — not live asset weight. Leave blank if users must enter weight manually.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <fieldset>
                <legend class="text-sm font-semibold mb-2">Custom field sets</legend>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">
                    Manage sets on the
                    <a href="<?php echo e(route('custom-field-sets.index')); ?>" class="text-brand">Field sets</a>
                    page.
                </p>
                <?php $__currentLoopData = $fieldSets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $set): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-start gap-2 text-sm mb-2">
                        <input type="checkbox" name="field_sets[]" value="<?php echo e($set->id); ?>" class="rounded text-brand mt-0.5"
                            <?php if(collect(old('field_sets', $itemType->fieldSets->pluck('id')))->contains($set->id)): echo 'checked'; endif; ?>>
                        <span><?php echo e($set->name); ?> <span class="text-brand-charcoal dark:text-brand-silver">(<?php echo e($set->definitions->pluck('name')->join(', ')); ?>)</span></span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </fieldset>

            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'asset_connections')): ?>
                <?php if (isset($component)) { $__componentOriginalaec6336dbc81cb9b2a86288a1a1a2d16 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaec6336dbc81cb9b2a86288a1a1a2d16 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.interface-template-repeater','data' => ['connectionTypes' => $connectionTypes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('interface-template-repeater'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['connection-types' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($connectionTypes)]); ?>
                    New assets of this type receive these interfaces. Editing templates does not rewrite existing assets — use Sync missing below.
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaec6336dbc81cb9b2a86288a1a1a2d16)): ?>
<?php $attributes = $__attributesOriginalaec6336dbc81cb9b2a86288a1a1a2d16; ?>
<?php unset($__attributesOriginalaec6336dbc81cb9b2a86288a1a1a2d16); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaec6336dbc81cb9b2a86288a1a1a2d16)): ?>
<?php $component = $__componentOriginalaec6336dbc81cb9b2a86288a1a1a2d16; ?>
<?php unset($__componentOriginalaec6336dbc81cb9b2a86288a1a1a2d16); ?>
<?php endif; ?>
            <?php endif; ?>

            <div class="flex flex-wrap items-center gap-3">
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Save <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                <a href="<?php echo e(route('item-types.index')); ?>" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
            </div>
        </form>

        <?php if($itemType->exists): ?>
            <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'asset_connections')): ?>
                <div class="mt-8 space-y-3 border p-4 bg-white dark:bg-brand-slate">
                    <h3 class="font-semibold text-lg">Sync missing interfaces</h3>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Copy any template interfaces that existing assets of this type do not already have (by template or name).
                    </p>
                    <form method="POST" action="<?php echo e(route('item-types.sync-interfaces', $itemType)); ?>">
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
<?php $component->withAttributes(['type' => 'submit']); ?>Sync missing templates <?php echo $__env->renderComponent(); ?>
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
                </div>
            <?php endif; ?>

            <div class="mt-8 space-y-3">
                <h3 class="font-semibold text-lg">Files</h3>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Files linked here appear on every asset of this type.
                </p>
                <?php if (isset($component)) { $__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.file-attachments','data' => ['linkableType' => 'item_type','linkableId' => $itemType->id,'rows' => $fileRows,'canMutate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('file-attachments'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['linkable-type' => 'item_type','linkable-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($itemType->id),'rows' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fileRows),'can-mutate' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056)): ?>
<?php $attributes = $__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056; ?>
<?php unset($__attributesOriginalc0cb64c672269fbcbe6ad9732ca7c056); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056)): ?>
<?php $component = $__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056; ?>
<?php unset($__componentOriginalc0cb64c672269fbcbe6ad9732ca7c056); ?>
<?php endif; ?>
            </div>

            <?php if($itemType->aliases->isNotEmpty()): ?>
                <div class="rounded border border-gray-200 dark:border-brand-charcoal p-3 space-y-1">
                    <p class="text-sm font-medium">Former names (import aliases)</p>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Spreadsheet rows using these names will use this item type instead of creating a new one.
                    </p>
                    <ul class="text-sm list-disc list-inside">
                        <?php $__currentLoopData = $itemType->aliases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alias): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($alias->former_name); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mt-6 space-y-2">
                <?php if($replacementTypes->isEmpty()): ?>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Create another item type before deleting this one. Assets need a replacement type.
                    </p>
                <?php else: ?>
                    <?php if (isset($component)) { $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.danger-button','data' => ['type' => 'button','xData' => '','xOn:click.prevent' => '$dispatch(\'open-modal\', \'confirm-item-type-deletion\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('danger-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','x-data' => '','x-on:click.prevent' => '$dispatch(\'open-modal\', \'confirm-item-type-deletion\')']); ?>
                        Delete item type
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $attributes = $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $component = $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
<?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'confirm-item-type-deletion','show' => $errors->has('replacement_item_type_id'),'focusable' => true,'maxWidth' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'confirm-item-type-deletion','show' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->has('replacement_item_type_id')),'focusable' => true,'maxWidth' => 'md']); ?>
                <form method="POST" action="<?php echo e(route('item-types.destroy', $itemType)); ?>" class="p-6 space-y-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “<?php echo e($itemType->name); ?>”?
                    </h2>

                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        <?php if($assetCount > 0): ?>
                            This type is used by <?php echo e($assetCount); ?> <?php echo e($assetCount === 1 ? 'asset' : 'assets'); ?>.
                            Choose a replacement item type for those assets, then delete.
                        <?php else: ?>
                            No assets use this type. Choose a replacement item type, then delete.
                        <?php endif; ?>
                        The deleted name is kept so future imports that still use it map to the replacement.
                    </p>

                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'replacement_item_type_id','value' => 'Replacement item type']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'replacement_item_type_id','value' => 'Replacement item type']); ?>
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
                            id="replacement_item_type_id"
                            name="replacement_item_type_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            required
                        >
                            <option value="">Select a type…</option>
                            <?php $__currentLoopData = $replacementTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($type->id); ?>" <?php if((string) old('replacement_item_type_id') === (string) $type->id): echo 'selected'; endif; ?>>
                                    <?php echo e($type->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('replacement_item_type_id'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('replacement_item_type_id')),'class' => 'mt-2']); ?>
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

                    <div class="flex justify-end gap-2">
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
                        <?php if (isset($component)) { $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.danger-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('danger-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                            Delete permanently
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $attributes = $__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__attributesOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11)): ?>
<?php $component = $__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11; ?>
<?php unset($__componentOriginal656e8c5ea4d9a4fa173298297bfe3f11); ?>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/item-types/form.blade.php ENDPATH**/ ?>
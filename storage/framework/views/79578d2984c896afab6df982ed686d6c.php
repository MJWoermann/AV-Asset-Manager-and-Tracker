<?php
    $initialSelections = $suggestedMappings ?? [];
    $requiredFields = [
        ['key' => 'name', 'label' => 'Map a column to Name'],
        ['key' => 'item_type', 'label' => 'Map a column to Item Type'],
    ];
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
     <?php $__env->slot('header', null, []); ?> <h2 class="font-semibold text-xl">Map columns</h2> <?php $__env->endSlot(); ?>
    <div class="max-w-2xl mx-auto px-4">
        <form
            method="POST"
            action="<?php echo e(route('import.prepare')); ?>"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
            x-data="importMapper(<?php echo \Illuminate\Support\Js::from(array_values($columns))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($initialSelections)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($requiredFields)->toHtml() ?>)"
            @submit="onSubmit($event)"
        >
            <?php echo csrf_field(); ?>
            <p class="text-sm text-gray-600 dark:text-brand-silver">
                Map each source column to an asset field. Item type and location (level / room / rack) are set here —
                missing types and locations are created automatically. Each target field may only be used once.
                Name and Item Type are required before you can continue.
            </p>

            <?php if(! empty($usedRememberedMappings)): ?>
                <p class="text-sm text-brand dark:text-brand">
                    Suggestions include mappings from your previous import where column names match.
                </p>
            <?php endif; ?>

            <div
                x-show="!canSubmit"
                x-cloak
                class="text-sm text-red-700 dark:text-red-400 border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-3 py-2"
            >
                <p class="font-medium">Finish these requirements before reviewing duplicates:</p>
                <ul class="mt-2 list-disc list-inside space-y-1">
                    <template x-for="requirement in missingRequirements" :key="requirement">
                        <li x-text="requirement"></li>
                    </template>
                    <li x-show="duplicateWarning" x-text="duplicateWarning"></li>
                </ul>
            </div>

            <?php if($errors->has('mapping')): ?>
                <div class="text-sm text-red-700 dark:text-red-400 border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-3 py-2">
                    <p class="font-medium">Finish these requirements before reviewing duplicates:</p>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        <?php $__currentLoopData = $errors->get('mapping'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($message); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center text-sm">
                    <div class="font-mono break-all"><?php echo e($column); ?></div>
                    <div>
                        <select
                            name="mapping[<?php echo e($column); ?>]"
                            class="w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            x-model="selections[<?php echo e(json_encode($column)); ?>]"
                            @change="onChange(<?php echo e(json_encode($column)); ?>)"
                        >
                            <option value="">Ignore</option>
                            <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option
                                    value="<?php echo e($field); ?>"
                                    :disabled="isTaken(<?php echo \Illuminate\Support\Js::from($field)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($column)->toHtml() ?>)"
                                ><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($createNewField); ?>">Create new field (Imported-Field:<?php echo e($column); ?>)</option>
                        </select>
                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('mapping.'.$column),'class' => 'mt-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('mapping.'.$column)),'class' => 'mt-1']); ?>
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
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-brand-black dark:bg-brand border border-transparent font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand disabled:opacity-50 disabled:cursor-not-allowed"
                    x-bind:disabled="!canSubmit"
                >
                    Review duplicates
                </button>
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
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/import/map.blade.php ENDPATH**/ ?>
<?php if(session('status')): ?>
    <p class="text-sm text-brand"><?php echo e(session('status')); ?></p>
<?php endif; ?>

<?php $__empty_1 = true; $__currentLoopData = $layouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $layout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-center">
        <div class="min-w-0">
            <div class="font-medium"><?php echo e($layout->name); ?></div>
            <div class="text-xs text-brand-charcoal dark:text-brand-silver">
                <?php echo e($layout->paper_preset); ?>

                · <?php echo e(rtrim(rtrim(number_format($layout->width_mm, 2, '.', ''), '0'), '.')); ?> × <?php echo e(rtrim(rtrim(number_format($layout->height_mm, 2, '.', ''), '0'), '.')); ?> mm
                · <?php echo e(count($layout->elements ?? [])); ?> elements
            </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <a href="<?php echo e(route('shelf-tags.layouts.edit', $layout)); ?>" class="text-brand">Edit</a>
            <form method="POST" action="<?php echo e(route('shelf-tags.layouts.destroy', $layout)); ?>" onsubmit="return confirm('Delete this layout?')">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="text-red-600">Delete</button>
            </form>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="text-sm text-brand-charcoal dark:text-brand-silver">No shelf tag layouts yet. Create one to start printing.</p>
<?php endif; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/shelf-tags/layouts/partials/list.blade.php ENDPATH**/ ?>
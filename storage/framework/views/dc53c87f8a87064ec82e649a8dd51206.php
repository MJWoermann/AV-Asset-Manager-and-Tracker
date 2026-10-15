<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Shelf tags</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; color: #000; }
        .label {
            position: relative;
            width: <?php echo e($layout->width_mm); ?>mm;
            height: <?php echo e($layout->height_mm); ?>mm;
            page-break-after: always;
            overflow: hidden;
        }
        .label:last-child { page-break-after: auto; }
        .el { position: absolute; overflow: hidden; }
        .el img { width: 100%; height: 100%; object-fit: contain; }
    </style>
</head>
<body>
<?php $__currentLoopData = $labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="label">
        <?php $__currentLoopData = $label['elements']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo $__env->make('shelf-tags.print.partials.element', ['element' => $element, 'logoUrl' => null, 'forPdf' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</body>
</html>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/shelf-tags/print/pdf.blade.php ENDPATH**/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Comparison report — <?php echo e($source->name); ?> vs <?php echo e($target->name); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #444; margin-bottom: 18px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #00adb7; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background: #f3f3f3; font-weight: bold; }
        .child { padding-left: 18px; }
        .empty { color: #666; font-style: italic; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Comparison report</h1>
    <p class="meta">
        Scanned / event: <strong><?php echo e($source->name); ?></strong><br>
        Inventory / source: <strong><?php echo e($target->name); ?></strong><br>
        Generated: <?php echo e(now()->timezone(config('app.timezone'))->format('Y-m-d H:i')); ?>

    </p>

    <?php $__currentLoopData = [
        'matched' => 'Matched',
        'inventory_only' => 'Inventory only',
        'scanned_only' => 'Scanned only (not on inventory)',
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <h2><?php echo e($title); ?> (<?php echo e($result[$key]->count()); ?>)</h2>
        <?php if($result[$key]->isEmpty()): ?>
            <p class="empty">None</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>FMI AST#</th>
                        <th>TP Barcode</th>
                        <?php if($key === 'matched'): ?>
                            <th>Inv qty</th>
                            <th>Scan qty</th>
                        <?php else: ?>
                            <th>Qty</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $result[$key]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="<?php echo e(! empty($row->parent_id) ? 'child' : ''); ?>"><?php echo e($row->name); ?></td>
                            <td class="mono"><?php echo e($row->fmi_ast ?? ''); ?></td>
                            <td class="mono"><?php echo e($row->tp_barcode ?? ''); ?></td>
                            <?php if($key === 'matched'): ?>
                                <td><?php echo e($row->inventory_qty ?? ''); ?></td>
                                <td><?php echo e($row->scanned_qty ?? ''); ?></td>
                            <?php else: ?>
                                <td><?php echo e($row->quantity ?? ''); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</body>
</html>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/reports/compare-pdf.blade.php ENDPATH**/ ?>
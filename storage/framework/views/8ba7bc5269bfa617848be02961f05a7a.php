<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout holdings</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 4px; }
        .meta { color: #444; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background: #f3f3f3; font-weight: bold; }
        .empty { color: #666; font-style: italic; }
        .detail { color: #444; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Checkout holdings</h1>
    <p class="meta">
        <?php echo e($filters['view'] === 'holders' ? 'By holder' : 'By model'); ?><br>
        Generated: <?php echo e(now()->timezone(config('app.timezone'))->format('Y-m-d H:i')); ?>

    </p>

    <?php if($filters['view'] === 'models'): ?>
        <?php if($modelRows === []): ?>
            <p class="empty">No assets match these filters.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Manufacturer</th>
                        <th>Model</th>
                        <th>Available</th>
                        <th>Checked out</th>
                        <th>In register</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $modelRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($row['manufacturer']); ?></td>
                            <td><?php echo e($row['model']); ?></td>
                            <td><?php echo e($row['available']); ?></td>
                            <td><?php echo e($row['checked_out']); ?></td>
                            <td><?php echo e($row['in_register']); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <?php $__empty_1 = true; $__currentLoopData = $holderGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <h2>
                <?php echo e($group['label']); ?>

                <span class="detail">
                    <?php if($group['detail'] !== ''): ?>
                        — <?php echo e($group['detail']); ?>

                    <?php endif; ?>
                    (<?php echo e($group['checkouts']->count()); ?>)
                </span>
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Identifiers</th>
                        <th>Model</th>
                        <th>Purpose</th>
                        <th>Checked out</th>
                        <th>Expected return</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $group['checkouts']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $checkout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $asset = $checkout->asset;
                            $identifiers = collect([
                                $asset?->serial_number,
                                $asset?->tp_barcode,
                                $asset?->fmi_ast,
                                $asset?->rig_tag,
                            ])->filter()->implode(' · ');
                        ?>
                        <tr>
                            <td><?php echo e($asset?->name ?? '—'); ?></td>
                            <td><?php echo e($identifiers !== '' ? $identifiers : '—'); ?></td>
                            <td><?php echo e(trim(($asset?->manufacturer?->name ?? '').' '.($asset?->manufacturerModel?->name ?? '')) ?: '—'); ?></td>
                            <td><?php echo e($checkout->purposeLabel()); ?></td>
                            <td><?php echo e($checkout->created_at?->format('Y-m-d H:i') ?? '—'); ?></td>
                            <td><?php echo e($checkout->expected_return_at?->format('Y-m-d H:i') ?? '—'); ?></td>
                            <td><?php echo e($asset?->location?->name ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="empty">No checkouts match these filters.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/reports/checkouts-pdf.blade.php ENDPATH**/ ?>
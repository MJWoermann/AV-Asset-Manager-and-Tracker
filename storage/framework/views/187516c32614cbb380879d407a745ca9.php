<?php
    /** @var array<string, mixed> $element */
    $type = $element['type'] ?? 'text';
    $props = is_array($element['props'] ?? null) ? $element['props'] : [];
    $value = (string) ($element['value'] ?? '');
    $style = sprintf(
        'left:%smm;top:%smm;width:%smm;height:%smm;z-index:%d;transform:rotate(%sdeg);',
        $element['x'] ?? 0,
        $element['y'] ?? 0,
        $element['width'] ?? 10,
        $element['height'] ?? 10,
        (int) ($element['z'] ?? 0),
        $element['rotation'] ?? 0,
    );
    $fontSize = (float) ($props['fontSize'] ?? 10);
    $align = (string) ($props['align'] ?? 'left');
    $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
    $weight = ($props['fontWeight'] ?? 'normal') === 'bold' ? 'bold' : 'normal';
    $color = (string) ($props['color'] ?? '#000000');
    $color = in_array($color, ['#000000', '#444444', '#888888'], true) ? $color : '#000000';
?>

<div class="absolute overflow-hidden" style="<?php echo e($style); ?>">
    <?php if(in_array($type, ['text', 'field'], true)): ?>
        <div style="font-size: <?php echo e($fontSize); ?>pt; font-weight: <?php echo e($weight); ?>; text-align: <?php echo e($align); ?>; color: <?php echo e($color); ?>; line-height: 1.1; width: 100%; height: 100%;">
            <?php echo e($value); ?>

        </div>
    <?php elseif($type === 'barcode'): ?>
        <?php if(!empty($forPdf) && !empty($element['image_data_uri'])): ?>
            <img src="<?php echo e($element['image_data_uri']); ?>" alt="" style="width:100%;height:100%;object-fit:contain;object-position:center;">
        <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                <svg
                    class="shelf-tag-barcode"
                    style="width:100%;height:100%;"
                    data-value="<?php echo e($value); ?>"
                    data-symbology="<?php echo e($props['symbology'] ?? 'code128'); ?>"
                    data-show-text="<?php echo e(!empty($props['showText']) ? '1' : '0'); ?>"
                ></svg>
            </div>
        <?php endif; ?>
    <?php elseif($type === 'qr'): ?>
        <?php if(!empty($forPdf) && !empty($element['image_data_uri'])): ?>
            <img src="<?php echo e($element['image_data_uri']); ?>" alt="" style="width:100%;height:100%;object-fit:contain;">
        <?php else: ?>
            <canvas
                class="w-full h-full shelf-tag-qr"
                data-value="<?php echo e($value); ?>"
            ></canvas>
        <?php endif; ?>
    <?php elseif($type === 'logo'): ?>
        <?php
            $src = !empty($forPdf) ? ($element['image_data_uri'] ?? null) : ($logoUrl ?? null);
        ?>
        <?php if($src): ?>
            <img src="<?php echo e($src); ?>" alt="" style="width:100%;height:100%;object-fit:contain;">
        <?php endif; ?>
    <?php elseif($type === 'rect'): ?>
        <div style="width:100%;height:100%;border:1px solid <?php echo e($props['stroke'] ?? '#000000'); ?>;background:<?php echo e($props['fill'] ?? 'transparent'); ?>;"></div>
    <?php elseif($type === 'line'): ?>
        <div style="width:100%;border-top:2px solid <?php echo e($props['stroke'] ?? '#000000'); ?>;margin-top:<?php echo e(max(0, (($element['height'] ?? 1) / 2) - 0.5)); ?>mm;"></div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/shelf-tags/print/partials/element.blade.php ENDPATH**/ ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['icon' => 'generic']));

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

foreach (array_filter((['icon' => 'generic']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $key = in_array($icon, \App\Support\ConnectionTypeIcons::keys(), true) ? $icon : 'generic';
?>

<?php switch($key):
    case ('network'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5.5 4.5h13v15h-13z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.25 8.25h7.5v5.5c0 1.1-.9 2-2 2h-3.5c-1.1 0-2-.9-2-2v-5.5z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.4 8.5v2.5M11.15 8.5v2.5M12.85 8.5v2.5M14.6 8.5v2.5"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.5 15.75h3"/>
        </svg>
        <?php break; ?>
    <?php case ('wireless'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.5 9.75a10.5 10.5 0 0115 0M7.25 12.75a6.5 6.5 0 019.5 0M10 15.75a2.75 2.75 0 014 0M12 18.25h.01"/>
        </svg>
        <?php break; ?>
    <?php case ('hdmi'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 8.5h16v7H4zM8 15.5v2M16 15.5v2M7 11.5h2M11 11.5h2M15 11.5h2"/>
        </svg>
        <?php break; ?>
    <?php case ('sdi'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <circle cx="12" cy="12" r="7.25" stroke-width="1.75"/>
            <circle cx="12" cy="12" r="2.5" stroke-width="1.75"/>
        </svg>
        <?php break; ?>
    <?php case ('dante'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.5 12h3l2-4 3 8 2-4h5"/>
        </svg>
        <?php break; ?>
    <?php case ('custom'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.5v3M12 16.5v3M4.5 12h3M16.5 12h3M7 7l2.1 2.1M14.9 14.9 17 17M17 7l-2.1 2.1M9.1 14.9 7 17"/>
        </svg>
        <?php break; ?>
    <?php case ('analog'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 14c2-4 4-4 6 0s4 4 6 0 4-4 6 0"/>
        </svg>
        <?php break; ?>
    <?php case ('xlr'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <circle cx="12" cy="12" r="8" stroke-width="1.75"/>
            <circle cx="12" cy="8.25" r="1.35" stroke-width="1.5"/>
            <circle cx="8.85" cy="13.75" r="1.35" stroke-width="1.5"/>
            <circle cx="15.15" cy="13.75" r="1.35" stroke-width="1.5"/>
        </svg>
        <?php break; ?>
    <?php case ('dmx'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <circle cx="12" cy="12" r="8" stroke-width="1.75"/>
            <circle cx="12" cy="7.5" r="1.15" stroke-width="1.5"/>
            <circle cx="8.2" cy="10.4" r="1.15" stroke-width="1.5"/>
            <circle cx="15.8" cy="10.4" r="1.15" stroke-width="1.5"/>
            <circle cx="9.4" cy="15.4" r="1.15" stroke-width="1.5"/>
            <circle cx="14.6" cy="15.4" r="1.15" stroke-width="1.5"/>
        </svg>
        <?php break; ?>
    <?php case ('power'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3.5v8"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7.35 6.9a6.5 6.5 0 109.3 0"/>
        </svg>
        <?php break; ?>
    <?php case ('usb'): ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 21V10.5M12 10.5 7.5 7M12 10.5l4.5-3.5M12 10.5V5"/>
            <circle cx="7.5" cy="6.25" r="1.6" stroke-width="1.5"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.4 4.15h3.4v3.4"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.6 3.4 12 2l1.4 1.4"/>
        </svg>
        <?php break; ?>
    <?php default: ?>
        <svg <?php echo e($attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24'])); ?> aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 12h8M12 8v8M5 5h14v14H5z"/>
        </svg>
<?php endswitch; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/connection-type-icon.blade.php ENDPATH**/ ?>
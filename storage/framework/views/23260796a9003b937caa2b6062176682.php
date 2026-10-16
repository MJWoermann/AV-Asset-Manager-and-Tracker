<?php
    $alternate = (bool) ($alternate ?? false);
    $hasChildren = $location->children->isNotEmpty();
    $hasRackChildren = $hasChildren && $location->children->contains(
        fn ($child): bool => $child->type === \App\Enums\LocationType::Rack
    );

    $rowClasses = match ($location->type) {
        \App\Enums\LocationType::Site => $alternate
            ? 'bg-[#dcdcdc] dark:bg-[#3d4043] border-l-[3px] border-l-brand-charcoal'
            : 'bg-brand-silver dark:bg-brand-slate border-l-[3px] border-l-brand-charcoal',
        \App\Enums\LocationType::Level => $alternate
            ? 'bg-[#cfdce6] dark:bg-[#3a4650] border-l-[3px] border-l-brand-grey-blue'
            : 'bg-[#e4edf3] dark:bg-[#313940] border-l-[3px] border-l-brand-grey-blue',
        \App\Enums\LocationType::Room => $alternate
            ? 'bg-[#e8e8e8] dark:bg-[#35383b] border-l-[3px] border-l-brand-silver dark:border-l-brand-charcoal'
            : 'bg-white dark:bg-[#2f3235] border-l-[3px] border-l-brand-silver dark:border-l-brand-charcoal',
        \App\Enums\LocationType::Rack => $alternate
            ? 'bg-[#cce8eb] dark:bg-[#33494c] border-l-[3px] border-l-brand'
            : 'bg-[#e5f5f6] dark:bg-[#2b3c3e] border-l-[3px] border-l-brand',
    };
?>
<li
    class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0"
    data-location-id="<?php echo e($location->id); ?>"
    <?php if($hasChildren): ?>
        data-location-collapsible="1"
    <?php endif; ?>
    <?php if($hasRackChildren): ?>
        data-location-has-racks="1"
    <?php endif; ?>
>
    <div class="<?php echo e($rowClasses); ?> px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-start gap-3 min-w-0">
            <?php if($hasChildren): ?>
                <button
                    type="button"
                    class="mt-0.5 shrink-0 p-0.5 text-brand-charcoal dark:text-brand-silver"
                    @click="toggle(<?php echo e($location->id); ?>)"
                    :aria-expanded="isOpen(<?php echo e($location->id); ?>) ? 'true' : 'false'"
                    aria-label="Toggle <?php echo e($location->name); ?> children"
                >
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': isOpen(<?php echo e($location->id); ?>) }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            <?php else: ?>
                <span class="mt-0.5 inline-block w-5 shrink-0" aria-hidden="true"></span>
            <?php endif; ?>
            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                <input
                    type="checkbox"
                    class="mt-1 rounded border-brand-charcoal/40 shrink-0"
                    data-bulk-location-id="<?php echo e($location->id); ?>"
                    data-bulk-location-name="<?php echo e(mb_strtolower(trim($location->name))); ?>"
                    :checked="!!selected[<?php echo e($location->id); ?>]"
                    @change="setSelected(<?php echo e($location->id); ?>, $event.target.checked)"
                    aria-label="Select <?php echo e($location->name); ?>"
                >
            <?php endif; ?>
            <div>
                <span class="font-medium"><?php echo e($location->name); ?></span>
                <?php if($location->room_code): ?>
                    <span class="text-brand-charcoal dark:text-brand-silver"> (<?php echo e($location->room_code); ?>)</span>
                <?php endif; ?>
                <span class="text-brand-charcoal dark:text-brand-silver"> — <?php echo e($location->type->label()); ?><?php if($location->is_portable): ?> (portable)<?php endif; ?></span>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a
                href="<?php echo e(route('assets.index', ['location_id' => $location->id])); ?>"
                class="text-sm text-brand"
            >Assets</a>
            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'admin|inventory_manager')): ?>
                <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                <a href="<?php echo e(route('locations.edit', $location)); ?>" class="text-sm text-brand">Edit</a>
                <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                <form method="POST" action="<?php echo e(route('locations.destroy', $location)); ?>" class="inline" onsubmit="return confirm('Delete this location? Child locations become top-level and assets here lose their location.')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="text-sm text-red-700">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php if($hasChildren): ?>
        <ul
            class="ml-4 border-l border-brand-charcoal/30 dark:border-brand-charcoal"
            x-show="isOpen(<?php echo e($location->id); ?>)"
        >
            <?php
                $previousType = null;
                $sameTypeRun = 0;
            ?>
            <?php $__currentLoopData = $location->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    if ($previousType === $child->type) {
                        $sameTypeRun++;
                    } else {
                        $previousType = $child->type;
                        $sameTypeRun = 0;
                    }
                ?>
                <?php echo $__env->make('locations.partials.node', [
                    'location' => $child,
                    'alternate' => $sameTypeRun % 2 === 1,
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    <?php endif; ?>
</li>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/locations/partials/node.blade.php ENDPATH**/ ?>
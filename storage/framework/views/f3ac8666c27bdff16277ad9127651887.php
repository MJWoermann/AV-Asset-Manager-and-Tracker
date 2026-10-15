<?php
    /** @var bool $interactive */
    $interactive = $interactive ?? true;
    /** @var bool $compact */
    $compact = $compact ?? false;
?>


<div
    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal overflow-hidden w-full',
        'max-w-[16rem] ml-auto' => $compact,
    ]); ?>"
>
    <template x-for="row in rows" :key="(face || 'front') + '-' + row.ru">
        <?php if($interactive): ?>
            <button
                type="button"
                class="w-full flex border-b border-brand-silver/60 dark:border-brand-charcoal text-left aspect-[19/1.75]"
                :class="row.occupied ? 'bg-brand/10 hover:bg-brand/15' : 'hover:bg-brand/5'"
                @click="row.placement ? openEdit(row.placement) : openAdd(row.ru)"
            >
                <div
                    class="w-10 shrink-0 flex items-center justify-center text-[0.65rem] font-mono text-brand-charcoal dark:text-brand-silver border-r border-brand-silver dark:border-brand-charcoal"
                    :class="row.occupied ? 'bg-brand-silver/40 dark:bg-brand-charcoal/50' : ''"
                >
                    <span x-text="row.ru"></span>
                </div>
                <div class="flex-1 px-2 min-w-0 flex items-center overflow-hidden">
                    <template x-if="row.isStart">
                        <div class="min-w-0 leading-tight">
                            <div class="text-xs font-medium truncate" x-text="placementLabel(row.placement)"></div>
                            <div class="text-[0.65rem] text-brand-charcoal dark:text-brand-silver truncate">
                                <span x-text="row.placement.start_ru"></span>–<span x-text="Number(row.placement.start_ru) + Number(row.placement.ru_height) - 1"></span>
                                RU
                                ·
                                <span x-text="row.placement.kind === 'note' ? 'Note' : 'Asset'"></span>
                                <span x-show="row.placement.full_depth" x-cloak> · Full depth</span>
                                <span x-show="row.mirrored" x-cloak> · Rear</span>
                            </div>
                        </div>
                    </template>
                    <template x-if="row.occupied && !row.isStart">
                        <span class="text-[0.65rem] text-brand-charcoal/50 dark:text-brand-silver/50">┊</span>
                    </template>
                    <template x-if="!row.occupied">
                        <span class="text-[0.65rem] text-brand-charcoal/60 dark:text-brand-silver/60">Empty</span>
                    </template>
                </div>
            </button>
        <?php else: ?>
            <div
                class="w-full flex border-b border-brand-silver/60 dark:border-brand-charcoal aspect-[19/1.75]"
                :class="row.occupied ? 'bg-brand/10' : ''"
            >
                <div
                    class="w-8 shrink-0 flex items-center justify-center text-[0.6rem] font-mono text-brand-charcoal dark:text-brand-silver border-r border-brand-silver dark:border-brand-charcoal"
                    :class="row.occupied ? 'bg-brand-silver/40 dark:bg-brand-charcoal/50' : ''"
                >
                    <span x-text="row.ru"></span>
                </div>
                <div class="flex-1 px-1.5 min-w-0 flex items-center overflow-hidden">
                    <template x-if="row.isStart">
                        <div class="min-w-0 leading-tight">
                            <div class="text-[0.65rem] font-medium truncate" x-text="placementLabel(row.placement)"></div>
                            <div class="text-[0.55rem] text-brand-charcoal dark:text-brand-silver truncate">
                                <span x-text="row.placement.start_ru"></span>–<span x-text="Number(row.placement.start_ru) + Number(row.placement.ru_height) - 1"></span>
                                RU
                                <span x-show="row.placement.full_depth" x-cloak> · FD</span>
                            </div>
                        </div>
                    </template>
                    <template x-if="row.occupied && !row.isStart">
                        <span class="text-[0.55rem] text-brand-charcoal/50 dark:text-brand-silver/50">┊</span>
                    </template>
                </div>
            </div>
        <?php endif; ?>
    </template>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/partials/ru-diagram.blade.php ENDPATH**/ ?>
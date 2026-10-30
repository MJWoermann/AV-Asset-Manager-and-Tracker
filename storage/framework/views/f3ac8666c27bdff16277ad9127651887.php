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
        <div
            class="w-full flex border-b border-brand-silver/60 dark:border-brand-charcoal"
            class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'min-h-[2rem]' => $interactive,
                'min-h-[1.5rem]' => $compact,
            ]); ?>"
        >
            <div
                class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'shrink-0 flex items-center justify-center font-mono text-brand-charcoal dark:text-brand-silver border-r border-brand-silver dark:border-brand-charcoal',
                    'w-12 text-xs' => $interactive,
                    'w-8 text-[0.6rem]' => $compact,
                ]); ?>"
            >
                <span x-text="row.ru"></span>
            </div>
            <div
                class="flex-1 grid min-w-0"
                :style="'grid-template-columns: repeat(' + row.columns + ', minmax(0, 1fr))'"
            >
                <template x-for="cell in row.slots" :key="row.ru + '-' + cell.slot">
                    <?php if($interactive): ?>
                        <button
                            type="button"
                            class="min-w-0 px-2 py-1.5 text-left"
                            :class="[
                                cell.occupied ? 'bg-brand/10 hover:bg-brand/15' : 'hover:bg-brand/5',
                                cell.slot > 1 ? 'border-l border-brand-silver/60 dark:border-brand-charcoal' : '',
                            ]"
                            @click="cell.placement ? openEdit(cell.placement) : openAdd(row.ru, cell.width || 'full', cell.slot)"
                        >
                            <template x-if="cell.isStart">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium truncate" x-text="placementLabel(cell.placement)"></div>
                                    <div class="text-xs text-brand-charcoal dark:text-brand-silver truncate">
                                        <span x-text="cell.placement.start_ru"></span>–<span x-text="Number(cell.placement.start_ru) + Number(cell.placement.ru_height) - 1"></span>
                                        RU
                                        ·
                                        <span x-text="cell.placement.kind === 'note' ? 'Note' : 'Asset'"></span>
                                        <span x-show="cell.placement.width && cell.placement.width !== 'full'" x-cloak>
                                            · <span x-text="slotLabel(cell.placement.width, cell.slot)"></span>
                                        </span>
                                        <span x-show="cell.placement.full_depth" x-cloak> · Full depth</span>
                                        <span x-show="cell.mirrored" x-cloak> · Rear</span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="cell.occupied && !cell.isStart">
                                <span class="text-xs text-brand-charcoal/50 dark:text-brand-silver/50">┊</span>
                            </template>
                            <template x-if="!cell.occupied">
                                <span class="text-xs text-brand-charcoal/60 dark:text-brand-silver/60">Empty</span>
                            </template>
                        </button>
                    <?php else: ?>
                        <div
                            class="min-w-0 px-1.5 py-0.5 flex items-center overflow-hidden"
                            :class="[
                                cell.occupied ? 'bg-brand/10' : '',
                                cell.slot > 1 ? 'border-l border-brand-silver/60 dark:border-brand-charcoal' : '',
                            ]"
                        >
                            <template x-if="cell.isStart">
                                <div class="min-w-0 leading-tight">
                                    <div class="text-[0.65rem] font-medium truncate" x-text="placementLabel(cell.placement)"></div>
                                    <div class="text-[0.55rem] text-brand-charcoal dark:text-brand-silver truncate">
                                        <span x-text="cell.placement.start_ru"></span>–<span x-text="Number(cell.placement.start_ru) + Number(cell.placement.ru_height) - 1"></span>
                                        RU
                                        <span x-show="cell.placement.width && cell.placement.width !== 'full'" x-cloak>
                                            · <span x-text="slotLabel(cell.placement.width, cell.slot)"></span>
                                        </span>
                                        <span x-show="cell.placement.full_depth" x-cloak> · FD</span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="cell.occupied && !cell.isStart">
                                <span class="text-[0.55rem] text-brand-charcoal/50 dark:text-brand-silver/50">┊</span>
                            </template>
                        </div>
                    <?php endif; ?>
                </template>
            </div>
        </div>
    </template>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/assets/partials/ru-diagram.blade.php ENDPATH**/ ?>
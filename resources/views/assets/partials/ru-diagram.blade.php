@php
    /** @var bool $interactive */
    $interactive = $interactive ?? true;
    /** @var bool $compact */
    $compact = $compact ?? false;
@endphp

<div
    @class([
        'bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal overflow-hidden w-full',
        'max-w-[16rem] ml-auto' => $compact,
    ])
>
    <template x-for="row in rows" :key="(face || 'front') + '-' + row.ru">
        @if($interactive)
            <button
                type="button"
                class="w-full flex min-h-[2rem] border-b border-brand-silver/60 dark:border-brand-charcoal text-left"
                :class="row.occupied ? 'bg-brand/10 hover:bg-brand/15' : 'hover:bg-brand/5'"
                @click="row.placement ? openEdit(row.placement) : openAdd(row.ru)"
            >
                <div
                    class="w-12 shrink-0 flex items-center justify-center text-xs font-mono text-brand-charcoal dark:text-brand-silver border-r border-brand-silver dark:border-brand-charcoal"
                    :class="row.occupied ? 'bg-brand-silver/40 dark:bg-brand-charcoal/50' : ''"
                >
                    <span x-text="row.ru"></span>
                </div>
                <div class="flex-1 px-3 py-1.5 min-w-0 flex items-center">
                    <template x-if="row.isStart">
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate" x-text="placementLabel(row.placement)"></div>
                            <div class="text-xs text-brand-charcoal dark:text-brand-silver truncate">
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
                        <span class="text-xs text-brand-charcoal/50 dark:text-brand-silver/50">┊</span>
                    </template>
                    <template x-if="!row.occupied">
                        <span class="text-xs text-brand-charcoal/60 dark:text-brand-silver/60">Empty</span>
                    </template>
                </div>
            </button>
        @else
            <div
                class="w-full flex min-h-[1.5rem] border-b border-brand-silver/60 dark:border-brand-charcoal"
                :class="row.occupied ? 'bg-brand/10' : ''"
            >
                <div
                    class="w-8 shrink-0 flex items-center justify-center text-[0.6rem] font-mono text-brand-charcoal dark:text-brand-silver border-r border-brand-silver dark:border-brand-charcoal"
                    :class="row.occupied ? 'bg-brand-silver/40 dark:bg-brand-charcoal/50' : ''"
                >
                    <span x-text="row.ru"></span>
                </div>
                <div class="flex-1 px-1.5 py-0.5 min-w-0 flex items-center overflow-hidden">
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
        @endif
    </template>
</div>

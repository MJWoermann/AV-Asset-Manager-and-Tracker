<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => '—',
    'required' => false,
    'nullable' => false,
    'disabled' => false,
    'createOption' => null,
    'form' => null,
    'autoSubmit' => false,
    'compact' => false,
    'searchUrl' => null,
    'searchParams' => [],
]));

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

foreach (array_filter(([
    'name',
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => '—',
    'required' => false,
    'nullable' => false,
    'disabled' => false,
    'createOption' => null,
    'form' => null,
    'autoSubmit' => false,
    'compact' => false,
    'searchUrl' => null,
    'searchParams' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $id ??= $name;
    $ariaLabel = $attributes->get('aria-label');
    $normalized = collect($options)->map(function ($option) {
        if (is_array($option)) {
            return [
                'value' => (string) ($option['value'] ?? ''),
                'label' => (string) ($option['label'] ?? ''),
                'keywords' => (string) ($option['keywords'] ?? ''),
            ];
        }

        return [
            'value' => (string) data_get($option, 'value', data_get($option, 'id', '')),
            'label' => (string) data_get($option, 'label', data_get($option, 'name', '')),
            'keywords' => (string) data_get($option, 'keywords', ''),
        ];
    })->values();

    $create = null;
    if (is_array($createOption) && ($createOption['value'] ?? null) !== null && ($createOption['value'] ?? '') !== '') {
        $create = [
            'value' => (string) $createOption['value'],
            'label' => (string) ($createOption['label'] ?? '+ Add new…'),
        ];
    }

    $triggerClass = $compact
        ? 'flex min-w-0 flex-1 items-center justify-between gap-1 rounded border border-brand-charcoal/30 bg-white px-2 py-1.5 text-left text-xs dark:border-brand-charcoal dark:bg-black dark:text-white disabled:cursor-not-allowed disabled:opacity-60'
        : 'flex min-w-0 flex-1 items-center justify-between gap-2 rounded border border-gray-300 bg-white px-3 py-2 text-left text-sm dark:border-brand-charcoal dark:bg-black dark:text-white disabled:cursor-not-allowed disabled:opacity-60';

    $clearClass = $compact
        ? 'rounded border border-brand-charcoal/30 px-1.5 text-xs text-brand-charcoal hover:text-black dark:border-brand-charcoal dark:text-brand-silver dark:hover:text-white'
        : 'rounded border border-gray-300 px-2 text-sm text-brand-charcoal hover:text-black dark:border-brand-charcoal dark:text-brand-silver dark:hover:text-white';
?>

<div
    data-searchable-select
    <?php echo e($attributes->except('aria-label')->class($compact ? 'relative' : 'relative mt-1')); ?>

    x-data="searchableSelect(<?php echo \Illuminate\Support\Js::from($normalized)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($selected !== null ? (string) $selected : '')->toHtml() ?>, {
        placeholder: <?php echo \Illuminate\Support\Js::from($placeholder)->toHtml() ?>,
        nullable: <?php echo \Illuminate\Support\Js::from((bool) $nullable)->toHtml() ?>,
        disabled: <?php echo \Illuminate\Support\Js::from((bool) $disabled)->toHtml() ?>,
        createOption: <?php echo \Illuminate\Support\Js::from($create)->toHtml() ?>,
        autoSubmit: <?php echo \Illuminate\Support\Js::from((bool) $autoSubmit)->toHtml() ?>,
        compact: <?php echo \Illuminate\Support\Js::from((bool) $compact)->toHtml() ?>,
        searchUrl: <?php echo \Illuminate\Support\Js::from($searchUrl)->toHtml() ?>,
        searchParams: <?php echo \Illuminate\Support\Js::from($searchParams ?: new \stdClass)->toHtml() ?>,
    })"
    @keydown="onKeydown($event)"
    @click.outside="($refs.panel && $refs.panel.contains($event.target)) || closeList()"
>
    <input type="hidden" name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" x-model="value" <?php if($required): ?> required <?php endif; ?> <?php if($form): ?> form="<?php echo e($form); ?>" <?php endif; ?>>

    <div class="flex gap-1">
        <button
            type="button"
            x-ref="trigger"
            class="<?php echo e($triggerClass); ?>"
            @click="open ? closeList() : openList()"
            :disabled="disabled"
            :aria-expanded="open.toString()"
            aria-haspopup="listbox"
            aria-controls="<?php echo e($id); ?>-listbox"
            <?php if($ariaLabel): ?>
                aria-label="<?php echo e($ariaLabel); ?>"
            <?php endif; ?>
        >
            <span class="truncate" :class="value ? '' : 'text-brand-charcoal dark:text-brand-silver'" x-text="selectedLabel"></span>
            <svg class="<?php echo e($compact ? 'h-3.5 w-3.5' : 'h-4 w-4'); ?> shrink-0 text-brand-charcoal dark:text-brand-silver" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
            </svg>
        </button>
        <button
            type="button"
            x-show="nullable && value && ! disabled"
            x-cloak
            class="<?php echo e($clearClass); ?>"
            @click="clear()"
            aria-label="Clear selection"
        >&times;</button>
    </div>

    <template x-teleport="body">
    <div
        x-show="open"
        x-cloak
        x-ref="panel"
        :style="panelStyle"
        @keydown="onKeydown($event)"
        class="fixed z-[60] border border-brand-silver bg-white shadow-sm dark:border-brand-charcoal dark:bg-brand-slate"
        id="<?php echo e($id); ?>-listbox"
        role="listbox"
    >
        <div class="border-b border-brand-silver p-2 dark:border-brand-charcoal">
            <input
                type="search"
                x-ref="search"
                x-model="query"
                placeholder="Search…"
                class="block w-full rounded border-gray-300 text-sm dark:border-brand-charcoal dark:bg-black dark:text-white"
                autocomplete="off"
            >
        </div>
        <ul class="max-h-60 overflow-y-auto py-1 text-sm">
            <template x-if="remoteLoading">
                <li class="px-3 py-2 text-brand-charcoal dark:text-brand-silver">Searching…</li>
            </template>
            <template x-if="! remoteLoading && filtered.length === 0">
                <li class="px-3 py-2 text-brand-charcoal dark:text-brand-silver" x-text="emptyMessage"></li>
            </template>
            <template x-for="(option, index) in filtered" :key="option.value === '' ? '__empty__' : option.value">
                <li>
                    <button
                        type="button"
                        class="block w-full px-3 py-2 text-left hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal"
                        :class="{
                            'bg-brand-silver/40 dark:bg-brand-charcoal': index === highlight || option.value === value,
                        }"
                        role="option"
                        :aria-selected="(option.value === value).toString()"
                        @click="select(option)"
                        @mouseenter="highlight = index"
                        x-html="highlightedLabel(option.label)"
                    ></button>
                </li>
            </template>
        </ul>
    </div>
    </template>
</div>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/components/searchable-select.blade.php ENDPATH**/ ?>
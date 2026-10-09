@props([
    'name',
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => '—',
    'required' => false,
    'nullable' => false,
])

@php
    $id ??= $name;
    $normalized = collect($options)->map(function ($option) {
        if (is_array($option)) {
            return [
                'value' => (string) ($option['value'] ?? ''),
                'label' => (string) ($option['label'] ?? ''),
            ];
        }

        return [
            'value' => (string) data_get($option, 'value', data_get($option, 'id', '')),
            'label' => (string) data_get($option, 'label', data_get($option, 'name', '')),
        ];
    })->values();
@endphp

<div
    {{ $attributes->class('relative mt-1') }}
    x-data="searchableSelect(@js($normalized), @js($selected !== null ? (string) $selected : ''), {
        placeholder: @js($placeholder),
        nullable: @js((bool) $nullable),
    })"
    @keydown="onKeydown($event)"
    @click.outside="closeList()"
>
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" x-model="value" @if($required) required @endif>

    <div class="flex gap-1">
        <button
            type="button"
            class="flex min-w-0 flex-1 items-center justify-between gap-2 rounded border border-gray-300 bg-white px-3 py-2 text-left text-sm dark:border-brand-charcoal dark:bg-black dark:text-white"
            @click="open ? closeList() : openList()"
            :aria-expanded="open.toString()"
            aria-haspopup="listbox"
            aria-controls="{{ $id }}-listbox"
        >
            <span class="truncate" :class="value ? '' : 'text-brand-charcoal dark:text-brand-silver'" x-text="selectedLabel"></span>
            <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
            </svg>
        </button>
        <button
            type="button"
            x-show="nullable && value"
            x-cloak
            class="rounded border border-gray-300 px-2 text-sm text-brand-charcoal hover:text-black dark:border-brand-charcoal dark:text-brand-silver dark:hover:text-white"
            @click="clear()"
            aria-label="Clear selection"
        >&times;</button>
    </div>

    <div
        x-show="open"
        x-cloak
        class="absolute z-20 mt-1 w-full border border-brand-silver bg-white shadow-sm dark:border-brand-charcoal dark:bg-brand-slate"
        id="{{ $id }}-listbox"
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
            <template x-if="filtered.length === 0">
                <li class="px-3 py-2 text-brand-charcoal dark:text-brand-silver">No matches</li>
            </template>
            <template x-for="(option, index) in filtered" :key="option.value">
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
</div>

@php
    $initialSelections = $suggestedMappings ?? [];
    $requiredFields = [
        ['key' => 'name', 'label' => 'Map a column to Name'],
        ['key' => 'item_type', 'label' => 'Map a column to Item Type'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Map columns</h2></x-slot>
    <div class="max-w-2xl mx-auto px-4">
        <form
            method="POST"
            action="{{ route('import.prepare') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
            data-import-mapper
            x-data="importMapper(@js(array_values($columns)), @js($initialSelections), @js($requiredFields), @js($fieldOptions))"
            @submit="onSubmit($event)"
        >
            @csrf
            <p class="text-sm text-gray-600 dark:text-brand-silver">
                Map each source column to an asset field or custom field (listed as Set: Field). Item type and location
                (level / room / rack) are set here — missing types and locations are created automatically. Mapped
                custom field sets are enabled on item types that do not already have them. Each target field may only
                be used once. Name and Item Type are required before you can continue.
            </p>

            @if (! empty($usedRememberedMappings))
                <p class="text-sm text-brand dark:text-brand">
                    Suggestions include mappings from your previous import where column names match.
                </p>
            @endif

            <div
                x-show="!canSubmit"
                x-cloak
                class="text-sm text-red-700 dark:text-red-400 border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-3 py-2"
            >
                <p class="font-medium">Finish these requirements before reviewing duplicates:</p>
                <ul class="mt-2 list-disc list-inside space-y-1">
                    <template x-for="requirement in missingRequirements" :key="requirement">
                        <li x-text="requirement"></li>
                    </template>
                    <li x-show="duplicateWarning" x-text="duplicateWarning"></li>
                </ul>
            </div>

            @if ($errors->has('mapping'))
                <div class="text-sm text-red-700 dark:text-red-400 border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-3 py-2">
                    <p class="font-medium">Finish these requirements before reviewing duplicates:</p>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach ($errors->get('mapping') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @foreach($columns as $column)
                @php
                    $columnExample = $columnExamples[$column] ?? null;
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center text-sm">
                    <div class="min-w-0">
                        <div class="font-mono break-all">{{ $column }}</div>
                        @if ($columnExample === null)
                            <div class="mt-0.5 text-xs italic text-brand-charcoal dark:text-brand-silver">empty column</div>
                        @else
                            <div class="mt-0.5 text-xs text-brand-charcoal dark:text-brand-silver truncate" title="{{ $columnExample }}">
                                e.g. {{ $columnExample }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <div
                            class="relative"
                            x-data="importMappingSelect(@js($column))"
                            @keydown="onKeydown($event)"
                            @click.outside="closeList()"
                        >
                            <input type="hidden" name="mapping[{{ $column }}]" x-bind:value="value">

                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-center justify-between gap-2 rounded border border-gray-300 bg-white px-3 py-2 text-left text-sm dark:border-brand-charcoal dark:bg-black dark:text-white"
                                    @click="open ? closeList() : openList()"
                                    :aria-expanded="open.toString()"
                                    aria-haspopup="listbox"
                                >
                                    <span class="truncate" :class="value ? '' : 'text-brand-charcoal dark:text-brand-silver'" x-text="selectedLabel"></span>
                                    <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    x-show="value"
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
                                    <template x-for="(option, index) in filtered" :key="option.value === '' ? '__ignore' : option.value">
                                        <li>
                                            <button
                                                type="button"
                                                class="block w-full px-3 py-2 text-left"
                                                :class="{
                                                    'opacity-40 cursor-not-allowed': isOptionTaken(option.value),
                                                    'hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal': !isOptionTaken(option.value),
                                                    'bg-brand-silver/40 dark:bg-brand-charcoal': index === highlight || option.value === value,
                                                }"
                                                role="option"
                                                :aria-selected="(option.value === value).toString()"
                                                :aria-disabled="isOptionTaken(option.value).toString()"
                                                @click="select(option)"
                                                @mouseenter="highlight = index"
                                                x-html="highlightedLabel(option.label)"
                                            ></button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('mapping.'.$column)" class="mt-1" />
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-brand-black dark:bg-brand border border-transparent font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand disabled:opacity-50 disabled:cursor-not-allowed"
                    x-bind:disabled="!canSubmit"
                >
                    Review duplicates
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

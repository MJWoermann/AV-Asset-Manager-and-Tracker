<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Merge assets</h2>
            <a href="{{ route('assets.duplicates.index') }}" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Back to duplicates</a>
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
        x-data="assetMerge(@js([
            'survivorId' => $assets->first()->id,
            'emptySource' => $emptySource,
            'fields' => $defaultFields,
            'customFields' => $defaultCustomFields,
            'assetIds' => $assets->pluck('id')->values(),
            'quantityTotal' => $quantityTotal,
            'setQuantityToTotal' => (bool) old('set_quantity_to_total', false),
        ]))"
    >
        @if ($errors->any())
            <div class="text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Choose which asset survives (keeps its ID). For each field, pick which column’s value to keep, or clear it.
            Conflicting values are highlighted. Non-survivor assets are deleted after related records are reassigned.
        </p>

        <form method="POST" action="{{ route('assets.duplicates.merge.store') }}" class="space-y-4">
            @csrf
            @foreach ($assets as $asset)
                <input type="hidden" name="asset_ids[]" value="{{ $asset->id }}">
            @endforeach
            <input type="hidden" name="survivor_id" :value="survivorId">
            <input type="hidden" name="confirm_merge" value="1">

            <label class="flex items-start gap-3 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 text-sm cursor-pointer">
                <input
                    type="checkbox"
                    name="set_quantity_to_total"
                    value="1"
                    class="mt-1 rounded border-brand-charcoal/40"
                    x-model="setQuantityToTotal"
                >
                <span>
                    <span class="font-medium block">Set quantity to total of merged assets</span>
                    <span class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Survivor quantity becomes
                        <span class="font-mono font-semibold" x-text="quantityTotal"></span>
                        (sum of each selected asset’s quantity; blank/zero counts as 1). Overrides the Quantity row below.
                    </span>
                </span>
            </label>

            <div class="overflow-x-auto border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
                <table class="min-w-full text-sm">
                    <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                        <tr>
                            <th class="px-3 py-2 sticky left-0 z-10 bg-brand-silver dark:bg-brand-charcoal min-w-[10rem]">Field</th>
                            @foreach ($assets as $asset)
                                <th class="px-3 py-2 min-w-[12rem] align-top">
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="survivor_preview"
                                            class="mt-1"
                                            value="{{ $asset->id }}"
                                            x-model.number="survivorId"
                                        >
                                        <span>
                                            <span class="font-medium block">{{ $asset->name }}</span>
                                            <span class="text-xs text-brand-charcoal dark:text-brand-silver">#{{ $asset->id }} · survivor when selected · qty {{ max(1, (int) ($asset->quantity ?? 1)) }}</span>
                                        </span>
                                    </label>
                                </th>
                            @endforeach
                            <th class="px-3 py-2 min-w-[7rem]">Clear</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $key = $row['key'];
                                $isCustom = ($row['type'] ?? '') === 'custom';
                                $definitionId = $row['definition_id'] ?? null;
                                $inputName = $isCustom
                                    ? 'custom_fields['.$definitionId.']'
                                    : 'fields['.$key.']';
                                $modelKey = $isCustom ? 'customFields[\''.$definitionId.'\']' : 'fields[\''.$key.'\']';
                                $conflict = $valueMatrix[$key]['_conflict'] ?? false;
                                $allBlank = $valueMatrix[$key]['_all_blank'] ?? false;
                                $isQuantityRow = $key === 'quantity';
                            @endphp
                            <tr
                                @class([
                                    'border-t border-brand-silver dark:border-brand-charcoal',
                                    'bg-amber-50 dark:bg-amber-950/30' => $conflict && ! $isQuantityRow,
                                ])
                                @if ($isQuantityRow)
                                    :class="setQuantityToTotal ? 'opacity-50' : ''"
                                @endif
                            >
                                <td class="px-3 py-2 sticky left-0 z-10 bg-white dark:bg-brand-slate font-medium align-top">
                                    {{ $row['label'] }}
                                    @if ($isQuantityRow)
                                        <span class="block text-xs font-normal text-brand" x-show="setQuantityToTotal" x-cloak>
                                            Forced to total (<span x-text="quantityTotal"></span>)
                                        </span>
                                    @elseif ($conflict)
                                        <span class="block text-xs font-normal text-amber-800 dark:text-amber-200">Conflict</span>
                                    @elseif ($allBlank)
                                        <span class="block text-xs font-normal text-brand-charcoal dark:text-brand-silver">All empty</span>
                                    @endif
                                    @if (! $isQuantityRow)
                                        <input type="hidden" name="{{ $inputName }}" :value="{{ $modelKey }}">
                                    @else
                                        <template x-if="!setQuantityToTotal">
                                            <input type="hidden" name="fields[quantity]" :value="fields['quantity']">
                                        </template>
                                    @endif
                                </td>
                                @foreach ($assets as $asset)
                                    @php
                                        $cell = $valueMatrix[$key][$asset->id] ?? ['display' => '', 'blank' => true];
                                    @endphp
                                    <td class="px-3 py-2 align-top">
                                        @if ($isQuantityRow)
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    class="mt-1"
                                                    value="{{ $asset->id }}"
                                                    x-model="fields['quantity']"
                                                    :disabled="setQuantityToTotal || {{ $cell['blank'] ? 'true' : 'false' }}"
                                                >
                                                <span class="text-xs break-words {{ $cell['blank'] ? 'text-brand-charcoal/50 dark:text-brand-silver/50' : '' }}">
                                                    {{ $cell['blank'] ? '—' : $cell['display'] }}
                                                </span>
                                            </label>
                                        @else
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    class="mt-1"
                                                    value="{{ $asset->id }}"
                                                    x-model="{{ $modelKey }}"
                                                    @disabled($cell['blank'])
                                                >
                                                <span class="text-xs break-words {{ $cell['blank'] ? 'text-brand-charcoal/50 dark:text-brand-silver/50' : '' }}">
                                                    {{ $cell['blank'] ? '—' : $cell['display'] }}
                                                </span>
                                            </label>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 align-top">
                                    @if ($isQuantityRow)
                                        <span class="text-xs text-brand-charcoal/50 dark:text-brand-silver/50">Required</span>
                                    @elseif (! in_array($key, \App\Services\AssetMergeService::REQUIRED_FIELDS, true))
                                        <label class="flex items-center gap-2 cursor-pointer text-xs">
                                            <input type="radio" value="{{ $emptySource }}" x-model="{{ $modelKey }}">
                                            <span>Empty</span>
                                        </label>
                                    @else
                                        <span class="text-xs text-brand-charcoal/50 dark:text-brand-silver/50">Required</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('assets.duplicates.index') }}" class="px-4 py-2 text-sm border border-brand-charcoal dark:border-brand-silver">Cancel</a>
                <button type="submit" class="px-4 py-2 text-sm bg-brand text-white">Merge permanently</button>
            </div>
        </form>
    </div>

    <script>
        function assetMerge(config) {
            return {
                survivorId: config.survivorId,
                emptySource: config.emptySource,
                fields: config.fields || {},
                customFields: config.customFields || {},
                quantityTotal: config.quantityTotal || 0,
                setQuantityToTotal: !!config.setQuantityToTotal,
            };
        }
    </script>
</x-app-layout>

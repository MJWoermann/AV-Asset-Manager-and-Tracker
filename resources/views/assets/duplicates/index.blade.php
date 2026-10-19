<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Duplicate check</h2>
            <a href="{{ route('assets.index') }}" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Back to assets</a>
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
        x-data="duplicateGroups(@js([
            'maxMerge' => \App\Services\AssetMergeService::MAX_ASSETS,
            'matchKeys' => \App\Services\AssetDuplicateService::MATCH_KEYS,
            'matchKeyLabels' => $matchKeyLabels,
            'groupMatchedOn' => collect($groups)->map(fn ($group) => $group['matched_on'])->values(),
            'groupAssetOptions' => collect($groups)->map(fn ($group) => $group['assets']->map(fn ($asset) => [
                'id' => $asset->id,
                'label' => $asset->name
                    .(collect([$asset->tp_barcode, $asset->fmi_ast, $asset->serial_number])->filter()->isNotEmpty()
                        ? ' ('.collect([$asset->tp_barcode, $asset->fmi_ast, $asset->serial_number])->filter()->implode(' · ').')'
                        : ''),
            ])->values())->values(),
        ]))"
    >
        @if (session('status'))
            <div class="text-sm text-brand">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Groups share at least one identifier (serial, FMI/AST, MAC, rig tag, TP barcode, or IP), matched case-insensitively within your active department scope.
            Select assets to merge (2–{{ \App\Services\AssetMergeService::MAX_ASSETS }}), clear shared identifiers, or link as parent/child.
        </p>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3">
            <p class="text-sm">
                @if ($includeRelated)
                    <span class="font-medium">Mode:</span> including parent/child matches
                    <span class="text-brand-charcoal dark:text-brand-silver">(assets already linked in a hierarchy may appear)</span>
                @else
                    <span class="font-medium">Mode:</span> excluding parent/child matches
                    <span class="text-brand-charcoal dark:text-brand-silver">(default — ancestor/descendant pairs that share identifiers are hidden)</span>
                @endif
            </p>
            <div class="flex flex-wrap gap-2 sm:justify-end">
                @if ($includeRelated)
                    <a
                        href="{{ $excludeRelatedUrl }}"
                        class="px-3 py-2 border border-brand-charcoal dark:border-brand-silver text-sm inline-flex items-center justify-center"
                    >Hide parent/child matches</a>
                @else
                    <a
                        href="{{ $includeRelatedUrl }}"
                        class="px-3 py-2 border border-brand-charcoal dark:border-brand-silver text-sm inline-flex items-center justify-center"
                    >Search including parent/child</a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('assets.duplicates.index') }}" class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:items-end border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3">
            @if ($includeRelated)
                <input type="hidden" name="include_related" value="1">
            @endif
            <div class="min-w-[12rem]">
                <x-input-label for="duplicate_field_filter" value="Matched field" />
                <select
                    id="duplicate_field_filter"
                    name="field"
                    class="mt-1 w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm"
                    onchange="this.form.requestSubmit()"
                >
                    <option value="">All fields</option>
                    @foreach ($matchKeyLabels as $key => $label)
                        <option value="{{ $key }}" @selected($fieldFilter === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm pb-2 sm:pb-1">
                <input
                    type="checkbox"
                    name="multi"
                    value="1"
                    class="rounded border-brand-charcoal/40"
                    @checked($multiFieldOnly)
                    onchange="this.form.requestSubmit()"
                >
                <span>2+ fields only</span>
            </label>
            <x-clear-filters
                :href="$clearFiltersUrl"
                :active="$filtersActive"
            />
        </form>

        <div class="text-sm">
            <span class="font-semibold">{{ $groupCount }} {{ $groupCount === 1 ? 'group' : 'groups' }}</span>
            ·
            <span class="font-semibold">{{ $assetCount }} {{ $assetCount === 1 ? 'asset' : 'assets' }} involved</span>
            @if ($filtersActive && $totalGroupCount !== $groupCount)
                <span class="text-brand-charcoal dark:text-brand-silver">({{ $totalGroupCount }} total before filter)</span>
            @endif
        </div>

        @forelse ($groups as $groupIndex => $group)
            <div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
                <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <p class="font-medium">
                            Group {{ $groupIndex + 1 }}
                            <span class="text-brand-charcoal dark:text-brand-silver font-normal">· {{ $group['count'] }} assets</span>
                        </p>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver mt-1">
                            Matched on
                            @foreach ($group['matched_on'] as $key)
                                <span class="inline-block mr-2">
                                    {{ $matchKeyLabels[$key] ?? $key }}:
                                    <span class="font-mono">{{ $group['match_values'][$key] ?? '—' }}</span>
                                </span>
                            @endforeach
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center">
                        <span class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="selectionLabel({{ $groupIndex }})"></span>
                        <button
                            type="button"
                            class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver disabled:opacity-40"
                            :disabled="!canClearGroup({{ $groupIndex }})"
                            @click="openClearModal({{ $groupIndex }})"
                        >Clear fields…</button>
                        <button
                            type="button"
                            class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver disabled:opacity-40"
                            :disabled="!canParentChildGroup({{ $groupIndex }})"
                            @click="openParentModal({{ $groupIndex }})"
                        >Make parent/child…</button>
                        <form method="GET" action="{{ route('assets.duplicates.merge') }}" class="inline"
                              @submit="prepareMergeSubmit($event, {{ $groupIndex }})">
                            <template x-for="id in selectedByGroup[{{ $groupIndex }}] || []" :key="'merge-g{{ $groupIndex }}-'+id">
                                <input type="hidden" name="asset_ids[]" :value="id">
                            </template>
                            <button
                                type="submit"
                                class="px-3 py-2 text-sm bg-brand text-white disabled:opacity-40"
                                :disabled="!canMergeGroup({{ $groupIndex }})"
                            >Merge selected</button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                            <tr>
                                <th class="px-3 py-2 w-10">
                                    <input
                                        type="checkbox"
                                        class="rounded border-brand-charcoal/40"
                                        @change="toggleGroup({{ $groupIndex }}, $event.target.checked, @js($group['asset_ids']))"
                                        :checked="isGroupFullySelected({{ $groupIndex }}, {{ count($group['asset_ids']) }})"
                                    >
                                </th>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">TP barcode</th>
                                <th class="px-3 py-2">FMI / AST</th>
                                <th class="px-3 py-2">Serial</th>
                                <th class="px-3 py-2">Rig tag</th>
                                <th class="px-3 py-2">MAC</th>
                                <th class="px-3 py-2">IP</th>
                                <th class="px-3 py-2">ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['assets'] as $asset)
                                <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                                    <td class="px-3 py-2">
                                        <input
                                            type="checkbox"
                                            class="rounded border-brand-charcoal/40"
                                            value="{{ $asset->id }}"
                                            @change="toggleAsset({{ $groupIndex }}, {{ $asset->id }}, $event.target.checked)"
                                            :checked="isSelected({{ $groupIndex }}, {{ $asset->id }})"
                                        >
                                    </td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('assets.show', $asset) }}" class="text-brand hover:underline">{{ $asset->name }}</a>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $asset->tp_barcode ?: '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $asset->fmi_ast ?: '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $asset->serial_number ?: '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $asset->rig_tag ?: '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $asset->mac_address ?: '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $asset->ip_address ?: '—' }}</td>
                                    <td class="px-3 py-2 text-xs text-brand-charcoal dark:text-brand-silver">#{{ $asset->id }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-6 text-sm text-brand-charcoal dark:text-brand-silver">
                @if ($filtersActive)
                    No duplicate groups match the current filters.
                    <a href="{{ route('assets.duplicates.index') }}" class="text-brand underline ml-1">Clear filters</a>
                @else
                    No duplicates found. Assets are grouped when they share a serial number, FMI/AST, MAC, rig tag, TP barcode, or IP address.
                @endif
            </div>
        @endforelse

        <div
            x-cloak
            x-show="clearModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="clear-duplicate-fields-title"
        >
            <div class="fixed inset-0 bg-black/50" @click="cancelClear()"></div>
            <div class="relative w-full max-w-lg max-h-[calc(100vh-3rem)] overflow-y-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-4 shadow-xl">
                <h3 id="clear-duplicate-fields-title" class="text-lg font-semibold">Clear identifier fields</h3>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Remove the selected identifier values from
                    <span class="font-semibold text-brand-black dark:text-white" x-text="clearSelectedCount()"></span>
                    asset<span x-show="clearSelectedCount() !== 1">s</span>.
                    Leave the field on any assets you did not select so they are no longer flagged together on that field.
                </p>

                <fieldset class="space-y-2 text-sm border border-brand-silver dark:border-brand-charcoal p-3">
                    <legend class="px-1 font-medium">Fields to clear</legend>
                    <template x-for="key in matchKeys" :key="'clear-field-'+key">
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                class="rounded border-brand-charcoal/40"
                                :value="key"
                                x-model="clearFields"
                            >
                            <span x-text="matchKeyLabels[key] || key"></span>
                        </label>
                    </template>
                </fieldset>

                <form method="POST" action="{{ route('assets.duplicates.clear') }}" @submit="prepareClearSubmit($event)">
                    @csrf
                    <input type="hidden" name="confirm_clear" value="1">
                    <template x-for="id in clearAssetIds()" :key="'clear-asset-'+id">
                        <input type="hidden" name="asset_ids[]" :value="id">
                    </template>
                    <template x-for="field in clearFields" :key="'clear-post-'+field">
                        <input type="hidden" name="fields[]" :value="field">
                    </template>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="cancelClear()">Cancel</button>
                        <button
                            type="submit"
                            class="px-3 py-2 text-sm bg-brand text-white disabled:opacity-40"
                            :disabled="clearFields.length === 0 || clearSelectedCount() < 1"
                        >Clear values</button>
                    </div>
                </form>
            </div>
        </div>

        <div
            x-cloak
            x-show="parentModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="parent-child-title"
        >
            <div class="fixed inset-0 bg-black/50" @click="cancelParent()"></div>
            <div class="relative w-full max-w-lg max-h-[calc(100vh-3rem)] overflow-y-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-4 shadow-xl">
                <h3 id="parent-child-title" class="text-lg font-semibold">Make parent / child</h3>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Choose which selected asset is the parent. The others become its children.
                    Children inherit the parent’s location (and department). Existing parents are replaced.
                    Cycles are blocked.
                </p>

                <fieldset class="space-y-2 text-sm">
                    <legend class="font-medium text-brand-black dark:text-white">Parent asset</legend>
                    <template x-for="option in parentOptions()" :key="'parent-opt-'+option.id">
                        <label class="flex items-start gap-2 border border-brand-silver dark:border-brand-charcoal p-2 cursor-pointer">
                            <input
                                type="radio"
                                class="mt-1"
                                :value="option.id"
                                x-model.number="parentId"
                            >
                            <span x-text="option.label"></span>
                        </label>
                    </template>
                </fieldset>

                <form method="POST" action="{{ route('assets.duplicates.parent-child') }}" @submit="prepareParentSubmit($event)">
                    @csrf
                    <input type="hidden" name="confirm_parent_child" value="1">
                    <input type="hidden" name="parent_id" :value="parentId">
                    <template x-for="id in parentAssetIds()" :key="'parent-asset-'+id">
                        <input type="hidden" name="asset_ids[]" :value="id">
                    </template>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="cancelParent()">Cancel</button>
                        <button
                            type="submit"
                            class="px-3 py-2 text-sm bg-brand text-white disabled:opacity-40"
                            :disabled="!parentId || parentSelectedCount() < 2"
                        >Link as parent/child</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function duplicateGroups(config) {
            const maxMerge = config.maxMerge || 6;

            return {
                selectedByGroup: {},
                matchKeys: config.matchKeys || [],
                matchKeyLabels: config.matchKeyLabels || {},
                groupMatchedOn: config.groupMatchedOn || [],
                groupAssetOptions: config.groupAssetOptions || [],
                clearModalOpen: false,
                clearGroupIndex: null,
                clearFields: [],
                parentModalOpen: false,
                parentGroupIndex: null,
                parentId: null,

                ensureGroup(groupIndex) {
                    if (!this.selectedByGroup[groupIndex]) {
                        this.selectedByGroup[groupIndex] = [];
                    }
                },

                isSelected(groupIndex, assetId) {
                    this.ensureGroup(groupIndex);
                    return this.selectedByGroup[groupIndex].includes(assetId);
                },

                isGroupFullySelected(groupIndex, total) {
                    this.ensureGroup(groupIndex);
                    return this.selectedByGroup[groupIndex].length === total && total > 0;
                },

                toggleAsset(groupIndex, assetId, checked) {
                    this.ensureGroup(groupIndex);
                    const list = this.selectedByGroup[groupIndex];
                    if (checked) {
                        if (!list.includes(assetId)) {
                            list.push(assetId);
                        }
                    } else {
                        this.selectedByGroup[groupIndex] = list.filter((id) => id !== assetId);
                    }
                },

                toggleGroup(groupIndex, checked, assetIds) {
                    this.ensureGroup(groupIndex);
                    if (!checked) {
                        this.selectedByGroup[groupIndex] = [];
                        return;
                    }
                    this.selectedByGroup[groupIndex] = [...assetIds];
                },

                canMergeGroup(groupIndex) {
                    this.ensureGroup(groupIndex);
                    const count = this.selectedByGroup[groupIndex].length;
                    return count >= 2 && count <= maxMerge;
                },

                canClearGroup(groupIndex) {
                    this.ensureGroup(groupIndex);
                    return this.selectedByGroup[groupIndex].length >= 1;
                },

                canParentChildGroup(groupIndex) {
                    this.ensureGroup(groupIndex);
                    return this.selectedByGroup[groupIndex].length >= 2;
                },

                selectionLabel(groupIndex) {
                    this.ensureGroup(groupIndex);
                    const count = this.selectedByGroup[groupIndex].length;
                    if (count === 0) {
                        return 'None selected';
                    }
                    let label = count + ' selected';
                    if (count > maxMerge) {
                        label += ' (merge max ' + maxMerge + ')';
                    }
                    return label;
                },

                prepareMergeSubmit(event, groupIndex) {
                    if (!this.canMergeGroup(groupIndex)) {
                        event.preventDefault();
                        return false;
                    }
                    return true;
                },

                openClearModal(groupIndex) {
                    if (!this.canClearGroup(groupIndex)) {
                        return;
                    }
                    this.clearGroupIndex = groupIndex;
                    const matched = this.groupMatchedOn[groupIndex] || [];
                    this.clearFields = matched.length > 0 ? [...matched] : [...this.matchKeys];
                    this.clearModalOpen = true;
                },

                cancelClear() {
                    this.clearModalOpen = false;
                    this.clearGroupIndex = null;
                    this.clearFields = [];
                },

                clearSelectedCount() {
                    if (this.clearGroupIndex === null) {
                        return 0;
                    }
                    this.ensureGroup(this.clearGroupIndex);
                    return this.selectedByGroup[this.clearGroupIndex].length;
                },

                clearAssetIds() {
                    if (this.clearGroupIndex === null) {
                        return [];
                    }
                    this.ensureGroup(this.clearGroupIndex);
                    return this.selectedByGroup[this.clearGroupIndex];
                },

                prepareClearSubmit(event) {
                    if (this.clearSelectedCount() < 1 || this.clearFields.length === 0) {
                        event.preventDefault();
                        return false;
                    }
                    return true;
                },

                openParentModal(groupIndex) {
                    if (!this.canParentChildGroup(groupIndex)) {
                        return;
                    }
                    this.parentGroupIndex = groupIndex;
                    this.ensureGroup(groupIndex);
                    this.parentId = this.selectedByGroup[groupIndex][0] ?? null;
                    this.parentModalOpen = true;
                },

                cancelParent() {
                    this.parentModalOpen = false;
                    this.parentGroupIndex = null;
                    this.parentId = null;
                },

                parentSelectedCount() {
                    if (this.parentGroupIndex === null) {
                        return 0;
                    }
                    this.ensureGroup(this.parentGroupIndex);
                    return this.selectedByGroup[this.parentGroupIndex].length;
                },

                parentAssetIds() {
                    if (this.parentGroupIndex === null) {
                        return [];
                    }
                    this.ensureGroup(this.parentGroupIndex);
                    return this.selectedByGroup[this.parentGroupIndex];
                },

                parentOptions() {
                    const ids = this.parentAssetIds();
                    const options = this.groupAssetOptions[this.parentGroupIndex] || [];
                    return options.filter((option) => ids.includes(option.id));
                },

                prepareParentSubmit(event) {
                    if (!this.parentId || this.parentSelectedCount() < 2) {
                        event.preventDefault();
                        return false;
                    }
                    return true;
                },
            };
        }
    </script>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Duplicate check</h2>
            <a href="{{ route('assets.index') }}" class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver">Back to assets</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4" x-data="duplicateGroups()">
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
            Select 2–{{ \App\Services\AssetMergeService::MAX_ASSETS }} assets in a group to review a side-by-side merge.
        </p>

        <div class="text-sm">
            <span class="font-semibold">{{ $groupCount }}</span> group{{ $groupCount === 1 ? '' : 's' }}
            ·
            <span class="font-semibold">{{ $assetCount }}</span> asset{{ $assetCount === 1 ? '' : 's' }} involved
        </div>

        @forelse ($groups as $groupIndex => $group)
            <div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
                <div class="px-4 py-3 border-b border-brand-silver dark:border-brand-charcoal flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
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
                    <form method="GET" action="{{ route('assets.duplicates.merge') }}" class="flex gap-2 items-center"
                          @submit="prepareGroupSubmit($event, {{ $groupIndex }})">
                        <template x-for="id in selectedByGroup[{{ $groupIndex }}] || []" :key="'g{{ $groupIndex }}-'+id">
                            <input type="hidden" name="asset_ids[]" :value="id">
                        </template>
                        <span class="text-xs text-brand-charcoal dark:text-brand-silver" x-text="selectionLabel({{ $groupIndex }})"></span>
                        <button
                            type="submit"
                            class="px-3 py-2 text-sm bg-brand text-white disabled:opacity-40"
                            :disabled="!canMergeGroup({{ $groupIndex }})"
                        >Merge selected</button>
                    </form>
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
                No duplicates found. Assets are grouped when they share a serial number, FMI/AST, MAC, rig tag, TP barcode, or IP address.
            </div>
        @endforelse
    </div>

    <script>
        function duplicateGroups() {
            const maxAssets = {{ \App\Services\AssetMergeService::MAX_ASSETS }};

            return {
                selectedByGroup: {},

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
                        if (!list.includes(assetId) && list.length < maxAssets) {
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
                    this.selectedByGroup[groupIndex] = assetIds.slice(0, maxAssets);
                },

                canMergeGroup(groupIndex) {
                    this.ensureGroup(groupIndex);
                    const count = this.selectedByGroup[groupIndex].length;
                    return count >= 2 && count <= maxAssets;
                },

                selectionLabel(groupIndex) {
                    this.ensureGroup(groupIndex);
                    const count = this.selectedByGroup[groupIndex].length;
                    if (count === 0) {
                        return 'None selected';
                    }
                    return count + ' selected' + (count > maxAssets ? ' (max ' + maxAssets + ')' : '');
                },

                prepareGroupSubmit(event, groupIndex) {
                    if (!this.canMergeGroup(groupIndex)) {
                        event.preventDefault();
                        return false;
                    }
                    return true;
                },
            };
        }
    </script>
</x-app-layout>

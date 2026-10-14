@php
    $capacity = (int) ($asset->ru_height ?? 0);
    $rearEnabled = (bool) $asset->ru_rear_enabled;
    $placements = $ruPlacementsPayload ?? [];
    $childOptions = $ruChildAssetOptions ?? [];
    $unplacedChildren = $ruUnplacedChildren ?? collect();
    $hasParentConfirmError = $errors->has('confirm_parent_change');
    $oldKind = old('kind', 'asset');
    $openPanel = ($hasParentConfirmError || ($errors->any() && old('start_ru') !== null))
        ? (old('_placement_id') ? 'edit' : 'add')
        : null;
    $editingId = old('_placement_id') ? (int) old('_placement_id') : null;
    $pendingChild = collect($childOptions)->first(
        fn ($option) => (string) ($option['value'] ?? '') === (string) old('child_asset_id')
    );
@endphp

<div
    class="space-y-6"
    x-data="ruVisualiser(@js([
        'capacity' => $capacity,
        'rearEnabled' => $rearEnabled,
        'initialFace' => old('face', 'front'),
        'placements' => $placements,
        'childOptions' => $childOptions,
        'storeUrl' => route('assets.ru-placements.store', $asset),
        'rackId' => (string) $asset->id,
        'showParentConfirm' => $hasParentConfirmError,
        'pendingParentName' => $pendingChild['parent_name'] ?? '',
        'openPanel' => $openPanel,
        'editingId' => $editingId,
        'old' => [
            'face' => old('face', 'front'),
            'start_ru' => old('start_ru'),
            'ru_height' => old('ru_height'),
            'kind' => $oldKind,
            'child_asset_id' => old('child_asset_id', ''),
            'note' => old('note', ''),
            'confirm_parent_change' => old('confirm_parent_change', false),
        ],
    ]))"
>
    @if (session('status'))
        <p class="text-sm text-brand">{{ session('status') }}</p>
    @endif

    @if ($errors->any() && ! $hasParentConfirmError)
        <div class="text-sm text-red-600 space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if ($capacity < 1)
        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-3">
            <h3 class="font-semibold text-lg">Set RU height</h3>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                This rack needs a capacity before you can place equipment or notes.
            </p>
            <a href="{{ route('assets.edit', $asset) }}" class="inline-flex px-3 py-2 bg-brand text-white text-sm">
                Edit asset to set RU height
            </a>
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-lg">RU visualiser</h3>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    {{ $capacity }} RU · numbering bottom-up (RU 1 at bottom)
                    @if($rearEnabled)
                        · rear face enabled
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="px-3 py-2 text-sm border border-brand-charcoal dark:border-white"
                    :class="face === 'front' ? 'bg-brand text-white border-brand' : ''"
                    @click="face = 'front'"
                >
                    Front
                </button>
                @if($rearEnabled)
                    <button
                        type="button"
                        class="px-3 py-2 text-sm border border-brand-charcoal dark:border-white"
                        :class="face === 'rear' ? 'bg-brand text-white border-brand' : ''"
                        @click="face = 'rear'"
                    >
                        Rear
                    </button>
                @endif
                <button
                    type="button"
                    class="px-3 py-2 text-sm bg-brand text-white"
                    @click="openAdd()"
                >
                    Add placement
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-2">
                <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal overflow-hidden">
                    <template x-for="row in rows" :key="face + '-' + row.ru">
                        <button
                            type="button"
                            class="w-full flex min-h-[2rem] border-b border-brand-silver/60 dark:border-brand-charcoal text-left"
                            :class="row.occupied ? 'bg-brand/10 hover:bg-brand/15' : 'hover:bg-brand/5'"
                            @click="row.placement ? openEdit(row.placement) : openAdd(row.ru)"
                        >
                            <div class="w-12 shrink-0 flex items-center justify-center text-xs font-mono text-brand-charcoal dark:text-brand-silver border-r border-brand-silver dark:border-brand-charcoal"
                                 :class="row.occupied ? 'bg-brand-silver/40 dark:bg-brand-charcoal/50' : ''">
                                <span x-text="row.ru"></span>
                            </div>
                            <div class="flex-1 px-3 py-1.5 min-w-0 flex items-center">
                                <template x-if="row.isStart">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium truncate" x-text="placementLabel(row.placement)"></div>
                                        <div class="text-xs text-brand-charcoal dark:text-brand-silver">
                                            <span x-text="row.placement.start_ru"></span>–<span x-text="Number(row.placement.start_ru) + Number(row.placement.ru_height) - 1"></span>
                                            RU
                                            ·
                                            <span x-text="row.placement.kind === 'note' ? 'Note' : 'Asset'"></span>
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
                    </template>
                </div>
            </div>

            <div class="space-y-4">
                <div
                    x-show="panel"
                    x-cloak
                    class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold" x-text="panel === 'edit' ? 'Edit placement' : 'Add placement'"></h4>
                        <button type="button" class="text-sm text-brand-charcoal dark:text-brand-silver" @click="closePanel()">Close</button>
                    </div>

                    <form
                        method="POST"
                        x-ref="placementForm"
                        :action="panel === 'edit' && editingPlacement() ? editingPlacement().update_url : storeUrl"
                        @submit="prepareSubmit($event)"
                        class="space-y-3"
                    >
                        @csrf
                        <input type="hidden" name="_method" value="PUT" x-bind:disabled="panel !== 'edit'">
                        <input type="hidden" name="_placement_id" :value="editingId || ''">
                        <input type="hidden" name="confirm_parent_change" :value="form.confirm_parent_change ? '1' : '0'">

                        <div>
                            <x-input-label value="Face" />
                            <select
                                name="face"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="form.face"
                                required
                            >
                                <option value="front">Front</option>
                                @if($rearEnabled)
                                    <option value="rear">Rear</option>
                                @endif
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label value="Start RU" />
                                <input
                                    type="number"
                                    name="start_ru"
                                    min="1"
                                    :max="capacity"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                    x-model="form.start_ru"
                                    required
                                >
                            </div>
                            <div>
                                <x-input-label value="Height (RU)" />
                                <input
                                    type="number"
                                    name="ru_height"
                                    min="1"
                                    :max="capacity"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                    x-model="form.ru_height"
                                >
                            </div>
                        </div>

                        <div>
                            <x-input-label value="Kind" />
                            <select
                                name="kind"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="form.kind"
                                required
                            >
                                <option value="asset">Child asset</option>
                                <option value="note">Text note</option>
                            </select>
                        </div>

                        <div x-show="form.kind === 'asset'" x-cloak class="space-y-2">
                            <x-input-label value="Child asset" />
                            <select
                                name="child_asset_id"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="form.child_asset_id"
                                @change="onChildSelected(form.child_asset_id)"
                                :required="form.kind === 'asset'"
                                :disabled="form.kind !== 'asset'"
                            >
                                <option value="">Select…</option>
                                <template x-for="option in childOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                            <p class="text-xs text-brand-charcoal dark:text-brand-silver" x-show="selectedChild()?.parent_name">
                                Currently parented to <span x-text="selectedChild()?.parent_name"></span>
                            </p>
                        </div>

                        <div x-show="form.kind === 'note'" x-cloak>
                            <x-input-label value="Note" />
                            <textarea
                                name="note"
                                rows="3"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                x-model="form.note"
                                :required="form.kind === 'note'"
                                :disabled="form.kind !== 'note'"
                            ></textarea>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-primary-button type="submit">
                                <span x-text="panel === 'edit' ? 'Save placement' : 'Place'"></span>
                            </x-primary-button>
                            <x-secondary-button type="button" @click="closePanel()">Cancel</x-secondary-button>
                        </div>
                    </form>

                    <form
                        x-show="panel === 'edit' && editingPlacement()"
                        x-cloak
                        method="POST"
                        :action="editingPlacement()?.destroy_url"
                        onsubmit="return confirm('Remove this RU placement?')"
                        class="pt-2 border-t border-brand-silver dark:border-brand-charcoal"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600">Remove placement</button>
                    </form>
                </div>

                <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
                    <h4 class="text-sm font-semibold">Unplaced children</h4>
                    @forelse($unplacedChildren as $child)
                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm border-b border-brand-silver/50 dark:border-brand-charcoal pb-2 last:border-0 last:pb-0">
                            <a href="{{ route('assets.show', $child) }}" class="text-brand">{{ $child->name }}</a>
                            <button
                                type="button"
                                class="text-sm text-brand"
                                @click="openAddForChild(@js((string) $child->id), @js(max(1, (int) ($child->ru_height ?? 1))))"
                            >
                                Place
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">No unplaced child assets.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <div
        x-show="confirmParent"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
        @keydown.escape.window="confirmParent = false"
    >
        <div class="fixed inset-0 bg-gray-500/75" @click="confirmParent = false"></div>
        <div class="relative w-full max-w-md bg-white dark:bg-brand-slate dark:text-white p-6 space-y-4 shadow-xl">
            <h2 class="text-lg font-medium">
                Change parent?
            </h2>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                This asset already belongs to
                <span class="font-medium" x-text="pendingParentName || 'another asset'"></span>.
                Placing it here will re-parent it under this rack.
            </p>
            @if($hasParentConfirmError)
                <x-input-error :messages="$errors->get('confirm_parent_change')" />
            @endif
            <div class="flex justify-end gap-2">
                <x-secondary-button type="button" @click="confirmParent = false">
                    Cancel
                </x-secondary-button>
                <x-primary-button type="button" @click="confirmAndSubmit()">
                    Confirm re-parent
                </x-primary-button>
            </div>
        </div>
    </div>
</div>

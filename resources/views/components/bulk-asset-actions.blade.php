@props([
    'statuses',
    'locations',
    'parents',
    'lists',
    'departments' => null,
    'currentList' => null,
    'canDelete' => false,
])

@php
    $canDelete = $canDelete || auth()->user()?->hasAnyRole(['admin', 'inventory_manager']);
    $canChangeDepartment = auth()->user()?->hasRole('admin') ?? false;
    $departments = collect($departments ?? []);
@endphp

<div
    x-cloak
    x-show="selectedCount > 0"
    x-ref="bulkPanel"
    class="sticky top-0 z-20 mb-4 max-h-screen overflow-y-auto border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 shadow-sm"
    style="max-height: 100vh"
    x-init="
        const logBulkPanel = (reason, hypothesisId) => {
            // #region agent log
            const panel = $refs.bulkPanel;
            const applyBtn = panel && [...panel.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Apply bulk update');
            const form = $refs.bulkForm;
            if (!panel) return;
            const pr = panel.getBoundingClientRect();
            const ar = applyBtn?.getBoundingClientRect();
            const cs = getComputedStyle(panel);
            const payload = {
                sessionId: '3fe58c',
                runId: 'post-fix',
                hypothesisId,
                location: 'bulk-asset-actions.blade.php:panel-measure',
                message: reason,
                data: {
                    panelOpen,
                    selectedCount,
                    viewportH: window.innerHeight,
                    docScrollH: document.documentElement.scrollHeight,
                    windowScrollY: window.scrollY,
                    panelHeight: Math.round(pr.height),
                    panelTop: Math.round(pr.top),
                    panelBottom: Math.round(pr.bottom),
                    panelOverflowY: cs.overflowY,
                    panelMaxHeight: cs.maxHeight,
                    panelPosition: cs.position,
                    formHeight: form ? Math.round(form.getBoundingClientRect().height) : null,
                    applyTop: ar ? Math.round(ar.top) : null,
                    applyBottom: ar ? Math.round(ar.bottom) : null,
                    applyInViewport: ar ? (ar.top >= 0 && ar.bottom <= window.innerHeight) : null,
                    applyBelowFold: ar ? ar.bottom > window.innerHeight : null,
                    panelTallerThanViewport: pr.height > window.innerHeight,
                },
                timestamp: Date.now(),
            };
            fetch('http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'3fe58c'},body:JSON.stringify(payload)}).catch(()=>{});
            // #endregion
        };
        $watch('panelOpen', (open) => {
            if (!open) return;
            $nextTick(() => {
                logBulkPanel('panel-opened', 'A');
                logBulkPanel('panel-opened-overflow-check', 'C');
            });
        });
        let lastScrollLog = 0;
        let lastApplyBelow = null;
        const onScroll = () => {
            if (!panelOpen || selectedCount < 1) return;
            const applyBtn = $refs.bulkPanel && [...$refs.bulkPanel.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Apply bulk update');
            const ar = applyBtn?.getBoundingClientRect();
            const below = ar ? ar.bottom > window.innerHeight : null;
            const now = Date.now();
            if (below === lastApplyBelow && now - lastScrollLog < 800) return;
            lastApplyBelow = below;
            lastScrollLog = now;
            logBulkPanel('scroll-while-panel-open', 'B');
        };
        window.addEventListener('scroll', onScroll, { passive: true });
    "
>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm">
            <span class="font-semibold" x-text="selectedCount"></span>
            <span x-text="selectedCount === 1 ? 'asset selected' : 'assets selected'"></span>
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="panelOpen = !panelOpen" x-text="panelOpen ? 'Hide bulk edit' : 'Bulk edit'"></button>
            @feature('shelf_tag_generator')
                @role('admin|inventory_manager')
                    @if($currentList)
                        <form method="GET" action="{{ route('labels.index') }}" class="inline">
                            <input type="hidden" name="asset_list_id" value="{{ $currentList->id }}">
                            <template x-for="id in selectedIds" :key="'shelf-'+id">
                                <input type="hidden" name="asset_ids[]" :value="id">
                            </template>
                            <button type="submit" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver">Labels</button>
                        </form>
                    @endif
                @endrole
            @endfeature
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="clearSelection()">Clear</button>
            @if($canDelete)
                <form
                    method="POST"
                    action="{{ route('assets.duplicates.start') }}"
                    class="inline"
                    x-show="selectedCount >= 2"
                    x-ref="mergeStartForm"
                >
                    @csrf
                    <template x-for="id in selectedIds" :key="'merge-start-'+id">
                        <input type="hidden" name="asset_ids[]" :value="id">
                    </template>
                    <button type="submit" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver">Merge selected</button>
                </form>
                <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="openDeleteConfirm()">Delete selected</button>
            @endif
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('assets.bulk-update') }}"
        class="mt-4 space-y-4 border-t border-brand-silver dark:border-brand-charcoal pt-4"
        x-show="panelOpen || pendingDelete || pendingMerge"
        x-ref="bulkForm"
        @submit="prepareSubmit($event)"
    >
        @csrf
        <input type="hidden" name="action" :value="pendingDelete ? 'delete' : (pendingMerge ? 'merge' : 'update')">
        <template x-for="id in selectedIds" :key="id">
            <input type="hidden" name="asset_ids[]" :value="id">
        </template>
        @if($currentList)
            <input type="hidden" name="current_list_id" value="{{ $currentList->id }}">
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Status</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_status" value="1" x-model="fields.update_status">
                    <span>Update status</span>
                </label>
                <select name="status" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="!fields.update_status">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Location</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_location" value="1" x-model="fields.update_location" @change="if (fields.update_location) fields.clear_location = false">
                    <span>Update location</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_location" value="1" x-model="fields.clear_location" @change="if (fields.clear_location) fields.update_location = false">
                    <span>Clear location</span>
                </label>
                <x-searchable-select
                    name="location_id"
                    id="bulk_location_id"
                    :options="$locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])"
                    nullable
                    placeholder="Select location"
                />
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Parent asset</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_parent" value="1" x-model="fields.update_parent" @change="if (fields.update_parent) fields.clear_parent = false">
                    <span>Update parent</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_parent" value="1" x-model="fields.clear_parent" @change="if (fields.clear_parent) fields.update_parent = false">
                    <span>Clear parent</span>
                </label>
                <x-searchable-select
                    name="parent_id"
                    id="bulk_parent_id"
                    :options="collect($parents ?? [])->map(fn ($parent) => is_array($parent)
                        ? $parent
                        : ['value' => $parent->id, 'label' => \App\Support\AssetSearch::optionLabel($parent)])"
                    :search-url="route('assets.search')"
                    :search-params="['parents_only' => 1]"
                    nullable
                    placeholder="Search by name, ID#, barcode…"
                />
            </fieldset>

            @if($canChangeDepartment && $departments->isNotEmpty())
                <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                    <legend class="px-1 font-medium">Department</legend>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="update_department" value="1" x-model="fields.update_department">
                        <span>Update department</span>
                    </label>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        Child assets follow the parent. Moving a child alone clears its parent link.
                    </p>
                    <select
                        name="department_id"
                        class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        :disabled="!fields.update_department"
                    >
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </fieldset>
            @endif

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Test &amp; Tag expiry</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_test_tag_expiry" value="1" x-model="fields.update_test_tag_expiry" @change="if (fields.update_test_tag_expiry) fields.clear_test_tag_expiry = false">
                    <span>Update expiry</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_test_tag_expiry" value="1" x-model="fields.clear_test_tag_expiry" @change="if (fields.clear_test_tag_expiry) fields.update_test_tag_expiry = false">
                    <span>Clear expiry</span>
                </label>
                <input type="date" name="test_tag_expiry" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="!fields.update_test_tag_expiry">
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Warranty expiry</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_warranty_expiry" value="1" x-model="fields.update_warranty_expiry" @change="if (fields.update_warranty_expiry) fields.clear_warranty_expiry = false">
                    <span>Update warranty</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_warranty_expiry" value="1" x-model="fields.clear_warranty_expiry" @change="if (fields.clear_warranty_expiry) fields.update_warranty_expiry = false">
                    <span>Clear warranty</span>
                </label>
                <input type="date" name="warranty_expiry" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="!fields.update_warranty_expiry">
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 font-medium">Notes</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_notes" value="1" x-model="fields.update_notes" @change="if (fields.update_notes) fields.clear_notes = false">
                    <span>Replace notes</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_notes" value="1" x-model="fields.clear_notes" @change="if (fields.clear_notes) fields.update_notes = false">
                    <span>Clear notes</span>
                </label>
                <textarea name="notes" rows="2" class="w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="!fields.update_notes"></textarea>
            </fieldset>

            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3 md:col-span-2">
                <legend class="px-1 font-medium">Lists</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="add_to_list_id" value="Add to list" />
                        <select id="add_to_list_id" name="add_to_list_id" class="mt-1 w-full rounded border-brand-charcoal/30 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="">—</option>
                            @foreach($lists as $listOption)
                                <option value="{{ $listOption->id }}">{{ $listOption->name }} ({{ $listOption->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    @if($currentList)
                        <label class="flex items-center gap-2 mt-6">
                            <input type="checkbox" name="remove_from_list" value="1">
                            <span>Remove from this list ({{ $currentList->name }})</span>
                        </label>
                    @endif
                </div>
            </fieldset>
        </div>

        <div class="sticky bottom-0 -mx-3 -mb-3 mt-4 flex justify-end border-t border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate px-3 py-3">
            <button type="submit" class="px-4 py-2 bg-brand text-white text-sm">Apply bulk update</button>
        </div>
    </form>
</div>

@if($canDelete)
    <div
        x-cloak
        x-show="deleteConfirmOpen"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-black/50" @click="cancelDelete()"></div>
        <div class="relative w-full max-w-md max-h-[calc(100vh-3rem)] overflow-y-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-6 space-y-4 shadow-xl">
            <h3 class="text-lg font-semibold">Confirm delete</h3>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                Permanently delete
                <span class="font-semibold text-brand-black dark:text-white" x-text="selectedCount"></span>
                <span x-text="selectedCount === 1 ? 'asset' : 'assets'"></span>?
                This cannot be undone.
            </p>
            <div class="flex justify-end gap-2">
                <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="cancelDelete()">Cancel</button>
                <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="confirmDelete()">Delete permanently</button>
            </div>
        </div>
    </div>

@endif

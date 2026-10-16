@props([
    'parents',
])

<div
    x-cloak
    x-show="selectedCount > 0"
    class="sticky top-0 z-20 mb-4 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 shadow-sm"
>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm">
            <span class="font-semibold" x-text="selectedCount"></span>
            <span x-text="selectedCount === 1 ? 'location selected' : 'locations selected'"></span>
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="panelOpen = !panelOpen" x-text="panelOpen ? 'Hide bulk edit' : 'Bulk edit'"></button>
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="submitMerge()">Merge same names</button>
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="clearSelection()">Clear</button>
            <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="openDeleteConfirm()">Delete selected</button>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('locations.bulk-update') }}"
        class="mt-4 space-y-4 border-t border-brand-silver dark:border-brand-charcoal pt-4"
        x-show="panelOpen || pendingDelete || pendingMerge"
        x-ref="bulkForm"
        @submit="prepareSubmit($event)"
    >
        @csrf
        <input type="hidden" name="action" :value="pendingDelete ? 'delete' : (pendingMerge ? 'merge_duplicates' : 'update')">
        <template x-for="id in selectedIds" :key="id">
            <input type="hidden" name="location_ids[]" :value="id">
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" x-show="!pendingDelete && !pendingMerge">
            <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3 md:col-span-2">
                <legend class="px-1 font-medium">Parent location</legend>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="update_parent" value="1" x-model="fields.update_parent" @change="if (fields.update_parent) fields.clear_parent = false">
                    <span>Assign parent</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="clear_parent" value="1" x-model="fields.clear_parent" @change="if (fields.clear_parent) fields.update_parent = false">
                    <span>Clear parent</span>
                </label>
                <div x-bind:class="!fields.update_parent ? 'pointer-events-none opacity-50' : ''">
                    <x-searchable-select
                        name="parent_id"
                        id="bulk_location_parent_id"
                        :options="$parents->map(fn ($parent) => ['value' => $parent->id, 'label' => $parent->breadcrumb()])"
                        nullable
                    />
                </div>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Use “Merge same names” to combine selected locations that share a name. Assets on duplicates move to the kept location.
                </p>
            </fieldset>
        </div>

        <div class="flex justify-end" x-show="!pendingDelete && !pendingMerge">
            <button type="submit" class="px-4 py-2 bg-brand text-white text-sm">Apply bulk update</button>
        </div>
    </form>
</div>

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
            <span x-text="selectedCount === 1 ? 'location' : 'locations'"></span>?
            Child locations become top-level and assets at these locations lose their location.
        </p>
        <div class="flex justify-end gap-2">
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="cancelDelete()">Cancel</button>
            <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="confirmDelete()">Delete permanently</button>
        </div>
    </div>
</div>

@props([
    'statuses',
    'locations',
    'parents',
    'lists',
    'currentList' => null,
    'canDelete' => false,
])

@php
    $canDelete = $canDelete || auth()->user()?->hasAnyRole(['admin', 'inventory_manager']);
@endphp

<div
    x-cloak
    x-show="selectedCount > 0"
    class="sticky top-0 z-20 mb-4 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-black p-3 shadow-sm"
>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm">
            <span class="font-semibold" x-text="selectedCount"></span>
            <span x-text="selectedCount === 1 ? 'asset selected' : 'assets selected'"></span>
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="panelOpen = !panelOpen" x-text="panelOpen ? 'Hide bulk edit' : 'Bulk edit'"></button>
            <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="clearSelection()">Clear</button>
            @if($canDelete)
                <button type="button" class="px-3 py-2 text-sm bg-red-700 text-white" @click="openDeleteConfirm()">Delete selected</button>
            @endif
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('assets.bulk-update') }}"
        class="mt-4 space-y-4 border-t border-brand-silver dark:border-brand-charcoal pt-4"
        x-show="panelOpen"
        x-ref="bulkForm"
        @submit="prepareSubmit($event)"
    >
        @csrf
        <input type="hidden" name="action" :value="pendingDelete ? 'delete' : 'update'">
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
                    :options="$parents->map(fn ($parent) => ['value' => $parent->id, 'label' => $parent->name])"
                    nullable
                    placeholder="Select parent"
                />
            </fieldset>

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

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-brand text-white text-sm">Apply bulk update</button>
        </div>
    </form>
</div>

@if($canDelete)
    <div
        x-cloak
        x-show="deleteConfirmOpen"
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-black/50" @click="cancelDelete()"></div>
        <div class="relative mx-auto max-w-md bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal p-6 space-y-4">
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

@props([
    'departments',
])

@php
    $departments = collect($departments ?? []);
@endphp

@if($departments->isNotEmpty())
    <div
        x-cloak
        x-show="selectedCount > 0"
        class="sticky top-0 z-20 mb-4 border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-3 shadow-sm"
    >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm">
                <span class="font-semibold" x-text="selectedCount"></span>
                <span x-text="selectedCount === 1 ? 'list selected' : 'lists selected'"></span>
            </p>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="panelOpen = !panelOpen" x-text="panelOpen ? 'Hide bulk edit' : 'Bulk edit'"></button>
                <button type="button" class="px-3 py-2 text-sm border border-brand-charcoal dark:border-brand-silver" @click="clearSelection()">Clear</button>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('lists.bulk-update') }}"
            class="mt-4 space-y-4 border-t border-brand-silver dark:border-brand-charcoal pt-4"
            x-show="panelOpen"
            x-ref="bulkForm"
            @submit="prepareSubmit($event)"
        >
            @csrf
            <input type="hidden" name="action" value="update">
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="asset_list_ids[]" :value="id">
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <fieldset class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                    <legend class="px-1 font-medium">Department</legend>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="update_department" value="1" x-model="fields.update_department">
                        <span>Update department</span>
                    </label>
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
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-brand text-white text-sm">Apply bulk update</button>
            </div>
        </form>
    </div>
@endif

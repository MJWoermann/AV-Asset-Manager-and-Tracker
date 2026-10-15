<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Export columns</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4">
        <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
            Choose which asset fields are included when administrators and inventory managers export the asset register or a list.
            Location, parent, and each custom field set can be selected independently.
        </p>

        <form method="POST" action="{{ route('admin.export-columns.update') }}" class="space-y-6 bg-white dark:bg-brand-slate border p-4">
            @csrf
            @method('PUT')

            <div class="grid gap-6 md:grid-cols-2">
                <fieldset>
                    <legend class="text-sm font-medium mb-2">Asset exports</legend>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-3">Used by the Export button on Assets.</p>
                    @include('admin.export-columns.partials.column-picker', [
                        'inputName' => 'asset_columns',
                        'selected' => $assetColumns,
                        'availableColumns' => $availableColumns,
                        'customFieldSets' => $customFieldSets,
                    ])
                    <x-input-error class="mt-2" :messages="$errors->get('asset_columns')" />
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-medium mb-2">List exports</legend>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-3">Used by the Export button on a list.</p>
                    @include('admin.export-columns.partials.column-picker', [
                        'inputName' => 'list_columns',
                        'selected' => $listColumns,
                        'availableColumns' => $availableColumns,
                        'customFieldSets' => $customFieldSets,
                    ])
                    <x-input-error class="mt-2" :messages="$errors->get('list_columns')" />
                </fieldset>
            </div>

            <x-primary-button>Save export columns</x-primary-button>
        </form>
    </div>
</x-app-layout>

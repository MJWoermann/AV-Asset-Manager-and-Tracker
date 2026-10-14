<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Export columns</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4">
        <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
            Choose which asset fields are included when administrators and inventory managers export the asset register or a list.
        </p>

        <form method="POST" action="{{ route('admin.export-columns.update') }}" class="space-y-6 bg-white dark:bg-brand-slate border p-4">
            @csrf
            @method('PUT')

            <div class="grid gap-6 md:grid-cols-2">
                <fieldset>
                    <legend class="text-sm font-medium mb-2">Asset exports</legend>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-3">Used by the Export button on Assets.</p>
                    <div class="space-y-1 max-h-96 overflow-y-auto">
                        @foreach($availableColumns as $key => $label)
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="asset_columns[]"
                                    value="{{ $key }}"
                                    class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                                    @checked(in_array($key, old('asset_columns', $assetColumns), true))
                                    @if($key === 'name') disabled @endif
                                >
                                <span>{{ $label }}</span>
                            </label>
                            @if($key === 'name')
                                <input type="hidden" name="asset_columns[]" value="name">
                            @endif
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('asset_columns')" />
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-medium mb-2">List exports</legend>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-3">Used by the Export button on a list.</p>
                    <div class="space-y-1 max-h-96 overflow-y-auto">
                        @foreach($availableColumns as $key => $label)
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="list_columns[]"
                                    value="{{ $key }}"
                                    class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                                    @checked(in_array($key, old('list_columns', $listColumns), true))
                                    @if($key === 'name') disabled @endif
                                >
                                <span>{{ $label }}</span>
                            </label>
                            @if($key === 'name')
                                <input type="hidden" name="list_columns[]" value="name">
                            @endif
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('list_columns')" />
                </fieldset>
            </div>

            <x-primary-button>Save export columns</x-primary-button>
        </form>
    </div>
</x-app-layout>

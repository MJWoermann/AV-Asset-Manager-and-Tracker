<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Print shelf tags</h2>
            <a href="{{ route('shelf-tags.layouts.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Manage layouts</a>
        </div>
    </x-slot>

    <div
        class="max-w-3xl mx-auto px-4"
        x-data="{
            mode: @js(old('mode', $mode)),
            idsText: @js(collect(old('asset_ids', $selectedAssetIds))->implode("\n")),
            get assetIds() {
                return String(this.idsText || '')
                    .split(/[\s,;]+/)
                    .map((v) => v.trim())
                    .filter(Boolean);
            }
        }"
    >
        @if ($errors->any())
            <div class="mb-4 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($layouts->isEmpty())
            <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
                Create a <a href="{{ route('shelf-tags.layouts.create') }}" class="text-brand underline">shelf tag layout</a> before printing.
            </p>
        @endif

        <form method="POST" action="{{ route('shelf-tags.print.preview') }}" class="space-y-4 bg-white dark:bg-brand-slate border p-4">
            @csrf

            <div>
                <x-input-label for="shelf_tag_layout_id" value="Layout" />
                <select id="shelf_tag_layout_id" name="shelf_tag_layout_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    <option value="">Select layout…</option>
                    @foreach($layouts as $layout)
                        <option value="{{ $layout->id }}" @selected(old('shelf_tag_layout_id', $selectedLayoutId) == $layout->id)>
                            {{ $layout->name }} ({{ $layout->width_mm }}×{{ $layout->height_mm }} mm)
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('shelf_tag_layout_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="asset_list_id" value="List" />
                <select id="asset_list_id" name="asset_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    <option value="">Select list…</option>
                    @foreach($lists as $list)
                        <option value="{{ $list->id }}" @selected(old('asset_list_id', $selectedListId) == $list->id)>
                            {{ $list->name }} ({{ $list->type }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('asset_list_id')" class="mt-1" />
            </div>

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium">Assets</legend>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="mode" value="all" class="text-brand" x-model="mode">
                    All assets on the list
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="mode" value="selected" class="text-brand" x-model="mode">
                    Selected assets only
                </label>
            </fieldset>

            <div x-show="mode === 'selected'" x-cloak class="space-y-2">
                <x-input-label value="Asset IDs" />
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">Prefer selecting checkboxes on a list, or paste IDs (one per line).</p>
                <textarea
                    rows="6"
                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white font-mono text-sm"
                    x-model="idsText"
                ></textarea>
                <template x-for="id in assetIds" :key="id">
                    <input type="hidden" name="asset_ids[]" :value="id">
                </template>
                <x-input-error :messages="$errors->get('asset_ids')" class="mt-1" />
            </div>

            <p class="text-xs text-brand-charcoal dark:text-brand-silver">Maximum {{ \App\Services\ShelfTagRenderService::MAX_BATCH }} labels per batch.</p>

            <x-primary-button>Preview labels</x-primary-button>
        </form>
    </div>
</x-app-layout>

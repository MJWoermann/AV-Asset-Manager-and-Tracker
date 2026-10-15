@props([
    'categories' => [],
    'linkableType' => null,
    'linkableId' => null,
    'idPrefix' => 'bulk',
])

@php
    $categories = $categories ?: \App\Enums\FileCategory::options();
    $defaultCategory = array_key_first($categories) ?: \App\Enums\FileCategory::Other->value;
    $maxFiles = (int) config('attachments.max_bulk_files', 20);
    $accept = collect(config('attachments.allowed_mimes', []))
        ->map(fn (string $mime) => '.'.$mime)
        ->implode(',');
@endphp

<div
    class="space-y-3"
    x-data="{
        items: [],
        defaultCategory: @js($defaultCategory),
        maxFiles: @js($maxFiles),
        applyCategory: @js($defaultCategory),
        onPick(event) {
            const files = Array.from(event.target.files || []);
            if (files.length > this.maxFiles) {
                alert('You can upload at most ' + this.maxFiles + ' files at once.');
                this.resetPicker();
                return;
            }
            this.items = files.map((file) => ({
                name: file.name,
                title: this.titleFromFilename(file.name),
                category: this.defaultCategory,
                note: '',
            }));
        },
        titleFromFilename(name) {
            const trimmed = String(name || '').trim();
            const withoutExt = trimmed.replace(/\.[^.]+$/, '');
            return (withoutExt || trimmed).slice(0, 255);
        },
        removeAt(index) {
            this.items.splice(index, 1);
            const input = this.$refs.picker;
            if (! input || ! input.files) {
                return;
            }
            const dt = new DataTransfer();
            Array.from(input.files).forEach((file, i) => {
                if (i !== index) {
                    dt.items.add(file);
                }
            });
            input.files = dt.files;
            if (this.items.length === 0) {
                input.value = '';
            }
        },
        resetPicker() {
            this.items = [];
            if (this.$refs.picker) {
                this.$refs.picker.value = '';
            }
        },
        applyCategoryToAll() {
            this.items = this.items.map((item) => ({
                ...item,
                category: this.applyCategory,
            }));
        },
        prepareSubmit(event) {
            if (this.items.length === 0) {
                event.preventDefault();
                alert('Choose at least one file to upload.');
            }
        }
    }"
>
    <form
        method="POST"
        action="{{ route('files.bulk-upload') }}"
        enctype="multipart/form-data"
        class="space-y-3"
        @submit="prepareSubmit($event)"
    >
        @csrf
        @if($linkableType !== null)
            <input type="hidden" name="linkable_type" value="{{ $linkableType }}">
            <input type="hidden" name="linkable_id" value="{{ $linkableId }}">
        @endif

        <div>
            <x-input-label :for="$idPrefix.'_files'" value="Files" />
            <input
                id="{{ $idPrefix }}_files"
                x-ref="picker"
                type="file"
                name="files[]"
                multiple
                accept="{{ $accept }}"
                class="mt-1 block w-full text-sm"
                required
                @change="onPick($event)"
            >
            <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                Select up to {{ $maxFiles }} files (jpg, png, webp, pdf, docx, xlsx, csv — max 25MB each). Set a title and category for each file below before uploading.
            </p>
            <x-input-error :messages="$errors->get('files')" class="mt-1" />
            <x-input-error :messages="$errors->get('files.*')" class="mt-1" />
        </div>

        <div x-show="items.length" x-cloak class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                <div class="flex-1 min-w-[10rem]">
                    <x-input-label :for="$idPrefix.'_apply_category'" value="Set category for all" />
                    <select
                        id="{{ $idPrefix }}_apply_category"
                        x-model="applyCategory"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                    >
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button
                    type="button"
                    class="px-3 py-2 text-sm border border-brand-charcoal dark:border-white"
                    @click="applyCategoryToAll()"
                >Apply to all</button>
            </div>

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index + '-' + item.name">
                    <div class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-mono text-brand-charcoal dark:text-brand-silver break-all" x-text="item.name"></p>
                            <button
                                type="button"
                                class="text-sm text-red-600 dark:text-red-400 shrink-0"
                                @click="removeAt(index)"
                            >Remove</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium" :for="'{{ $idPrefix }}_title_' + index">Title</label>
                                <input
                                    type="text"
                                    :id="'{{ $idPrefix }}_title_' + index"
                                    :name="'titles[' + index + ']'"
                                    x-model="item.title"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white shadow-sm focus:border-brand focus:ring-brand"
                                    required
                                    maxlength="255"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium" :for="'{{ $idPrefix }}_category_' + index">Category</label>
                                <select
                                    :id="'{{ $idPrefix }}_category_' + index"
                                    :name="'categories[' + index + ']'"
                                    x-model="item.category"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                    required
                                >
                                    @foreach($categories as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium" :for="'{{ $idPrefix }}_note_' + index">Note (optional)</label>
                            <input
                                type="text"
                                :id="'{{ $idPrefix }}_note_' + index"
                                :name="'notes[' + index + ']'"
                                x-model="item.note"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white shadow-sm focus:border-brand focus:ring-brand"
                                maxlength="255"
                            >
                        </div>
                    </div>
                </template>
            </div>

            <x-input-error :messages="$errors->get('titles')" class="mt-1" />
            <x-input-error :messages="$errors->get('titles.*')" class="mt-1" />
            <x-input-error :messages="$errors->get('categories')" class="mt-1" />
            <x-input-error :messages="$errors->get('categories.*')" class="mt-1" />

            <x-primary-button x-bind:disabled="items.length === 0">
                <span x-text="items.length === 1 ? 'Upload file' : ('Upload ' + items.length + ' files')"></span>
            </x-primary-button>
        </div>
    </form>
</div>

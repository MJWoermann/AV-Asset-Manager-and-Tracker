@props([
    'linkableType',
    'linkableId',
    'rows' => [],
    'canMutate' => true,
    'categories' => [],
])

@php
    $categories = $categories ?: \App\Enums\FileCategory::options();
    $composerShouldOpen = $canMutate
        && $errors->any()
        && (string) old('linkable_type') === (string) $linkableType
        && (string) old('linkable_id') === (string) $linkableId;
    $initialTab = old('external_url') !== null ? 'url' : 'upload';
@endphp

<div class="space-y-4" x-data="{
    composerOpen: @js($composerShouldOpen),
    tab: @js($initialTab),
    query: '',
    results: [],
    selectedId: null,
    selectedTitle: '',
    searching: false,
    previewOpen: false,
    previewUrl: '',
    previewDownloadUrl: '',
    previewKind: 'image',
    previewTitle: '',
    async search() {
        this.searching = true;
        try {
            const response = await fetch(@js(route('files.search')) + '?q=' + encodeURIComponent(this.query), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await response.json();
            this.results = json.data || [];
        } finally {
            this.searching = false;
        }
    },
    pick(file) {
        this.selectedId = file.id;
        this.selectedTitle = file.title;
        this.results = [];
        this.query = file.title;
    },
    openPreview(url, downloadUrl, kind, title) {
        this.previewUrl = url;
        this.previewDownloadUrl = downloadUrl;
        this.previewKind = kind;
        this.previewTitle = title;
        this.previewOpen = true;
    },
    closePreview() {
        this.previewOpen = false;
        this.previewUrl = '';
        this.previewDownloadUrl = '';
    }
}">
    <div class="relative bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
        @if($canMutate)
            <button
                type="button"
                class="absolute top-1.5 right-1.5 z-10 inline-flex items-center justify-center p-1 border border-brand-charcoal dark:border-brand-silver bg-white dark:bg-brand-slate text-brand-charcoal dark:text-brand-silver hover:border-brand hover:text-brand"
                title="Add file"
                aria-label="Add file"
                :aria-expanded="composerOpen.toString()"
                @click="composerOpen = !composerOpen"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
            </button>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">Category</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Note</th>
                        <th class="px-3 py-2 {{ $canMutate ? 'pr-10' : '' }}"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $file = $row['file'];
                            $inherited = $row['inherited'] ?? false;
                            $linkId = $row['link_id'] ?? null;
                            $note = $row['note'] ?? null;
                        @endphp
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal align-top">
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    @if($file->hasThumbnail())
                                        <img
                                            src="{{ route('files.thumbnail', $file) }}"
                                            alt=""
                                            class="h-10 w-10 object-cover border border-brand-silver dark:border-brand-charcoal shrink-0"
                                        >
                                    @elseif($file->isPreviewableImage() || $file->isPreviewablePdf())
                                        <span class="inline-flex h-10 w-10 items-center justify-center text-xs font-medium bg-brand-silver/50 dark:bg-brand-charcoal border border-brand-silver dark:border-brand-charcoal shrink-0">
                                            {{ $file->isPreviewablePdf() ? 'PDF' : 'IMG' }}
                                        </span>
                                    @endif
                                    <div>
                                        <div class="font-medium">{{ $file->title }}</div>
                                        @if($inherited)
                                            <div class="text-xs text-brand-charcoal dark:text-brand-silver">Via item type</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">{{ $file->category->label() }}</td>
                            <td class="px-3 py-2">
                                @if($file->isUrl())
                                    URL
                                @else
                                    {{ strtoupper(pathinfo($file->original_filename ?? '', PATHINFO_EXTENSION) ?: 'file') }}
                                @endif
                            </td>
                            <td class="px-3 py-2 text-brand-charcoal dark:text-brand-silver">{{ $note ?: '—' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                @if($file->isPreviewable())
                                    <button
                                        type="button"
                                        class="text-brand text-sm"
                                        @click="openPreview(@js(route('files.preview', $file)), @js(route('files.download', $file)), @js($file->isPreviewablePdf() ? 'pdf' : 'image'), @js($file->title))"
                                    >Preview</button>
                                @endif
                                @if($file->isUrl())
                                    <a href="{{ $file->external_url }}" target="_blank" rel="noopener" class="text-brand text-sm {{ $file->isPreviewable() ? 'ms-2' : '' }}">Open</a>
                                @else
                                    <a href="{{ route('files.download', $file) }}" class="text-brand text-sm {{ $file->isPreviewable() ? 'ms-2' : '' }}">Download</a>
                                @endif
                                @if($canMutate && ! $inherited && $linkId)
                                    <form method="POST" action="{{ route('files.unlink', $linkId) }}" class="inline ms-2" onsubmit="return confirm('Unlink this file?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 dark:text-red-400">Unlink</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No files attached yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canMutate)
        <div
            x-show="composerOpen"
            x-cloak
            class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap gap-2 text-sm" role="tablist">
                    <button type="button" @click="tab = 'upload'" :class="tab === 'upload' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">Upload</button>
                    <button type="button" @click="tab = 'url'" :class="tab === 'url' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">URL</button>
                    <button type="button" @click="tab = 'library'" :class="tab === 'library' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">From library</button>
                </div>
                <button type="button" class="text-sm text-brand-charcoal dark:text-brand-silver" @click="composerOpen = false">Close</button>
            </div>

            <div x-show="tab === 'upload'">
                <x-file-bulk-upload
                    :categories="$categories"
                    :linkable-type="$linkableType"
                    :linkable-id="$linkableId"
                    :id-prefix="'attach-'.$linkableType.'-'.$linkableId"
                />
            </div>

            <form x-show="tab === 'url'" method="POST" action="{{ route('files.url') }}" class="space-y-3" style="display: none;">
                @csrf
                <input type="hidden" name="linkable_type" value="{{ $linkableType }}">
                <input type="hidden" name="linkable_id" value="{{ $linkableId }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="url_title" value="Title" />
                        <x-text-input id="url_title" name="title" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="url_category" value="Category" />
                        <select id="url_category" name="category" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="external_url" value="URL" />
                    <x-text-input id="external_url" name="external_url" type="url" class="mt-1 block w-full" required placeholder="https://" />
                </div>
                <div>
                    <x-input-label for="url_note" value="Note (optional)" />
                    <x-text-input id="url_note" name="note" class="mt-1 block w-full" />
                </div>
                <x-primary-button>Attach URL</x-primary-button>
            </form>

            <form
                x-show="tab === 'library'"
                method="POST"
                :action="selectedId ? @js(route('files.link', ['file' => 0])).replace(/\/0\/link$/, '/' + selectedId + '/link') : '#'"
                class="space-y-3"
                style="display: none;"
                @submit="if (!selectedId) { $event.preventDefault(); }"
            >
                @csrf
                <input type="hidden" name="linkable_type" value="{{ $linkableType }}">
                <input type="hidden" name="linkable_id" value="{{ $linkableId }}">
                <div class="relative">
                    <x-input-label for="library_search" value="Search library" />
                    <x-text-input id="library_search" class="mt-1 block w-full" x-model="query" @input.debounce.300ms="search()" placeholder="Search by title…" autocomplete="off" />
                    <ul x-show="results.length" class="absolute z-10 mt-1 w-full border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate max-h-48 overflow-auto text-sm">
                        <template x-for="file in results" :key="file.id">
                            <li>
                                <button type="button" class="w-full text-left px-3 py-2 hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal" @click="pick(file)">
                                    <span x-text="file.title"></span>
                                    <span class="text-brand-charcoal dark:text-brand-silver" x-text="' — ' + file.category"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <p x-show="selectedId" class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Selected: <span x-text="selectedTitle"></span></p>
                </div>
                <div>
                    <x-input-label for="library_note" value="Note (optional)" />
                    <x-text-input id="library_note" name="note" class="mt-1 block w-full" />
                </div>
                <x-primary-button x-bind:disabled="!selectedId">Link file</x-primary-button>
            </form>
        </div>
    @endif

    @include('files.partials.preview-modal')
</div>

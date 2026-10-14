@props([
    'linkableType',
    'linkableId',
    'rows' => [],
    'canMutate' => true,
    'categories' => [],
])

@php
    $categories = $categories ?: \App\Enums\FileCategory::options();
@endphp

<div class="space-y-4" x-data="{
    tab: 'upload',
    query: '',
    results: [],
    selectedId: null,
    selectedTitle: '',
    searching: false,
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
    }
}">
    <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
        <table class="min-w-full text-sm">
            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                <tr>
                    <th class="px-3 py-2">Title</th>
                    <th class="px-3 py-2">Category</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Note</th>
                    <th class="px-3 py-2"></th>
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
                            <div class="font-medium">{{ $file->title }}</div>
                            @if($inherited)
                                <div class="text-xs text-brand-charcoal dark:text-brand-silver">Via item type</div>
                            @endif
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
                            @if($file->isUrl())
                                <a href="{{ $file->external_url }}" target="_blank" rel="noopener" class="text-brand text-sm">Open</a>
                            @else
                                <a href="{{ route('files.download', $file) }}" class="text-brand text-sm">Download</a>
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

    @if($canMutate)
        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-4">
            <div class="flex flex-wrap gap-2 text-sm" role="tablist">
                <button type="button" @click="tab = 'upload'" :class="tab === 'upload' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">Upload</button>
                <button type="button" @click="tab = 'url'" :class="tab === 'url' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">URL</button>
                <button type="button" @click="tab = 'library'" :class="tab === 'library' ? 'border-brand text-brand' : 'border-transparent text-brand-charcoal dark:text-brand-silver'" class="px-3 py-1 border-b-2">From library</button>
            </div>

            <form x-show="tab === 'upload'" method="POST" action="{{ route('files.upload') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="linkable_type" value="{{ $linkableType }}">
                <input type="hidden" name="linkable_id" value="{{ $linkableId }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="upload_title" value="Title" />
                        <x-text-input id="upload_title" name="title" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="upload_category" value="Category" />
                        <select id="upload_category" name="category" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="upload_file" value="File" />
                    <input id="upload_file" type="file" name="file" class="mt-1 block w-full text-sm" required>
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">jpg, png, webp, pdf, docx, xlsx, csv — max 25MB</p>
                </div>
                <div>
                    <x-input-label for="upload_note" value="Note (optional)" />
                    <x-text-input id="upload_note" name="note" class="mt-1 block w-full" />
                </div>
                <x-primary-button>Upload</x-primary-button>
            </form>

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
</div>

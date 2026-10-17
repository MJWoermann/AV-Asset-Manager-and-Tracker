@php
    $bulkUploadHasErrors = $errors->hasAny(['files', 'titles', 'categories', 'notes'])
        || collect($errors->keys())->contains(function (string $key): bool {
            return str_starts_with($key, 'files.')
                || str_starts_with($key, 'titles.')
                || str_starts_with($key, 'categories.')
                || str_starts_with($key, 'notes.');
        });
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">File library</h2>
            <button
                type="button"
                x-data
                class="px-3 py-2 bg-brand text-white text-sm"
                @click="$dispatch('toggle-bulk-upload')"
            >Bulk upload</button>
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
        x-data="{
            bulkOpen: @js($bulkUploadHasErrors),
            previewOpen: false,
            previewUrl: '',
            previewDownloadUrl: '',
            previewKind: 'image',
            previewTitle: '',
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
        }"
        @toggle-bulk-upload.window="bulkOpen = !bulkOpen"
    >
        <div
            x-show="bulkOpen"
            x-cloak
            class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-3"
        >
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-semibold text-sm">Bulk upload</h3>
                <button type="button" class="text-sm text-brand-charcoal dark:text-brand-silver" @click="bulkOpen = false">Close</button>
            </div>
            <x-file-bulk-upload :categories="$categories" id-prefix="library-bulk" />
        </div>

        <form method="GET" class="flex flex-col sm:flex-row gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
            <div class="flex-1">
                <x-input-label for="q" value="Search" />
                <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Title or filename" />
            </div>
            <div class="min-w-[12rem]">
                <x-input-label for="category" value="Category" />
                <x-searchable-select
                    name="category"
                    id="category"
                    :options="collect($categories)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()"
                    :selected="request('category')"
                    placeholder="All"
                    nullable
                    auto-submit
                    aria-label="Filter by category"
                />
            </div>
            <div class="flex items-end gap-2 flex-wrap">
                <x-primary-button>Search</x-primary-button>
                <x-clear-filters
                    :href="route('files.index')"
                    :active="filled(request('q')) || filled(request('category'))"
                />
            </div>
        </form>

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">Category</th>
                        <th class="px-3 py-2">Source</th>
                        <th class="px-3 py-2">Links</th>
                        <th class="px-3 py-2">Uploaded by</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $file)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
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
                                        <a href="{{ route('files.show', $file) }}" class="font-medium text-brand hover:underline">{{ $file->title }}</a>
                                        @if($file->original_filename)
                                            <div class="text-xs text-brand-charcoal dark:text-brand-silver font-mono">{{ $file->original_filename }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">{{ $file->category->label() }}</td>
                            <td class="px-3 py-2">{{ $file->source_type->label() }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('files.show', $file) }}" class="text-brand hover:underline">
                                    {{ $file->links_count }}{{ $file->links_count === 1 ? ' link' : ' links' }}
                                </a>
                            </td>
                            <td class="px-3 py-2">{{ $file->uploader?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                @if($file->isPreviewable())
                                    <button
                                        type="button"
                                        class="text-brand"
                                        @click="openPreview(@js(route('files.preview', $file)), @js(route('files.download', $file)), @js($file->isPreviewablePdf() ? 'pdf' : 'image'), @js($file->title))"
                                    >Preview</button>
                                @endif
                                @if($file->isUrl())
                                    <a href="{{ $file->external_url }}" target="_blank" rel="noopener" class="text-brand {{ $file->isPreviewable() ? 'ms-2' : '' }}">Open</a>
                                @else
                                    <a href="{{ route('files.download', $file) }}" class="text-brand {{ $file->isPreviewable() ? 'ms-2' : '' }}">Download</a>
                                @endif
                                <form method="POST" action="{{ route('files.destroy', $file) }}" class="inline ms-2" onsubmit="return confirm('Delete this file from the library? This removes all links.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No files in the library yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $files->links() }}</div>

        @include('files.partials.preview-modal')
    </div>
</x-app-layout>

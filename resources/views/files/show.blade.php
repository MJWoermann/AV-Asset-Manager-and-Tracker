@php
    $sizeLabel = $file->size_bytes !== null
        ? number_format($file->size_bytes / 1024, $file->size_bytes >= 1024 * 100 ? 0 : 1).' KB'
        : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">{{ $file->title }}</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver mt-1">
                    <a href="{{ route('files.index') }}" class="text-brand hover:underline">Back to library</a>
                </p>
            </div>
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
        x-data="{
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
    >
        <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    @if($file->hasThumbnail())
                        <img
                            src="{{ route('files.thumbnail', $file) }}"
                            alt=""
                            class="h-16 w-16 object-cover border border-brand-silver dark:border-brand-charcoal shrink-0"
                        >
                    @endif
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm min-w-0">
                        <div>
                            <dt class="text-brand-charcoal dark:text-brand-silver">Category</dt>
                            <dd>{{ $file->category->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-brand-charcoal dark:text-brand-silver">Source</dt>
                            <dd>{{ $file->source_type->label() }}</dd>
                        </div>
                        @if($file->mime_type)
                            <div>
                                <dt class="text-brand-charcoal dark:text-brand-silver">MIME</dt>
                                <dd class="font-mono text-xs">{{ $file->mime_type }}</dd>
                            </div>
                        @endif
                        @if($sizeLabel)
                            <div>
                                <dt class="text-brand-charcoal dark:text-brand-silver">Size</dt>
                                <dd>{{ $sizeLabel }}</dd>
                            </div>
                        @endif
                        @if($file->original_filename)
                            <div class="sm:col-span-2">
                                <dt class="text-brand-charcoal dark:text-brand-silver">Filename</dt>
                                <dd class="font-mono text-xs break-all">{{ $file->original_filename }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-brand-charcoal dark:text-brand-silver">Uploaded by</dt>
                            <dd>{{ $file->uploader?->name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    @if($file->isPreviewable())
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 text-sm border border-brand text-brand"
                            @click="openPreview(@js(route('files.preview', $file)), @js(route('files.download', $file)), @js($file->isPreviewablePdf() ? 'pdf' : 'image'), @js($file->title))"
                        >Preview</button>
                    @endif
                    @if($file->isUrl())
                        <a href="{{ $file->external_url }}" target="_blank" rel="noopener" class="inline-flex items-center px-3 py-1.5 text-sm border border-brand text-brand">Open URL</a>
                    @else
                        <a href="{{ route('files.download', $file) }}" class="inline-flex items-center px-3 py-1.5 text-sm border border-brand text-brand">Download</a>
                    @endif
                    <form method="POST" action="{{ route('files.destroy', $file) }}" onsubmit="return confirm('Delete this file from the library? This removes all links.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm border border-red-600 text-red-600 dark:border-red-400 dark:text-red-400">Delete</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <div class="px-3 py-2 border-b border-brand-silver dark:border-brand-charcoal">
                <h3 class="font-medium text-sm">Linked items</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Note</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($links as $link)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2">{{ $link->typeLabel() }}</td>
                            <td class="px-3 py-2">{{ $link->displayName() }}</td>
                            <td class="px-3 py-2 text-brand-charcoal dark:text-brand-silver">{{ $link->note ?: '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                @if($url = $link->destinationUrl())
                                    <a href="{{ $url }}" class="text-brand">View</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">Not linked to any records</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('files.partials.preview-modal')
    </div>
</x-app-layout>

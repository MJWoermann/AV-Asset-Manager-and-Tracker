<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">File library</h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4">
            <div class="flex-1">
                <x-input-label for="q" value="Search" />
                <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Title or filename" />
            </div>
            <div>
                <x-input-label for="category" value="Category" />
                <select id="category" name="category" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="">All</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <x-primary-button>Filter</x-primary-button>
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
                                <div class="font-medium">{{ $file->title }}</div>
                                @if($file->original_filename)
                                    <div class="text-xs text-brand-charcoal dark:text-brand-silver font-mono">{{ $file->original_filename }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $file->category->label() }}</td>
                            <td class="px-3 py-2">{{ $file->source_type->label() }}</td>
                            <td class="px-3 py-2">{{ $file->links_count }}</td>
                            <td class="px-3 py-2">{{ $file->uploader?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                @if($file->isUrl())
                                    <a href="{{ $file->external_url }}" target="_blank" rel="noopener" class="text-brand">Open</a>
                                @else
                                    <a href="{{ route('files.download', $file) }}" class="text-brand">Download</a>
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
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Shelf tag layouts</h2>
            <div class="flex gap-2">
                <a href="{{ route('shelf-tags.print.create') }}" class="px-3 py-2 border text-sm">Print</a>
                <a href="{{ route('shelf-tags.layouts.create') }}" class="px-3 py-2 bg-brand text-white text-sm">New layout</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 space-y-3">
        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        @forelse($layouts as $layout)
            <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-center">
                <div class="min-w-0">
                    <div class="font-medium">{{ $layout->name }}</div>
                    <div class="text-xs text-brand-charcoal dark:text-brand-silver">
                        {{ $layout->paper_preset }}
                        · {{ rtrim(rtrim(number_format($layout->width_mm, 2, '.', ''), '0'), '.') }} × {{ rtrim(rtrim(number_format($layout->height_mm, 2, '.', ''), '0'), '.') }} mm
                        · {{ count($layout->elements ?? []) }} elements
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('shelf-tags.layouts.edit', $layout) }}" class="text-brand">Edit</a>
                    <form method="POST" action="{{ route('shelf-tags.layouts.destroy', $layout) }}" onsubmit="return confirm('Delete this layout?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No shelf tag layouts yet. Create one to start printing.</p>
        @endforelse
    </div>
</x-app-layout>

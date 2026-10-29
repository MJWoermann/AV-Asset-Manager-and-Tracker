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
            <a href="{{ route('labels.layouts.edit', $layout) }}" data-history-roundtrip class="text-brand">Edit</a>
            <form method="POST" action="{{ route('labels.layouts.duplicate', $layout) }}">
                @csrf
                <button type="submit" class="text-brand">Duplicate</button>
            </form>
            <form method="POST" action="{{ route('labels.layouts.destroy', $layout) }}" onsubmit="return confirm('Delete this layout?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600">Delete</button>
            </form>
        </div>
    </div>
@empty
    <p class="text-sm text-brand-charcoal dark:text-brand-silver">No label layouts yet. Create one to start printing.</p>
@endforelse

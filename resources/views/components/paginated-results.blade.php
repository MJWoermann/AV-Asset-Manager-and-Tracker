@props([
    'paginator',
    'default' => \App\Support\PageSize::DEFAULT,
])

@php
    use App\Support\PageSize;

    $selected = PageSize::selected(request(), (int) $default);
@endphp

<div {{ $attributes->class('mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between') }}>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            @if ($paginator->total() > 0 && $paginator->firstItem() !== null)
                Showing
                <span class="font-medium text-black dark:text-white">{{ $paginator->firstItem() }}</span>
                to
                <span class="font-medium text-black dark:text-white">{{ $paginator->lastItem() }}</span>
                of
                <span class="font-medium text-black dark:text-white">{{ $paginator->total() }}</span>
                results
            @else
                Showing
                <span class="font-medium text-black dark:text-white">0</span>
                results
            @endif
        </p>

        <label class="inline-flex items-center gap-2 text-sm text-brand-charcoal dark:text-brand-silver">
            <span>Per page</span>
            <select
                class="rounded border-brand-charcoal/40 bg-white text-sm dark:bg-black dark:border-brand-charcoal dark:text-white"
                aria-label="Results per page"
                onchange="(() => { const url = new URL(window.location.href); url.searchParams.set('{{ PageSize::PARAM }}', this.value); url.searchParams.delete('page'); window.location.assign(url.toString()); })()"
            >
                @foreach (PageSize::OPTIONS as $size)
                    <option value="{{ $size }}" @selected($selected === $size)>{{ $size }}</option>
                @endforeach
                <option value="{{ PageSize::ALL }}" @selected($selected === PageSize::ALL)>ALL</option>
            </select>
        </label>
    </div>

    @if ($paginator->hasPages())
        <div class="sm:shrink-0">
            {{ $paginator->links('vendor.pagination.numbers') }}
        </div>
    @endif
</div>

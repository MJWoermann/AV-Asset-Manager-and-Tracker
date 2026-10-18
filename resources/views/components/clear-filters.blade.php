@props([
    'href',
    'active' => true,
])

@if($active)
    <a
        href="{{ $href }}"
        {{ $attributes->class('px-4 py-2 border border-brand-charcoal dark:border-white text-sm inline-flex items-center justify-center shrink-0') }}
    >Clear all filters</a>
@endif

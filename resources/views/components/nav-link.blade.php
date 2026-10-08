@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center px-2 py-1 text-sm font-medium text-brand border-b-2 border-brand'
    : 'inline-flex items-center px-2 py-1 text-sm font-medium text-brand-silver hover:text-white border-b-2 border-transparent';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

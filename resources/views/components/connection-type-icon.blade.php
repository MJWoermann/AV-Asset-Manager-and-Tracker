@props(['icon' => 'generic'])

@php
    $key = in_array($icon, \App\Support\ConnectionTypeIcons::keys(), true) ? $icon : 'generic';
@endphp

@switch($key)
    @case('network')
        <svg {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.25 6.75h7.5M6 9.75h12M7.5 12.75h9M9 15.75h6M4.5 4.5h15v15h-15z"/>
        </svg>
        @break
    @case('hdmi')
        <svg {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 8.5h16v7H4zM8 15.5v2M16 15.5v2M7 11.5h2M11 11.5h2M15 11.5h2"/>
        </svg>
        @break
    @case('sdi')
        <svg {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <circle cx="12" cy="12" r="7.25" stroke-width="1.75"/>
            <circle cx="12" cy="12" r="2.5" stroke-width="1.75"/>
        </svg>
        @break
    @case('dante')
        <svg {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.5 12h3l2-4 3 8 2-4h5"/>
        </svg>
        @break
    @case('custom')
        <svg {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.5v3M12 16.5v3M4.5 12h3M16.5 12h3M7 7l2.1 2.1M14.9 14.9 17 17M17 7l-2.1 2.1M9.1 14.9 7 17"/>
        </svg>
        @break
    @case('analog')
        <svg {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 14c2-4 4-4 6 0s4 4 6 0 4-4 6 0"/>
        </svg>
        @break
    @default
        <svg {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }} aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 12h8M12 8v8M5 5h14v14H5z"/>
        </svg>
@endswitch

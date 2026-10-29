@props([
    'variant' => 'banner',
])

@php
    $user = Auth::user();
    $show = $user?->needsDepartmentAssignment() ?? false;
@endphp

@if($show)
    @php
        $message = 'No Department configured for your profile. Please request an assignment from your IT support.';
    @endphp

    @if($variant === 'inline')
        <div
            {{ $attributes->merge(['class' => 'border border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100 text-sm px-4 py-3', 'role' => 'alert']) }}
        >
            {{ $message }}
        </div>
    @else
        <div class="no-print max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div
                {{ $attributes->merge(['class' => 'rounded border border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100 text-sm px-4 py-3', 'role' => 'alert']) }}
            >
                {{ $message }}
            </div>
        </div>
    @endif
@endif

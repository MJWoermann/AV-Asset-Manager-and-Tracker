@props([
    'value',
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-brand-silver']) }}>
    {{ $value ?? $slot }}
    @if($required)
        <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
        <span class="sr-only">(required)</span>
    @endif
</label>

@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-brand focus:ring-brand rounded-md shadow-sm dark:border-brand-charcoal dark:bg-black dark:text-white']) }}>

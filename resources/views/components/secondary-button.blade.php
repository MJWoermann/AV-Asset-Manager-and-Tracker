<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 dark:bg-black dark:border-brand-charcoal dark:text-brand-silver dark:hover:bg-brand-charcoal dark:hover:text-white dark:focus:ring-offset-black']) }}>
    {{ $slot }}
</button>

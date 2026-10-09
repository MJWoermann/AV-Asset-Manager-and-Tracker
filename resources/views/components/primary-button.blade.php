<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-brand border border-transparent rounded font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-teal focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 dark:focus:ring-offset-black transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-black dark:text-white leading-tight">
            {{ __('Leaving the application') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-brand-slate border border-transparent dark:border-brand-charcoal shadow sm:rounded-lg space-y-6">
                <div>
                    <p class="text-sm text-gray-600 dark:text-brand-silver">
                        {{ __('You are about to open an external website. Only continue if you trust this destination.') }}
                    </p>
                    <p class="mt-4 text-lg font-semibold text-gray-900 dark:text-white break-all">
                        {{ $host }}
                    </p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-brand-silver break-all">
                        {{ $url }}
                    </p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <a
                        href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
                        class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-brand-slate border border-gray-300 dark:border-brand-charcoal rounded-md font-semibold text-xs text-gray-700 dark:text-brand-silver uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-brand-charcoal focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
                    >
                        {{ __('Cancel') }}
                    </a>

                    <form method="post" action="{{ route('files.external.continue', $file) }}" class="inline">
                        @csrf
                        <input type="hidden" name="trust" value="0">
                        <x-secondary-button type="submit">
                            {{ __('Continue once') }}
                        </x-secondary-button>
                    </form>

                    <form method="post" action="{{ route('files.external.continue', $file) }}" class="inline">
                        @csrf
                        <input type="hidden" name="trust" value="1">
                        <x-primary-button type="submit">
                            {{ __('Trust this domain and continue') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

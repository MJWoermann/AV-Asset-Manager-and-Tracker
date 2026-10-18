<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-brand-black dark:text-white leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-brand-slate border border-transparent dark:border-brand-charcoal shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            @unlessrole('external_repairer')
                <x-department-controls />
            @endunlessrole

            <div class="p-4 sm:p-8 bg-white dark:bg-brand-slate border border-transparent dark:border-brand-charcoal shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @unlessrole('external_repairer')
                <div class="p-4 sm:p-8 bg-white dark:bg-brand-slate border border-transparent dark:border-brand-charcoal shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.api-tokens')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-brand-slate border border-transparent dark:border-brand-charcoal shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endunlessrole
        </div>
    </div>
</x-app-layout>

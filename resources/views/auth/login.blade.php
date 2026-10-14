<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(($sso['google'] ?? false) || ($sso['azure'] ?? false))
        <div class="mb-6 space-y-3">
            @if($sso['google'] ?? false)
                <a
                    href="{{ route('auth.social.redirect', ['provider' => 'google']) }}"
                    class="flex w-full items-center justify-center gap-2 rounded-md border border-brand-charcoal/30 bg-white px-4 py-2 text-sm font-medium text-brand-charcoal hover:bg-brand-charcoal/5 dark:border-brand-silver/30 dark:bg-brand-slate dark:text-brand-silver dark:hover:bg-brand-charcoal"
                >
                    Continue with Google
                </a>
            @endif
            @if($sso['azure'] ?? false)
                <a
                    href="{{ route('auth.social.redirect', ['provider' => 'azure']) }}"
                    class="flex w-full items-center justify-center gap-2 rounded-md border border-brand-charcoal/30 bg-white px-4 py-2 text-sm font-medium text-brand-charcoal hover:bg-brand-charcoal/5 dark:border-brand-silver/30 dark:bg-brand-slate dark:text-brand-silver dark:hover:bg-brand-charcoal"
                >
                    Continue with Microsoft
                </a>
            @endif
        </div>

        <div class="relative mb-6">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-brand-charcoal/20 dark:border-brand-silver/20"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-white px-2 text-brand-charcoal dark:bg-brand-charcoal dark:text-brand-silver">Or sign in with password</span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

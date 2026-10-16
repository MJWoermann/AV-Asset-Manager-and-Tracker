<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-brand-silver">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-brand-silver">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-brand-silver hover:text-gray-900 dark:hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="theme" :value="__('Theme preference')" />
            <select id="theme" name="theme" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                <option value="light" @selected(old('theme', $user->theme) === 'light')>Light</option>
                <option value="dark" @selected(old('theme', $user->theme) === 'dark')>Dark</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('theme')" />
        </div>

        <div class="space-y-2">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ __('Scan feedback') }}
            </p>
            <p class="text-sm text-gray-600 dark:text-brand-silver">
                {{ __('Optional cues when a barcode matches or fails. Vibration uses less power than tones on supported phones.') }}
            </p>
            <label for="scan_vibrate" class="flex items-start gap-2 text-sm text-gray-800 dark:text-brand-silver">
                <input type="hidden" name="scan_vibrate" value="0">
                <input
                    id="scan_vibrate"
                    type="checkbox"
                    name="scan_vibrate"
                    value="1"
                    class="rounded border-gray-300 text-brand mt-0.5"
                    @checked(old('scan_vibrate', $user->wantsScanVibrate()))
                >
                <span class="font-medium text-gray-900 dark:text-white">{{ __('Enable scan vibration') }}</span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('scan_vibrate')" />
            <label for="scan_noise" class="flex items-start gap-2 text-sm text-gray-800 dark:text-brand-silver">
                <input type="hidden" name="scan_noise" value="0">
                <input
                    id="scan_noise"
                    type="checkbox"
                    name="scan_noise"
                    value="1"
                    class="rounded border-gray-300 text-brand mt-0.5"
                    @checked(old('scan_noise', $user->wantsScanNoise()))
                >
                <span class="font-medium text-gray-900 dark:text-white">{{ __('Enable scan noise') }}</span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('scan_noise')" />
            <label for="camera_battery_optimisation" class="flex items-start gap-2 text-sm text-gray-800 dark:text-brand-silver">
                <input type="hidden" name="camera_battery_optimisation" value="0">
                <input
                    id="camera_battery_optimisation"
                    type="checkbox"
                    name="camera_battery_optimisation"
                    value="1"
                    class="rounded border-gray-300 text-brand mt-0.5"
                    @checked(old('camera_battery_optimisation', $user->wantsCameraBatteryOptimisation()))
                >
                <span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ __('Battery-saving camera mode') }}</span>
                    <span class="block text-gray-600 dark:text-brand-silver">
                        {{ __('Uses a lower camera resolution and frame rate. Leave off when scanning fine barcodes.') }}
                    </span>
                </span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('camera_battery_optimisation')" />
        </div>

        @php
            $trustedDomains = \App\Support\TrustedExternalDomains::hosts($user->preferences);
        @endphp
        <div class="space-y-2">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ __('Trusted external domains') }}
            </p>
            <p class="text-sm text-gray-600 dark:text-brand-silver">
                {{ __('When you open a URL attachment, hosts you trust skip the leave-app confirmation for every file on that domain.') }}
            </p>
            @if ($trustedDomains === [])
                <p class="text-sm text-gray-600 dark:text-brand-silver">
                    {{ __('No trusted domains yet.') }}
                </p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-brand-charcoal border border-gray-200 dark:border-brand-charcoal rounded-md">
                    @foreach ($trustedDomains as $trustedHost)
                        <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <span class="font-medium text-gray-900 dark:text-white break-all">{{ $trustedHost }}</span>
                            <form method="post" action="{{ route('preferences.trusted-external-domains.destroy') }}">
                                @csrf
                                @method('delete')
                                <input type="hidden" name="host" value="{{ $trustedHost }}">
                                <x-danger-button type="submit" class="!px-2 !py-1 !text-[10px]">
                                    {{ __('Remove') }}
                                </x-danger-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($user->hasRole('admin'))
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ __('Developer features') }}
                </legend>
                <p class="text-sm text-gray-600 dark:text-brand-silver">
                    {{ __('Optional previews for features still under development. Enabling a flag only affects your account.') }}
                </p>

                @foreach (\App\Support\DeveloperFeatures::definitions() as $featureKey => $featureLabel)
                    <label for="features_{{ $featureKey }}" class="flex items-start gap-2 text-sm text-gray-800 dark:text-brand-silver">
                        <input type="hidden" name="features[{{ $featureKey }}]" value="0">
                        <input
                            id="features_{{ $featureKey }}"
                            type="checkbox"
                            name="features[{{ $featureKey }}]"
                            value="1"
                            class="rounded border-gray-300 text-brand mt-0.5"
                            @checked(old('features.'.$featureKey, $user->hasFeature($featureKey)))
                        >
                        <span class="font-medium text-gray-900 dark:text-white">{{ __($featureLabel) }}</span>
                    </label>
                @endforeach
            </fieldset>
        @elseif (collect(\App\Support\DeveloperFeatures::keys())->contains(fn (string $key): bool => $user->hasFeature($key)))
            <div class="space-y-2">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ __('Developer features') }}
                </p>
                <p class="text-sm text-gray-600 dark:text-brand-silver">
                    {{ __('Feature flags on your account can only be changed by an administrator.') }}
                </p>
                <ul class="list-disc list-inside text-sm text-gray-800 dark:text-brand-silver">
                    @foreach (\App\Support\DeveloperFeatures::definitions() as $featureKey => $featureLabel)
                        @if ($user->hasFeature($featureKey))
                            <li>{{ __($featureLabel) }}</li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-brand-silver"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

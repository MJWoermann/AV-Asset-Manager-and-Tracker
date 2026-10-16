<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ __('API tokens') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-brand-silver">
            {{ __('Create personal access tokens for scripts and integrations. Treat tokens like passwords; they are shown only once. See docs/api.md for the REST API reference.') }}
        </p>
    </header>

    @if (session('status') === 'api-token-created' && session('api_token_plain'))
        <div
            class="mt-4 rounded border border-brand bg-brand/5 p-4 dark:border-brand dark:bg-brand/10"
            x-data="{
                copied: false,
                async copy() {
                    try {
                        await navigator.clipboard.writeText(@js(session('api_token_plain')));
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    } catch (e) {
                        this.$refs.tokenInput.select();
                    }
                }
            }"
        >
            <p class="text-sm font-medium text-brand-black dark:text-white">
                {{ __('Token created') }}@if (session('api_token_name')) — {{ session('api_token_name') }}@endif
            </p>
            <p class="mt-1 text-sm text-gray-600 dark:text-brand-silver">
                {{ __('Copy this token now. You will not be able to see it again.') }}
            </p>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                    x-ref="tokenInput"
                    type="text"
                    readonly
                    value="{{ session('api_token_plain') }}"
                    class="block w-full rounded border-gray-300 bg-white font-mono text-sm shadow-sm focus:border-brand focus:ring-brand dark:border-brand-charcoal dark:bg-black dark:text-white"
                />
                <x-secondary-button type="button" x-on:click="copy()" class="shrink-0">
                    <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></span>
                </x-secondary-button>
            </div>
        </div>
    @endif

    @if (session('status') === 'api-token-revoked')
        <p class="mt-4 text-sm text-brand">{{ __('API token revoked.') }}</p>
    @endif

    <div class="mt-6 space-y-4">
        @forelse ($apiTokens as $token)
            <div class="flex flex-col gap-3 border-b border-gray-200 pb-4 dark:border-brand-charcoal sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white">{{ $token->name }}</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-brand-silver">
                        {{ __('Abilities') }}:
                        @php
                            $abilities = is_array($token->abilities) ? $token->abilities : [];
                        @endphp
                        @if ($abilities === [] || in_array('*', $abilities, true))
                            {{ __('Full access') }} (*)
                        @else
                            {{ implode(', ', $abilities) }}
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-brand-silver">
                        {{ __('Created') }}: {{ $token->created_at?->timezone(config('app.timezone'))->toDayDateTimeString() }}
                        ·
                        {{ __('Last used') }}:
                        {{ $token->last_used_at?->timezone(config('app.timezone'))->toDayDateTimeString() ?? __('Never') }}
                    </p>
                </div>
                <form method="post" action="{{ route('profile.api-tokens.destroy', $token) }}" class="shrink-0"
                      onsubmit="return confirm(@js(__('Revoke this API token? Scripts using it will stop working.')))">
                    @csrf
                    @method('delete')
                    <x-danger-button type="submit">{{ __('Revoke') }}</x-danger-button>
                </form>
            </div>
        @empty
            <p class="text-sm text-gray-600 dark:text-brand-silver">{{ __('No API tokens yet.') }}</p>
        @endforelse
    </div>

    <form method="post" action="{{ route('profile.api-tokens.store') }}" class="mt-8 space-y-6">
        @csrf

        <div>
            <x-input-label for="api_token_name" :value="__('Token name')" />
            <x-text-input id="api_token_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autocomplete="off" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <fieldset>
            <legend class="text-sm font-medium text-gray-700 dark:text-brand-silver">{{ __('Abilities') }}</legend>
            <p class="mt-1 text-sm text-gray-600 dark:text-brand-silver">
                {{ __('Leave all unchecked for full access, or choose specific scopes. Full access overrides other selections.') }}
            </p>
            <div class="mt-3 space-y-2">
                @foreach ($apiTokenAbilities as $ability => $label)
                    <label class="flex items-start gap-2 text-sm text-gray-900 dark:text-white">
                        <input
                            type="checkbox"
                            name="abilities[]"
                            value="{{ $ability }}"
                            class="mt-0.5 rounded border-gray-300 text-brand shadow-sm focus:ring-brand dark:border-brand-charcoal dark:bg-black"
                            @checked(collect(old('abilities', ['*']))->contains($ability))
                        >
                        <span>
                            <span class="font-medium">{{ $label }}</span>
                            <span class="text-gray-500 dark:text-brand-silver"> ({{ $ability }})</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('abilities')" class="mt-2" />
            <x-input-error :messages="$errors->get('abilities.*')" class="mt-2" />
        </fieldset>

        <div>
            <x-input-label for="api_token_password" :value="__('Current password')" />
            <x-text-input id="api_token_password" name="password" type="password" class="mt-1 block w-full" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end gap-2">
            <x-primary-button>{{ __('Create token') }}</x-primary-button>
        </div>
    </form>
</section>

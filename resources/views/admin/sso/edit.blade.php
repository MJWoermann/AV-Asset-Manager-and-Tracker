<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Single sign-on (SSO)</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4">
        <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver">
            Enable Google or Microsoft Entra sign-in for existing users only. OAuth client IDs and secrets are configured in the server environment, not here.
        </p>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 text-sm">
            <div class="bg-white dark:bg-brand-slate border p-4">
                <p class="font-medium mb-1">Google credentials</p>
                <p class="{{ $credentials['google'] ? 'text-brand' : 'text-red-600 dark:text-red-400' }}">
                    {{ $credentials['google'] ? 'Configured in environment' : 'Missing in environment' }}
                </p>
            </div>
            <div class="bg-white dark:bg-brand-slate border p-4">
                <p class="font-medium mb-1">Microsoft Entra credentials</p>
                <p class="{{ $credentials['azure'] ? 'text-brand' : 'text-red-600 dark:text-red-400' }}">
                    {{ $credentials['azure'] ? 'Configured in environment' : 'Missing in environment' }}
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.sso.update') }}" class="space-y-6 bg-white dark:bg-brand-slate border p-4">
            @csrf
            @method('PUT')

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">Providers</legend>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="google_enabled"
                        value="1"
                        class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                        @checked(old('google_enabled', $settings['google_enabled']))
                    >
                    <span>Show Google sign-in on the login page</span>
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="azure_enabled"
                        value="1"
                        class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                        @checked(old('azure_enabled', $settings['azure_enabled']))
                    >
                    <span>Show Microsoft sign-in on the login page</span>
                </label>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Buttons appear only when the provider is enabled, credentials are set in <code class="text-xs">.env</code>, and at least one allowed domain is listed below.
                </p>
            </fieldset>

            <div>
                <label for="allowed_domains" class="block text-sm font-medium mb-1">Allowed email domains</label>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">One domain per line (for example <code class="text-xs">yourcompany.com</code>). SSO stays off until this list is non-empty.</p>
                <textarea
                    id="allowed_domains"
                    name="allowed_domains"
                    rows="6"
                    class="block w-full rounded-md border-brand-charcoal/30 dark:bg-brand-charcoal dark:border-brand-silver/30 text-sm"
                >{{ old('allowed_domains', $allowedDomainsText) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('allowed_domains')" />
            </div>

            <x-primary-button>Save SSO settings</x-primary-button>
        </form>
    </div>
</x-app-layout>

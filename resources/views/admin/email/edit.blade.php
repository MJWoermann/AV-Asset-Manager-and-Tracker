<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Email integration</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Configure outbound/inbound mail for maintenance notifications and MAC onboarding.
            Secrets stay in <code class="text-xs">.env</code> only.
            See <code class="text-xs">docs/email-setup.md</code> in the repo for Gmail, Outlook Graph, and manual SMTP setup.
        </p>

        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 text-sm">
            <div class="bg-white dark:bg-brand-slate border p-4">
                <p class="font-medium mb-1">SMTP / MAIL_* credentials</p>
                <p class="{{ $credentials['mail'] ? 'text-brand' : 'text-red-600 dark:text-red-400' }}">
                    {{ $credentials['mail'] ? 'Present in environment' : 'Missing or incomplete in environment' }}
                </p>
            </div>
            <div class="bg-white dark:bg-brand-slate border p-4">
                <p class="font-medium mb-1">Microsoft Graph credentials</p>
                <p class="{{ $credentials['graph'] ? 'text-brand' : 'text-red-600 dark:text-red-400' }}">
                    {{ $credentials['graph'] ? 'Configured (MICROSOFT_GRAPH_*)' : 'Not configured' }}
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.email.update') }}" class="space-y-6 bg-white dark:bg-brand-slate border p-4" x-data="{ driver: @js(old('driver', $settings['driver'])) }">
            @csrf
            @method('PUT')

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" class="rounded text-brand" @checked(old('enabled', $settings['enabled']))>
                <span>Enable email integration (outbound + inbound poll)</span>
            </label>

            <div>
                <x-input-label for="driver" value="Driver" />
                <select id="driver" name="driver" x-model="driver" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    @foreach($drivers as $driver)
                        <option value="{{ $driver }}">{{ strtoupper($driver) }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                    <span x-show="driver === 'gmail'">Gmail: uses smtp.gmail.com / imap.gmail.com presets. See <code class="text-xs">docs/email-setup.md</code> § Gmail.</span>
                    <span x-show="driver === 'outlook'" x-cloak>Outlook: Microsoft Graph send + inbox poll. See <code class="text-xs">docs/email-setup.md</code> § Outlook.</span>
                    <span x-show="driver === 'smtp'" x-cloak>Manual SMTP (+ optional IMAP). See <code class="text-xs">docs/email-setup.md</code> § SMTP.</span>
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="from_name" value="From name override" />
                    <x-text-input id="from_name" name="from_name" class="mt-1 block w-full" :value="old('from_name', $settings['from_name'])" />
                </div>
                <div>
                    <x-input-label for="from_address" value="From address override" />
                    <x-text-input id="from_address" name="from_address" type="email" class="mt-1 block w-full" :value="old('from_address', $settings['from_address'])" />
                </div>
            </div>

            <div x-show="driver === 'outlook'" x-cloak class="space-y-3">
                <div>
                    <x-input-label for="outlook_mailbox" value="Outlook mailbox UPN" />
                    <x-text-input id="outlook_mailbox" name="outlook_mailbox" class="mt-1 block w-full" :value="old('outlook_mailbox', $settings['outlook_mailbox'])" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="inbound_folder" value="Inbound folder" />
                    <x-text-input id="inbound_folder" name="inbound_folder" class="mt-1 block w-full" :value="old('inbound_folder', $settings['inbound_folder'])" />
                </div>
                <div>
                    <x-input-label for="poll_interval_minutes" value="Poll interval (minutes)" />
                    <x-text-input id="poll_interval_minutes" name="poll_interval_minutes" type="number" min="1" class="mt-1 block w-full" :value="old('poll_interval_minutes', $settings['poll_interval_minutes'])" />
                </div>
            </div>

            <fieldset x-show="driver !== 'outlook'" x-cloak class="space-y-3 border border-brand-silver dark:border-brand-charcoal p-3">
                <legend class="px-1 text-sm font-medium">IMAP (inbound)</legend>
                <p x-show="driver === 'gmail'" class="text-xs text-brand-charcoal dark:text-brand-silver">Gmail presets are applied automatically (imap.gmail.com:993 SSL).</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="imap_host" value="IMAP host" />
                        <x-text-input id="imap_host" name="imap_host" class="mt-1 block w-full" :value="old('imap_host', $settings['imap_host'])" />
                    </div>
                    <div>
                        <x-input-label for="imap_port" value="IMAP port" />
                        <x-text-input id="imap_port" name="imap_port" type="number" class="mt-1 block w-full" :value="old('imap_port', $settings['imap_port'])" />
                    </div>
                    <div>
                        <x-input-label for="imap_encryption" value="Encryption" />
                        <select id="imap_encryption" name="imap_encryption" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            @foreach(['ssl' => 'SSL', 'tls' => 'TLS', 'none' => 'None'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('imap_encryption', $settings['imap_encryption'] ?? 'ssl') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="imap_username" value="IMAP username" />
                        <x-text-input id="imap_username" name="imap_username" class="mt-1 block w-full" :value="old('imap_username', $settings['imap_username'])" />
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Password comes from MAIL_PASSWORD in .env.</p>
                    </div>
                </div>
            </fieldset>

            <div class="flex flex-wrap gap-2">
                <x-primary-button>Save settings</x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.email.test') }}" class="bg-white dark:bg-brand-slate border p-4">
            @csrf
            <p class="text-sm mb-3">Send a test message to the configured from address.</p>
            <x-primary-button>Send test email</x-primary-button>
        </form>
    </div>
</x-app-layout>

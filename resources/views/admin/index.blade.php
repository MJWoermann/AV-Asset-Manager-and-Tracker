<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Admin</h2></x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Application settings and administration.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            @can('admin:read')
                <a href="{{ route('admin.users.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Manage users</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Accounts, roles, and department membership.</p>
                </a>
            @endcan
            @can('admin:write')
                <a href="{{ route('admin.roles.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Roles</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Permissions and developer features for each role.</p>
                </a>
            @endcan
            @can('admin:read')
                <a href="{{ route('admin.departments.index') }}" class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Departments</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Operational data isolation boundaries.</p>
                </a>
            @endcan
            @can('admin:settings')
                <a href="{{ route('admin.export-columns.edit') }}" data-history-roundtrip class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Export columns</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Default columns for asset exports.</p>
                </a>
                <a href="{{ route('admin.storage.edit') }}" data-history-roundtrip class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">Storage</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Attachment disks, migration, and backups.</p>
                </a>
                <a href="{{ route('admin.sso.edit') }}" data-history-roundtrip class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                    <h3 class="font-semibold">SSO</h3>
                    <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Single sign-on configuration.</p>
                </a>
                @feature('email_integration')
                    <a href="{{ route('admin.email.edit') }}" data-history-roundtrip class="block border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 hover:border-brand">
                        <h3 class="font-semibold">Email</h3>
                        <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">Inbound and outbound email integration.</p>
                    </a>
                @endfeature
            @endcan
        </div>
    </div>
</x-app-layout>

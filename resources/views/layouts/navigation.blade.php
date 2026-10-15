<nav x-data="{ open: false }" class="bg-black text-white border-b border-brand-charcoal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <div class="flex items-center gap-6 min-w-0">
                <a href="{{ route('dashboard') }}" class="shrink-0 inline-flex items-center" aria-label="{{ config('app.name', 'AV Asset Manager') }}">
                    <img src="{{ asset('favicon.svg') }}" alt="" class="h-8 w-8 rounded-md" width="32" height="32">
                </a>
                <div class="hidden sm:flex items-center gap-1 text-sm">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('scan.index')" :active="request()->routeIs('scan.*')">Scan</x-nav-link>
                    <x-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">Assets</x-nav-link>
                    <x-nav-link :href="route('lists.index')" :active="request()->routeIs('lists.*')">Lists</x-nav-link>

                    @php
                        $reportsActive = request()->routeIs('reports.compare*', 'reports.unknown-scans*', 'reports.connection-map*', 'labels.*');
                        $dataActive = request()->routeIs('locations.*', 'manufacturers.*', 'item-types.*', 'custom-field-sets.*', 'files.*', 'connection-types.*');
                    @endphp

                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button type="button" @class([
                                'inline-flex items-center gap-1 px-2 py-1 text-sm font-medium border-b-2',
                                'text-brand border-brand' => $reportsActive,
                                'text-brand-silver hover:text-white border-transparent' => ! $reportsActive,
                            ])>
                                Reports
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('reports.compare')">Compare</x-dropdown-link>
                            <x-dropdown-link :href="route('reports.unknown-scans')">Unknown scans</x-dropdown-link>
                            @feature('asset_connections')
                                <x-dropdown-link :href="route('reports.connection-map')">Connection map</x-dropdown-link>
                            @endfeature
                            @feature('shelf_tag_generator')
                                @role('admin|inventory_manager')
                                    <x-dropdown-link :href="route('labels.index')">Labels</x-dropdown-link>
                                @endrole
                            @endfeature
                        </x-slot>
                    </x-dropdown>

                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button type="button" @class([
                                'inline-flex items-center gap-1 px-2 py-1 text-sm font-medium border-b-2',
                                'text-brand border-brand' => $dataActive,
                                'text-brand-silver hover:text-white border-transparent' => ! $dataActive,
                            ])>
                                Data
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('locations.index')">Locations</x-dropdown-link>
                            <x-dropdown-link :href="route('manufacturers.index')">Manufacturers</x-dropdown-link>
                            @role('admin|inventory_manager')
                                <x-dropdown-link :href="route('item-types.index')">Item Types</x-dropdown-link>
                                <x-dropdown-link :href="route('custom-field-sets.index')">Field sets</x-dropdown-link>
                                <x-dropdown-link :href="route('files.index')">Files</x-dropdown-link>
                                @feature('asset_connections')
                                    <x-dropdown-link :href="route('connection-types.index')">Connection types</x-dropdown-link>
                                @endfeature
                            @endrole
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="w-56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm text-brand-silver hover:text-white">
                            {{ Auth::user()->name }}
                            <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>

                        @role('admin')
                            <div class="border-t border-gray-100 dark:border-brand-charcoal my-1"></div>
                            <div class="px-4 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-brand-silver/70">Admin</div>
                            <x-dropdown-link :href="route('admin.users.index')">Manage users</x-dropdown-link>
                            <x-dropdown-link :href="route('admin.export-columns.edit')">Export columns</x-dropdown-link>
                            <x-dropdown-link :href="route('admin.storage.edit')">Storage</x-dropdown-link>
                            <x-dropdown-link :href="route('admin.sso.edit')">SSO</x-dropdown-link>
                            @feature('email_integration')
                                <x-dropdown-link :href="route('admin.email.edit')">Email</x-dropdown-link>
                            @endfeature
                        @endrole

                        @feature('email_integration')
                            @role('inventory_manager')
                                @unlessrole('admin')
                                    <div class="border-t border-gray-100 dark:border-brand-charcoal my-1"></div>
                                    <x-dropdown-link :href="route('admin.email.inbound.index')">Email</x-dropdown-link>
                                @endunlessrole
                            @endrole
                        @endfeature

                        <div class="border-t border-gray-100 dark:border-brand-charcoal my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="p-2 text-brand-silver hover:text-white" aria-label="Menu">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-brand-charcoal">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('scan.index')" :active="request()->routeIs('scan.*')">Scan</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">Assets</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('lists.index')" :active="request()->routeIs('lists.*')">Lists</x-responsive-nav-link>

            <div class="pt-2 pb-1 px-4 text-xs font-semibold uppercase tracking-wide text-brand-silver/70">Reports</div>
            <x-responsive-nav-link :href="route('reports.compare')" :active="request()->routeIs('reports.compare*')">Compare</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.unknown-scans')" :active="request()->routeIs('reports.unknown-scans*')">Unknown scans</x-responsive-nav-link>
            @feature('asset_connections')
                <x-responsive-nav-link :href="route('reports.connection-map')" :active="request()->routeIs('reports.connection-map*')">Connection map</x-responsive-nav-link>
            @endfeature
            @feature('shelf_tag_generator')
                @role('admin|inventory_manager')
                    <x-responsive-nav-link :href="route('labels.index')" :active="request()->routeIs('labels.*')">Labels</x-responsive-nav-link>
                @endrole
            @endfeature

            <div class="pt-2 pb-1 px-4 text-xs font-semibold uppercase tracking-wide text-brand-silver/70">Data</div>
            <x-responsive-nav-link :href="route('locations.index')" :active="request()->routeIs('locations.*')">Locations</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('manufacturers.index')" :active="request()->routeIs('manufacturers.*')">Manufacturers</x-responsive-nav-link>
            @role('admin|inventory_manager')
                <x-responsive-nav-link :href="route('item-types.index')" :active="request()->routeIs('item-types.*')">Item Types</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('custom-field-sets.index')" :active="request()->routeIs('custom-field-sets.*')">Field sets</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('files.index')" :active="request()->routeIs('files.index')">Files</x-responsive-nav-link>
                @feature('asset_connections')
                    <x-responsive-nav-link :href="route('connection-types.index')" :active="request()->routeIs('connection-types.*')">Connection types</x-responsive-nav-link>
                @endfeature
            @endrole

            <div class="pt-2 pb-1 px-4 text-xs font-semibold uppercase tracking-wide text-brand-silver/70">Account</div>
            <x-responsive-nav-link :href="route('profile.edit')">Profile</x-responsive-nav-link>

            @role('admin')
                <div class="pt-2 pb-1 px-4 text-xs font-semibold uppercase tracking-wide text-brand-silver/70">Admin</div>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Manage users</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.export-columns.edit')" :active="request()->routeIs('admin.export-columns.*')">Export columns</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.storage.edit')" :active="request()->routeIs('admin.storage.*')">Storage</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.sso.edit')" :active="request()->routeIs('admin.sso.*')">SSO</x-responsive-nav-link>
                @feature('email_integration')
                    <x-responsive-nav-link :href="route('admin.email.edit')" :active="request()->routeIs('admin.email.*')">Email</x-responsive-nav-link>
                @endfeature
            @endrole

            @feature('email_integration')
                @role('inventory_manager')
                    @unlessrole('admin')
                        <x-responsive-nav-link :href="route('admin.email.inbound.index')" :active="request()->routeIs('admin.email.*')">Email</x-responsive-nav-link>
                    @endunlessrole
                @endrole
            @endfeature

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>

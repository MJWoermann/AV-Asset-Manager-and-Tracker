<nav x-data="{ open: false }" class="bg-black text-white border-b border-brand-charcoal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <div class="flex items-center gap-6 min-w-0">
                <a href="{{ route(auth()->user()->homeRouteName()) }}" class="shrink-0 inline-flex items-center" aria-label="{{ config('app.name', 'AV Asset Manager') }}">
                    <img src="{{ asset('favicon.svg') }}" alt="" class="h-8 w-8 rounded-md" width="32" height="32">
                </a>
                <div class="hidden sm:flex items-center gap-1 text-sm">
                    @role('external_repairer')
                        <x-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs.*')">Repairs</x-nav-link>
                        <x-nav-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')">Maintenance</x-nav-link>
                    @else
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('scan.index')" :active="request()->routeIs('scan.*')">Scan</x-nav-link>
                    <x-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">Assets</x-nav-link>
                    <x-nav-link :href="route('lists.index')" :active="request()->routeIs('lists.*')">Lists</x-nav-link>

                    @php
                        $reportsActive = request()->routeIs('reports.*', 'labels.*');
                        $maintenanceActive = request()->routeIs('maintenance.*', 'repairs.*', 'repair-procedures.*', 'spares.*');
                        $dataActive = request()->routeIs('data.*', 'locations.*', 'manufacturers.*', 'item-types.*', 'custom-field-sets.*', 'files.*', 'connection-types.*');
                        $riggingActive = request()->routeIs('rigging.*');
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
                            @can('reports:export')
                                <x-dropdown-link :href="route('reports.checkouts')">Checkout holdings</x-dropdown-link>
                            @endcan
                            <x-dropdown-link :href="route('reports.unknown-scans')">Unknown scans</x-dropdown-link>
                            @feature('asset_connections')
                                <x-dropdown-link :href="route('reports.connection-map')">Connection map</x-dropdown-link>
                            @endfeature
                            @feature('shelf_tag_generator')
                                @can('labels:read')
                                    <x-dropdown-link :href="route('labels.index')">Labels</x-dropdown-link>
                                @endcan
                            @endfeature
                        </x-slot>
                    </x-dropdown>

                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button type="button" @class([
                                'inline-flex items-center gap-1 px-2 py-1 text-sm font-medium border-b-2',
                                'text-brand border-brand' => $maintenanceActive,
                                'text-brand-silver hover:text-white border-transparent' => ! $maintenanceActive,
                            ])>
                                Maintenance
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('repairs.index')">Repairs</x-dropdown-link>
                            @can('repairs:procedures')
                                <x-dropdown-link :href="route('repair-procedures.index')">Repair procedures</x-dropdown-link>
                            @endcan
                            <x-dropdown-link :href="route('spares.index')">Spares</x-dropdown-link>
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
                            @can('catalog:update')
                                <x-dropdown-link :href="route('item-types.index')">Item Types</x-dropdown-link>
                                <x-dropdown-link :href="route('custom-field-sets.index')">Field sets</x-dropdown-link>
                                @can('files:manage')
                                    <x-dropdown-link :href="route('files.index')">Files</x-dropdown-link>
                                @endcan
                                @feature('asset_connections')
                                    @can('connections:write')
                                        <x-dropdown-link :href="route('connection-types.index')">Connection types</x-dropdown-link>
                                    @endcan
                                @endfeature
                            @endcan
                        </x-slot>
                    </x-dropdown>

                    @feature('rigging')
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button type="button" @class([
                                    'inline-flex items-center gap-1 px-2 py-1 text-sm font-medium border-b-2',
                                    'text-brand border-brand' => $riggingActive,
                                    'text-brand-silver hover:text-white border-transparent' => ! $riggingActive,
                                ])>
                                    Rigging
                                    <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('rigging.events.index')">Rig Events</x-dropdown-link>
                                <x-dropdown-link :href="route('rigging.calculators.index')">Calculators</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endfeature
                    @endrole
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
                        <x-dropdown-link :href="route('profile.edit')" data-history-roundtrip>Profile</x-dropdown-link>
                        @unlessrole('external_repairer')
                        <x-dropdown-link
                            href="https://github.com/MJWoermann/AV-Asset-Manager-and-Tracker/wiki"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Help</x-dropdown-link>

                        @if (auth()->user()->canManageAdminArea())
                            <div class="border-t border-gray-100 dark:border-brand-charcoal my-1"></div>
                            <div class="px-4 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-brand-silver/70">Admin</div>
                            @can('admin:read')
                                <x-dropdown-link :href="route('admin.users.index')">Manage users</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.departments.index')">Departments</x-dropdown-link>
                            @endcan
                            @can('admin:write')
                                <x-dropdown-link :href="route('admin.roles.index')">Roles</x-dropdown-link>
                            @endcan
                            @can('admin:settings')
                                <x-dropdown-link :href="route('admin.export-columns.edit')" data-history-roundtrip>Export columns</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.storage.edit')" data-history-roundtrip>Storage</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.sso.edit')" data-history-roundtrip>SSO</x-dropdown-link>
                            @endcan
                            @feature('email_integration')
                                @can('admin:settings')
                                    <x-dropdown-link :href="route('admin.email.edit')" data-history-roundtrip>Email</x-dropdown-link>
                                @endcan
                            @endfeature
                        @endif

                        @feature('email_integration')
                            @can('email:ops')
                                @cannot('admin:settings')
                                    <div class="border-t border-gray-100 dark:border-brand-charcoal my-1"></div>
                                    <x-dropdown-link :href="route('admin.email.inbound.index')">Email</x-dropdown-link>
                                @endcannot
                            @endcan
                        @endfeature
                        @endunlessrole

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
            @role('external_repairer')
                <x-responsive-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs.*')">Repairs</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')">Maintenance</x-responsive-nav-link>
            @else
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('scan.index')" :active="request()->routeIs('scan.*')">Scan</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">Assets</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('lists.index')" :active="request()->routeIs('lists.*')">Lists</x-responsive-nav-link>
            <x-responsive-nav-link
                :href="route('reports.index')"
                :active="request()->routeIs('reports.*', 'labels.*')"
            >Reports</x-responsive-nav-link>
            <x-responsive-nav-link
                :href="route('maintenance.index')"
                :active="request()->routeIs('maintenance.*', 'repairs.*', 'repair-procedures.*', 'spares.*')"
            >Maintenance</x-responsive-nav-link>
            <x-responsive-nav-link
                :href="route('data.index')"
                :active="request()->routeIs('data.*', 'locations.*', 'manufacturers.*', 'item-types.*', 'custom-field-sets.*', 'files.*', 'connection-types.*')"
            >Data</x-responsive-nav-link>
            @feature('rigging')
                <x-responsive-nav-link :href="route('rigging.index')" :active="request()->routeIs('rigging.*')">Rigging</x-responsive-nav-link>
            @endfeature
            @endrole

            <div class="pt-2 pb-1 px-4 text-xs font-semibold uppercase tracking-wide text-brand-silver/70">Account</div>
            <x-responsive-nav-link :href="route('profile.edit')" data-history-roundtrip>Profile</x-responsive-nav-link>
            @unlessrole('external_repairer')
            <x-responsive-nav-link
                href="https://github.com/MJWoermann/AV-Asset-Manager-and-Tracker/wiki"
                target="_blank"
                rel="noopener noreferrer"
            >Help</x-responsive-nav-link>

            @if (auth()->user()->canManageAdminArea())
                <x-responsive-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')">Admin</x-responsive-nav-link>
            @endif

            @feature('email_integration')
                @can('email:ops')
                    @cannot('admin:settings')
                        <x-responsive-nav-link :href="route('admin.email.inbound.index')" :active="request()->routeIs('admin.email.*')">Email</x-responsive-nav-link>
                    @endcannot
                @endcan
            @endfeature
            @endunlessrole

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>

<nav x-data="{ open: false }" class="bg-black text-white border-b border-brand-charcoal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <div class="flex items-center gap-6 min-w-0">
                <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight text-white shrink-0">
                    <span class="text-brand">AV</span> Assets
                </a>
                <div class="hidden sm:flex items-center gap-1 text-sm">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('scan.index')" :active="request()->routeIs('scan.*')">Scan</x-nav-link>
                    <x-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">Assets</x-nav-link>
                    <x-nav-link :href="route('lists.index')" :active="request()->routeIs('lists.*')">Lists</x-nav-link>
                    <x-nav-link :href="route('reports.compare')" :active="request()->routeIs('reports.*')">Compare</x-nav-link>
                    <x-nav-link :href="route('locations.index')" :active="request()->routeIs('locations.*')">Locations</x-nav-link>
                    @role('admin|inventory_manager')
                        <x-nav-link :href="route('import.create')" :active="request()->routeIs('import.*')">Import</x-nav-link>
                        <x-nav-link :href="route('item-types.index')" :active="request()->routeIs('item-types.*')">Types</x-nav-link>
                        <x-nav-link :href="route('custom-field-sets.index')" :active="request()->routeIs('custom-field-sets.*')">Field sets</x-nav-link>
                    @endrole
                    @role('admin')
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">Users</x-nav-link>
                    @endrole
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm text-brand-silver hover:text-white">
                            {{ Auth::user()->name }}
                            <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
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
            <x-responsive-nav-link :href="route('reports.compare')" :active="request()->routeIs('reports.*')">Compare</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('locations.index')" :active="request()->routeIs('locations.*')">Locations</x-responsive-nav-link>
            @role('admin|inventory_manager')
                <x-responsive-nav-link :href="route('import.create')" :active="request()->routeIs('import.*')">Import</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('item-types.index')" :active="request()->routeIs('item-types.*')">Types</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('custom-field-sets.index')" :active="request()->routeIs('custom-field-sets.*')">Field sets</x-responsive-nav-link>
            @endrole
            @role('admin')
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">Users</x-responsive-nav-link>
            @endrole
            <x-responsive-nav-link :href="route('profile.edit')">Profile</x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>

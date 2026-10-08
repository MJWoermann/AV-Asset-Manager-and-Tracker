<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Locations</h2>
            @role('admin|inventory_manager')
                <a href="{{ route('locations.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add</a>
            @endrole
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ul class="bg-white dark:bg-black border divide-y text-sm">
            @foreach($locations as $location)
                <li class="px-4 py-2 flex justify-between gap-3">
                    <div>
                        <span class="font-medium">{{ $location->name }}</span>
                        <span class="text-brand-charcoal dark:text-brand-silver"> — {{ $location->type->label() }}@if($location->is_portable) (portable)@endif</span>
                        @if($location->parent)<div class="text-xs text-brand-charcoal">Parent: {{ $location->parent->name }}</div>@endif
                    </div>
                    @role('admin|inventory_manager')
                        <a href="{{ route('locations.edit', $location) }}" class="text-brand">Edit</a>
                    @endrole
                </li>
            @endforeach
        </ul>
    </div>
</x-app-layout>

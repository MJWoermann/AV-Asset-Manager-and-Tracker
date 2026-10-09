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
        @if ($locations->isEmpty())
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No locations yet.</p>
        @else
            <ul class="bg-white dark:bg-black border text-sm">
                @foreach ($locations as $location)
                    @include('locations.partials.node', ['location' => $location])
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>

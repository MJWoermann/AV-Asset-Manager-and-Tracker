<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Manufacturers / Models</h2>
            @role('admin|inventory_manager')
                <a href="{{ route('manufacturers.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add manufacturer</a>
            @endrole
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 text-sm text-brand">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($manufacturers->isEmpty())
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No manufacturers yet.</p>
        @else
            <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal text-sm overflow-hidden">
                @foreach ($manufacturers as $index => $manufacturer)
                    @include('manufacturers.partials.node', [
                        'manufacturer' => $manufacturer,
                        'alternate' => $index % 2 === 1,
                        'replacementManufacturers' => $replacementManufacturers,
                        'allModels' => $allModels,
                    ])
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>

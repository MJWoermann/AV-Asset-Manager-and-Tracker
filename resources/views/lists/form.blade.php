<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $list->exists ? 'Edit list' : 'New list' }}</h2>
    </x-slot>

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $list->exists ? route('lists.update', $list) : route('lists.store') }}" class="space-y-4 bg-white dark:bg-black border p-4">
            @csrf
            @if($list->exists) @method('PUT') @endif
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $list->name)" required />
            </div>
            <div>
                <x-input-label for="type" value="Type" />
                <select id="type" name="type" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    <option value="inventory" @selected(old('type', $list->type) === 'inventory')>Inventory</option>
                    <option value="event" @selected(old('type', $list->type) === 'event')>Event</option>
                </select>
            </div>
            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('description', $list->description) }}</textarea>
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $location->exists ? 'Edit location' : 'New location' }}</h2></x-slot>
    <div class="max-w-xl mx-auto px-4">
        <form method="POST" action="{{ $location->exists ? route('locations.update', $location) : route('locations.store') }}" class="space-y-4 bg-white dark:bg-black border p-4">
            @csrf
            @if($location->exists) @method('PUT') @endif
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $location->name)" required />
            </div>
            <div>
                <x-input-label for="type" value="Type" />
                <select name="type" id="type" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $location->type?->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="parent_id" value="Parent" />
                <select name="parent_id" id="parent_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="">—</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $location->parent_id) == $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_portable" value="1" class="rounded text-brand" @checked(old('is_portable', $location->is_portable))>
                Portable items pool
            </label>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div>
</x-app-layout>

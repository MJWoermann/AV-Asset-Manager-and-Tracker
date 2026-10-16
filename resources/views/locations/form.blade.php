<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $location->exists ? 'Edit location' : 'New location' }}</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4">
        <form method="POST" action="{{ $location->exists ? route('locations.update', $location) : route('locations.store') }}" class="space-y-4 bg-white dark:bg-brand-slate border p-4">
            @csrf
            @if($location->exists) @method('PUT') @endif
            <div class="grid grid-cols-1 sm:grid-cols-10 gap-4">
                <div class="sm:col-span-7">
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $location->name)" required />
                </div>
                <div class="sm:col-span-3">
                    <x-input-label for="room_code" value="Room code" />
                    <x-text-input id="room_code" name="room_code" class="mt-1 block w-full" :value="old('room_code', $location->room_code)" maxlength="64" />
                </div>
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
                <x-searchable-select
                    name="parent_id"
                    id="parent_id"
                    :options="$parents->map(fn ($parent) => ['value' => $parent->id, 'label' => $parent->name])"
                    :selected="old('parent_id', $location->parent_id)"
                    nullable
                />
                <x-input-error :messages="$errors->get('parent_id')" class="mt-1" />
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_portable" value="1" class="rounded text-brand" @checked(old('is_portable', $location->is_portable))>
                Portable items pool
            </label>
            <div class="flex justify-end">
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>

        @if($location->exists)
            <div class="mt-8 space-y-3">
                <h3 class="font-semibold text-lg">Files</h3>
                <x-file-attachments
                    linkable-type="location"
                    :linkable-id="$location->id"
                    :rows="$fileRows"
                    :can-mutate="true"
                />
            </div>
        @endif
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $itemType->exists ? 'Edit type' : 'New type' }}</h2></x-slot>
    <div class="max-w-xl mx-auto px-4">
        <form method="POST" action="{{ $itemType->exists ? route('item-types.update', $itemType) : route('item-types.store') }}" class="space-y-4 bg-white dark:bg-black border p-4">
            @csrf
            @if($itemType->exists) @method('PUT') @endif
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $itemType->name)" required />
            </div>
            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" rows="2">{{ old('description', $itemType->description) }}</textarea>
            </div>
            <fieldset>
                <legend class="text-sm font-semibold mb-2">Custom field sets</legend>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">
                    Manage sets on the
                    <a href="{{ route('custom-field-sets.index') }}" class="text-brand">Field sets</a>
                    page.
                </p>
                @foreach($fieldSets as $set)
                    <label class="flex items-start gap-2 text-sm mb-2">
                        <input type="checkbox" name="field_sets[]" value="{{ $set->id }}" class="rounded text-brand mt-0.5"
                            @checked(collect(old('field_sets', $itemType->fieldSets->pluck('id')))->contains($set->id))>
                        <span>{{ $set->name }} <span class="text-brand-charcoal">({{ $set->definitions->pluck('name')->join(', ') }})</span></span>
                    </label>
                @endforeach
            </fieldset>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div>
</x-app-layout>

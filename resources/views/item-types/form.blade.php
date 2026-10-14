<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $itemType->exists ? 'Edit type' : 'New type' }}</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4">
        <form method="POST" action="{{ $itemType->exists ? route('item-types.update', $itemType) : route('item-types.store') }}" class="space-y-4 bg-white dark:bg-brand-slate border p-4">
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
                        <span>{{ $set->name }} <span class="text-brand-charcoal dark:text-brand-silver">({{ $set->definitions->pluck('name')->join(', ') }})</span></span>
                    </label>
                @endforeach
            </fieldset>
            <div class="flex flex-wrap items-center gap-3">
                <x-primary-button>Save</x-primary-button>
                <a href="{{ route('item-types.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
            </div>
        </form>

        @if($itemType->exists)
            <div class="mt-8 space-y-3">
                <h3 class="font-semibold text-lg">Files</h3>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Files linked here appear on every asset of this type.
                </p>
                <x-file-attachments
                    linkable-type="item_type"
                    :linkable-id="$itemType->id"
                    :rows="$fileRows"
                    :can-mutate="true"
                />
            </div>

            <div class="mt-6 space-y-2">
                @if($replacementTypes->isEmpty())
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Create another item type before deleting this one. Assets need a replacement type.
                    </p>
                @else
                    <x-danger-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-item-type-deletion')"
                    >
                        Delete item type
                    </x-danger-button>
                @endif
            </div>

            <x-modal name="confirm-item-type-deletion" :show="$errors->has('replacement_item_type_id')" focusable maxWidth="md">
                <form method="POST" action="{{ route('item-types.destroy', $itemType) }}" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “{{ $itemType->name }}”?
                    </h2>

                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        @if($assetCount > 0)
                            This type is used by {{ $assetCount }} {{ $assetCount === 1 ? 'asset' : 'assets' }}.
                            Choose a replacement item type for those assets, then delete.
                        @else
                            No assets use this type. Choose a replacement item type, then delete.
                        @endif
                    </p>

                    <div>
                        <x-input-label for="replacement_item_type_id" value="Replacement item type" />
                        <select
                            id="replacement_item_type_id"
                            name="replacement_item_type_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            required
                        >
                            <option value="">Select a type…</option>
                            @foreach($replacementTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) old('replacement_item_type_id') === (string) $type->id)>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('replacement_item_type_id')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">
                            Cancel
                        </x-secondary-button>
                        <x-danger-button>
                            Delete permanently
                        </x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
</x-app-layout>

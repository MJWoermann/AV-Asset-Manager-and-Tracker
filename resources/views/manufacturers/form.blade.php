<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $manufacturer->exists ? 'Edit manufacturer' : 'New manufacturer' }}</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4">
        @if (session('status'))
            <p class="mb-3 text-sm text-brand">{{ session('status') }}</p>
        @endif

        <form
            method="POST"
            action="{{ $manufacturer->exists ? route('manufacturers.update', $manufacturer) : route('manufacturers.store') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
        >
            @csrf
            @if($manufacturer->exists) @method('PUT') @endif
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $manufacturer->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>

        @if($manufacturer->exists)
            <div class="mt-6 space-y-2">
                @if($replacementManufacturers->isEmpty())
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Create another manufacturer before deleting this one. Models and assets need a replacement.
                    </p>
                @else
                    <x-danger-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-manufacturer-deletion')"
                    >
                        Delete manufacturer
                    </x-danger-button>
                @endif
            </div>

            <x-modal name="confirm-manufacturer-deletion" :show="$errors->has('replacement_manufacturer_id')" focusable maxWidth="md">
                <form method="POST" action="{{ route('manufacturers.destroy', $manufacturer) }}" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “{{ $manufacturer->name }}”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        This manufacturer has {{ $modelCount }} model{{ $modelCount === 1 ? '' : 's' }}
                        and {{ $assetCount }} asset{{ $assetCount === 1 ? '' : 's' }}.
                        Choose a replacement manufacturer, then delete.
                    </p>
                    <div>
                        <x-input-label for="replacement_manufacturer_id" value="Replacement manufacturer" />
                        <select
                            id="replacement_manufacturer_id"
                            name="replacement_manufacturer_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            required
                        >
                            <option value="">Select a manufacturer…</option>
                            @foreach($replacementManufacturers as $option)
                                <option value="{{ $option->id }}" @selected((string) old('replacement_manufacturer_id') === (string) $option->id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('replacement_manufacturer_id')" class="mt-2" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-danger-button>Delete permanently</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
</x-app-layout>

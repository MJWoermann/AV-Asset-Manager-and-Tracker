<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $connectionType->exists ? 'Edit connection type' : 'New connection type' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4">
        <form
            method="POST"
            action="{{ $connectionType->exists ? route('connection-types.update', $connectionType) : route('connection-types.store') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
        >
            @csrf
            @if($connectionType->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $connectionType->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="color" value="Color" />
                    <input
                        id="color"
                        name="color"
                        type="color"
                        class="mt-1 h-10 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal"
                        value="{{ old('color', $connectionType->color ?: '#00adb7') }}"
                        required
                    >
                    <x-input-error :messages="$errors->get('color')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="icon" value="Icon" />
                    <select id="icon" name="icon" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                        @foreach($icons as $icon)
                            <option value="{{ $icon }}" @selected(old('icon', $connectionType->icon) === $icon)>{{ $icon }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('icon')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="sort_order" value="Sort order" />
                <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', $connectionType->sort_order)" />
                <x-input-error :messages="$errors->get('sort_order')" class="mt-1" />
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="hidden" name="allows_protocol" value="0">
                <input type="checkbox" name="allows_protocol" value="1" class="rounded text-brand" @checked(old('allows_protocol', $connectionType->allows_protocol))>
                Allow free-text protocol on connections
            </label>

            <div class="flex flex-wrap items-center gap-3">
                <x-primary-button>Save</x-primary-button>
                <a href="{{ route('connection-types.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>

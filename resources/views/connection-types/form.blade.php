@php
    $selectedIcon = old('icon', $connectionType->icon ?: 'generic');
    $selectedColor = strtolower((string) old('color', $connectionType->color ?: '#00adb7'));
    $colors = collect($colors ?? [])
        ->push($selectedColor)
        ->map(fn (string $color) => strtolower($color))
        ->unique()
        ->values()
        ->all();
@endphp

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
                    <div
                        class="mt-1"
                        x-data="{ color: @js($selectedColor) }"
                    >
                        <input type="hidden" id="color" name="color" x-model="color" required>
                        <div class="flex flex-wrap gap-2" role="listbox" aria-label="Color">
                            @foreach($colors as $color)
                                <button
                                    type="button"
                                    role="option"
                                    class="h-8 w-8 rounded border-2 transition focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-1 dark:focus:ring-offset-brand-slate"
                                    style="background-color: {{ $color }}"
                                    :class="color === @js($color)
                                        ? 'border-black dark:border-white'
                                        : 'border-transparent hover:border-brand-charcoal/50'"
                                    :aria-selected="(color === @js($color)).toString()"
                                    @click="color = @js($color)"
                                    title="{{ $color }}"
                                    aria-label="{{ $color }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('color')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="icon" value="Icon" />
                    <div
                        class="relative mt-1"
                        x-data="{ open: false, value: @js($selectedIcon) }"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                    >
                        <input type="hidden" id="icon" name="icon" x-model="value" required>

                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-2 rounded border border-gray-300 bg-white px-3 py-2 text-left text-sm dark:border-brand-charcoal dark:bg-black dark:text-white"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            aria-haspopup="listbox"
                            aria-controls="icon-listbox"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                @foreach($icons as $icon)
                                    <span x-show="value === @js($icon)" class="inline-flex items-center gap-2" x-cloak>
                                        <x-connection-type-icon :icon="$icon" class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" />
                                        <span>{{ $icon }}</span>
                                    </span>
                                @endforeach
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            class="absolute z-20 mt-1 w-full border border-brand-silver bg-white shadow-sm dark:border-brand-charcoal dark:bg-brand-slate"
                            id="icon-listbox"
                            role="listbox"
                        >
                            <ul class="max-h-60 overflow-y-auto py-1 text-sm">
                                @foreach($icons as $icon)
                                    <li>
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal"
                                            :class="{ 'bg-brand-silver/40 dark:bg-brand-charcoal': value === @js($icon) }"
                                            role="option"
                                            :aria-selected="(value === @js($icon)).toString()"
                                            @click="value = @js($icon); open = false"
                                        >
                                            <x-connection-type-icon :icon="$icon" class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" />
                                            <span>{{ $icon }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
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

            <label class="inline-flex items-start gap-2 text-sm">
                <input type="hidden" name="allows_bus" value="0">
                <input type="checkbox" name="allows_bus" value="1" class="mt-0.5 rounded text-brand" @checked(old('allows_bus', $connectionType->allows_bus))>
                <span>
                    Allow device bus (1-to-many)
                    <span class="block text-xs text-brand-charcoal dark:text-brand-silver">Join many interfaces of this type onto a shared bus (e.g. Dante).</span>
                </span>
            </label>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('connection-types.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

@php
    $selectedColor = old('color', $department->color);
    $selectedColor = $selectedColor !== null && $selectedColor !== ''
        ? strtolower((string) $selectedColor)
        : '';
    $colors = collect($colors ?? [])
        ->when($selectedColor !== '', fn ($c) => $c->push($selectedColor))
        ->map(fn (string $color) => strtolower($color))
        ->unique()
        ->values()
        ->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $department->exists ? 'Edit department' : 'New department' }}</h2>
    </x-slot>
    <div class="max-w-xl mx-auto px-4">
        <form
            method="POST"
            action="{{ $department->exists ? route('admin.departments.update', $department) : route('admin.departments.store') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
        >
            @csrf
            @if($department->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Name" required />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $department->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="color" value="Colour" />
                <div
                    class="mt-1"
                    x-data="{ color: @js($selectedColor) }"
                >
                    <input type="hidden" id="color" name="color" x-model="color">
                    <div class="flex flex-wrap gap-2" role="listbox" aria-label="Colour">
                        <button
                            type="button"
                            role="option"
                            class="h-8 px-2 rounded border-2 text-xs transition focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-1 dark:focus:ring-offset-brand-slate"
                            :class="color === ''
                                ? 'border-black dark:border-white'
                                : 'border-transparent hover:border-brand-charcoal/50'"
                            :aria-selected="(color === '').toString()"
                            @click="color = ''"
                        >
                            None
                        </button>
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
                <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                    Used for asset/list row tinting (when enabled) and the admin dashboard department module.
                </p>
                <x-input-error :messages="$errors->get('color')" class="mt-1" />
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded text-brand" @checked(old('is_active', $department->is_active ?? true))>
                Active
            </label>
            <x-input-error :messages="$errors->get('is_active')" class="mt-1" />

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.departments.index') }}" class="inline-flex items-center px-4 py-2 text-sm border border-brand-silver dark:border-brand-charcoal">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

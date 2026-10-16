@php
    $initialFields = old('fields');
    if ($initialFields === null) {
        $initialFields = $fieldSet->exists
            ? $fieldSet->definitions->map(fn ($definition) => [
                'id' => $definition->id,
                'name' => $definition->name,
                'type' => $definition->type,
                'options' => is_array($definition->options) ? implode(', ', $definition->options) : '',
                'is_required' => (bool) $definition->is_required,
            ])->values()->all()
            : [];
    } else {
        $initialFields = collect($initialFields)->map(fn ($field) => [
            'id' => $field['id'] ?? null,
            'name' => $field['name'] ?? '',
            'type' => $field['type'] ?? 'text',
            'options' => $field['options'] ?? '',
            'is_required' => filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ])->values()->all();
    }

    $typeLabels = [
        'text' => 'Text',
        'number' => 'Number',
        'checkbox' => 'Checkbox',
        'select' => 'Select',
        'spacer' => 'Spacer',
        'divider' => 'Divider',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $fieldSet->exists ? 'Edit field set' : 'New field set' }}</h2>
    </x-slot>
    <div class="max-w-2xl mx-auto px-4">
        <form
            method="POST"
            action="{{ $fieldSet->exists ? route('custom-field-sets.update', $fieldSet) : route('custom-field-sets.store') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
            x-data="customFieldSetForm(@js($initialFields))"
        >
            @csrf
            @if($fieldSet->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Set name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $fieldSet->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('description', $fieldSet->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold">Fields</h3>
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        <button type="button" class="text-brand" @click="addField()">Add field</button>
                        <button type="button" class="text-brand" @click="addSpacer()">Add spacer</button>
                        <button type="button" class="text-brand" @click="addDivider()">Add divider</button>
                    </div>
                </div>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">Drag the handle or use arrows to reorder. Spacers and dividers control layout on asset forms.</p>
                <x-input-error :messages="$errors->get('fields')" class="mt-1" />

                <template x-if="fields.length === 0">
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">No fields yet. Add fields that assets of assigned item types can fill in.</p>
                </template>

                <template x-for="(field, index) in fields" :key="field.key">
                    <div
                        class="border border-brand-charcoal/20 dark:border-brand-charcoal p-3 space-y-3"
                        :class="{ 'opacity-50': draggedIndex === index }"
                        @dragover.prevent="onDragOver($event)"
                        @drop.prevent="onDrop(index, $event)"
                    >
                        <input type="hidden" :name="`fields[${index}][id]`" :value="field.id || ''">

                        <div class="flex items-start gap-2">
                            <button
                                type="button"
                                class="mt-1 shrink-0 cursor-grab text-brand-charcoal dark:text-brand-silver touch-none"
                                draggable="true"
                                title="Drag to reorder"
                                aria-label="Drag to reorder"
                                @dragstart="onDragStart(index, $event)"
                                @dragend="onDragEnd()"
                            >
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm8-12a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/>
                                </svg>
                            </button>

                            <div class="flex flex-col gap-0.5 shrink-0 mt-0.5">
                                <button
                                    type="button"
                                    class="text-brand-charcoal dark:text-brand-silver disabled:opacity-30"
                                    title="Move up"
                                    aria-label="Move up"
                                    :disabled="index === 0"
                                    @click="moveField(index, -1)"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    class="text-brand-charcoal dark:text-brand-silver disabled:opacity-30"
                                    title="Move down"
                                    aria-label="Move down"
                                    :disabled="index === fields.length - 1"
                                    @click="moveField(index, 1)"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="flex-1 space-y-3 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1">
                                        <template x-if="field.type === 'spacer'">
                                            <div>
                                                <input type="hidden" :name="`fields[${index}][name]`" :value="field.name || 'Spacer'">
                                                <p class="text-sm font-medium pt-1">Blank spacer</p>
                                                <p class="text-xs text-brand-charcoal dark:text-brand-silver">Reserves an empty cell in the asset form grid.</p>
                                            </div>
                                        </template>
                                        <template x-if="field.type === 'divider'">
                                            <div>
                                                <x-input-label value="Divider label (optional)" />
                                                <input
                                                    type="text"
                                                    :name="`fields[${index}][name]`"
                                                    x-model="field.name"
                                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                                    placeholder="Divider"
                                                >
                                            </div>
                                        </template>
                                        <template x-if="!isLayoutType(field.type)">
                                            <div>
                                                <x-input-label value="Field name" />
                                                <input
                                                    type="text"
                                                    :name="`fields[${index}][name]`"
                                                    x-model="field.name"
                                                    required
                                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                                >
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" class="text-sm text-red-600 mt-6 shrink-0" @click="removeField(index)">Remove</button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <x-input-label value="Type" />
                                        <select
                                            :name="`fields[${index}][type]`"
                                            x-model="field.type"
                                            @change="onTypeChange(field)"
                                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                        >
                                            @foreach($fieldTypes as $type)
                                                <option value="{{ $type }}">{{ $typeLabels[$type] ?? ucfirst($type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <template x-if="!isLayoutType(field.type)">
                                        <label class="inline-flex items-center gap-2 text-sm mt-6">
                                            <input type="hidden" :name="`fields[${index}][is_required]`" value="0">
                                            <input type="checkbox" :name="`fields[${index}][is_required]`" value="1" class="rounded text-brand" x-model="field.is_required">
                                            Required
                                        </label>
                                    </template>
                                    <template x-if="isLayoutType(field.type)">
                                        <input type="hidden" :name="`fields[${index}][is_required]`" value="0">
                                    </template>
                                </div>

                                <template x-if="field.type === 'select'">
                                    <div>
                                        <x-input-label value="Options (comma or newline separated)" />
                                        <textarea
                                            :name="`fields[${index}][options]`"
                                            x-model="field.options"
                                            rows="2"
                                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                            placeholder="Option A, Option B"
                                        ></textarea>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('custom-field-sets.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>

        @if($fieldSet->exists)
            <form
                method="POST"
                action="{{ route('custom-field-sets.destroy', $fieldSet) }}"
                class="mt-6"
                onsubmit="return confirm('Delete this field set? Existing values for its fields will also be removed.')"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600">Delete field set</button>
            </form>
        @endif
    </div>
</x-app-layout>

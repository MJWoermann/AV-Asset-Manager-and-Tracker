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
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold">Fields</h3>
                    <button type="button" class="text-sm text-brand" @click="addField()">Add field</button>
                </div>
                <x-input-error :messages="$errors->get('fields')" class="mt-1" />

                <template x-if="fields.length === 0">
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">No fields yet. Add fields that assets of assigned item types can fill in.</p>
                </template>

                <template x-for="(field, index) in fields" :key="field.key">
                    <div class="border border-brand-charcoal/20 dark:border-brand-charcoal p-3 space-y-3">
                        <input type="hidden" :name="`fields[${index}][id]`" :value="field.id || ''">

                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <x-input-label value="Field name" />
                                <input
                                    type="text"
                                    :name="`fields[${index}][name]`"
                                    x-model="field.name"
                                    required
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                >
                            </div>
                            <button type="button" class="text-sm text-red-600 mt-6 shrink-0" @click="removeField(index)">Remove</button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-input-label value="Type" />
                                <select
                                    :name="`fields[${index}][type]`"
                                    x-model="field.type"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                >
                                    @foreach($fieldTypes as $type)
                                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm mt-6">
                                <input type="hidden" :name="`fields[${index}][is_required]`" value="0">
                                <input type="checkbox" :name="`fields[${index}][is_required]`" value="1" class="rounded text-brand" x-model="field.is_required">
                                Required
                            </label>
                        </div>

                        <div x-show="field.type === 'select'" x-cloak>
                            <x-input-label value="Options (comma or newline separated)" />
                            <textarea
                                :name="`fields[${index}][options]`"
                                x-model="field.options"
                                rows="2"
                                class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                placeholder="Option A, Option B"
                            ></textarea>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-primary-button>Save</x-primary-button>
                <a href="{{ route('custom-field-sets.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
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

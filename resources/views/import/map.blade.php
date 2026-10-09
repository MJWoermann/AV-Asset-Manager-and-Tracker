@php
    $initialSelections = $suggestedMappings ?? [];
    $requiredFields = [
        ['key' => 'name', 'label' => 'Map a column to Name'],
        ['key' => 'item_type', 'label' => 'Map a column to Item Type'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Map columns</h2></x-slot>
    <div class="max-w-2xl mx-auto px-4">
        <form
            method="POST"
            action="{{ route('import.prepare') }}"
            class="space-y-4 bg-white dark:bg-black border p-4"
            x-data="importMapper(@js(array_values($columns)), @js($initialSelections), @js($requiredFields))"
            @submit="onSubmit($event)"
        >
            @csrf
            <p class="text-sm text-gray-600 dark:text-brand-silver">
                Map each source column to an asset field. Item type and location (level / room / rack) are set here —
                missing types and locations are created automatically. Each target field may only be used once.
                Name and Item Type are required before you can continue.
            </p>

            @if (! empty($usedRememberedMappings))
                <p class="text-sm text-brand dark:text-brand">
                    Suggestions include mappings from your previous import where column names match.
                </p>
            @endif

            <div
                x-show="!canSubmit"
                x-cloak
                class="text-sm text-red-700 dark:text-red-400 border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-3 py-2"
            >
                <p class="font-medium">Finish these requirements before reviewing duplicates:</p>
                <ul class="mt-2 list-disc list-inside space-y-1">
                    <template x-for="requirement in missingRequirements" :key="requirement">
                        <li x-text="requirement"></li>
                    </template>
                    <li x-show="duplicateWarning" x-text="duplicateWarning"></li>
                </ul>
            </div>

            @if ($errors->has('mapping'))
                <div class="text-sm text-red-700 dark:text-red-400 border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-3 py-2">
                    <p class="font-medium">Finish these requirements before reviewing duplicates:</p>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach ($errors->get('mapping') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @foreach($columns as $column)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center text-sm">
                    <div class="font-mono break-all">{{ $column }}</div>
                    <div>
                        <select
                            name="mapping[{{ $column }}]"
                            class="w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            x-model="selections[{{ json_encode($column) }}]"
                            @change="onChange({{ json_encode($column) }})"
                        >
                            <option value="">Ignore</option>
                            @foreach($fields as $field => $label)
                                <option
                                    value="{{ $field }}"
                                    :disabled="isTaken(@js($field), @js($column))"
                                >{{ $label }}</option>
                            @endforeach
                            <option value="{{ $createNewField }}">Create new field (Imported-Field:{{ $column }})</option>
                        </select>
                        <x-input-error :messages="$errors->get('mapping.'.$column)" class="mt-1" />
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-brand-black dark:bg-brand border border-transparent font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand disabled:opacity-50 disabled:cursor-not-allowed"
                    x-bind:disabled="!canSubmit"
                >
                    Review duplicates
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

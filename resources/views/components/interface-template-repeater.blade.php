@props([
    'connectionTypes',
    'syncRoute' => null,
    'syncDescription' => 'Copy any template interfaces that existing assets do not already have (by template or name).',
])

<fieldset class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <legend class="text-sm font-semibold">Interface templates</legend>
        <button type="button" class="text-sm text-brand" @click="addTemplate()">Add interface</button>
    </div>
    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
        {{ $slot }}
    </p>

    <template x-if="templates.length === 0">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">No interface templates yet.</p>
    </template>

    <template x-for="(template, index) in templates" :key="template.key">
        <div class="border border-brand-charcoal/20 dark:border-brand-charcoal p-3 space-y-2">
            <input type="hidden" :name="`interface_templates[${index}][id]`" :value="template.id || ''">
            <div class="flex flex-wrap gap-2 items-start">
                <div class="flex-1 min-w-[10rem]">
                    <x-input-label value="Name" />
                    <input type="text" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" :name="`interface_templates[${index}][name]`" x-model="template.name" required>
                </div>
                <div class="min-w-[9rem] w-44">
                    <x-input-label value="Connection type" />
                    <select
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        :name="`interface_templates[${index}][connection_type_id]`"
                        x-model="template.connection_type_id"
                    >
                        <option value="">Optional…</option>
                        @foreach($connectionTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[10rem]">
                    <x-input-label value="Notes" />
                    <input type="text" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" :name="`interface_templates[${index}][notes]`" x-model="template.notes">
                </div>
                <button type="button" class="text-sm text-red-600 mt-6" @click="removeTemplate(index)">Remove</button>
            </div>
        </div>
    </template>
</fieldset>

@if($syncRoute)
    <div class="mt-8 space-y-3 border p-4 bg-white dark:bg-brand-slate">
        <h3 class="font-semibold text-lg">Sync missing interfaces</h3>
        <p class="text-xs text-brand-charcoal dark:text-brand-silver">{{ $syncDescription }}</p>
        <form method="POST" action="{{ $syncRoute }}">
            @csrf
            <x-secondary-button type="submit">Sync missing templates</x-secondary-button>
        </form>
    </div>
@endif

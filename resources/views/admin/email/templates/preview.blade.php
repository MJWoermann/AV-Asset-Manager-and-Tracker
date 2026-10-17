<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Preview: {{ $template->name }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <x-email-tabs />

        <form method="GET" action="{{ route('admin.email.templates.preview', $template) }}" class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="asset_id" value="Sample asset" />
                <x-searchable-select
                    name="asset_id"
                    id="asset_id"
                    :options="$assets->map(fn ($option) => ['value' => $option->id, 'label' => $option->name])->values()"
                    :selected="$asset?->id"
                    placeholder="Synthetic / first asset"
                    nullable
                    auto-submit
                    aria-label="Select sample asset"
                />
            </div>
        </form>

        <div class="bg-white dark:bg-brand-slate border p-4 space-y-3">
            <div>
                <p class="text-xs uppercase text-brand-charcoal dark:text-brand-silver">Subject</p>
                <p class="font-medium">{{ $subject }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-brand-charcoal dark:text-brand-silver">Body</p>
                <pre class="whitespace-pre-wrap text-sm">{{ $body }}</pre>
            </div>
        </div>

        <a href="{{ route('admin.email.templates.edit', $template) }}" class="text-sm text-brand">Back to edit</a>
    </div>
</x-app-layout>

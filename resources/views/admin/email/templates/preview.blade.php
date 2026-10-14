<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Preview: {{ $template->name }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <form method="GET" action="{{ route('admin.email.templates.preview', $template) }}" class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="asset_id" value="Sample asset" />
                <select id="asset_id" name="asset_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="">Synthetic / first asset</option>
                    @foreach($assets as $option)
                        <option value="{{ $option->id }}" @selected(($asset?->id) === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>Refresh</x-primary-button>
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

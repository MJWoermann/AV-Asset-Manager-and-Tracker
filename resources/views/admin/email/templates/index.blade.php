<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Email</h2>
            <a href="{{ route('admin.email.templates.create') }}" data-history-roundtrip class="px-3 py-2 bg-brand text-white text-sm">Add template</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 space-y-3">
        <x-email-tabs />

        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        @forelse($templates as $template)
            <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-center">
                <div>
                    <div class="font-medium">{{ $template->name }}</div>
                    <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                        {{ $eventLabels[$template->event_key] ?? $template->event_key }}
                        · {{ $template->recipients_count }} recipients
                        · {{ $template->is_active ? 'Active' : 'Inactive' }}
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('admin.email.templates.preview', $template) }}" class="text-brand">Preview</a>
                    <a href="{{ route('admin.email.templates.edit', $template) }}" data-history-roundtrip class="text-brand">Edit</a>
                    <form method="POST" action="{{ route('admin.email.templates.destroy', $template) }}" onsubmit="return confirm('Delete this template?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No templates yet. Run the email seeder or create one.</p>
        @endforelse
    </div>
</x-app-layout>

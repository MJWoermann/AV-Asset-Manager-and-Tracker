<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Email</h2>
            <a href="{{ route('admin.email.recipients.create') }}" data-history-roundtrip class="px-3 py-2 bg-brand text-white text-sm">Add recipient</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 space-y-3">
        <x-email-tabs />

        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        @forelse($recipients as $recipient)
            <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-center">
                <div>
                    <div class="font-medium">{{ $recipient->name }}</div>
                    <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                        {{ $recipient->email }}
                        @if($recipient->role_tag)
                            · {{ $recipient->role_tag }}
                        @endif
                        · {{ $recipient->is_active ? 'Active' : 'Inactive' }}
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('admin.email.recipients.edit', $recipient) }}" data-history-roundtrip class="text-brand">Edit</a>
                    <form method="POST" action="{{ route('admin.email.recipients.destroy', $recipient) }}" onsubmit="return confirm('Delete this recipient?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No recipients yet.</p>
        @endforelse
    </div>
</x-app-layout>

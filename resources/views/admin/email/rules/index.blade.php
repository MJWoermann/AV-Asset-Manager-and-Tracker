<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl">Inbound email rules</h2>
            <a href="{{ route('admin.email.rules.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 space-y-3">
        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Helpdesk replies that match an outbound thread are auto-logged as Notes. Other matches use these rules and usually require review.
        </p>

        @forelse($rules as $rule)
            <div class="bg-white dark:bg-brand-slate border p-4 flex flex-wrap justify-between gap-3 items-center">
                <div>
                    <div class="font-medium">{{ $rule->name }}</div>
                    <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Priority {{ $rule->priority }}
                        · {{ $rule->log_type?->label() ?? $rule->log_type }}
                        · {{ $rule->require_review ? 'Requires review' : 'Auto-apply' }}
                        · {{ $rule->is_active ? 'Active' : 'Inactive' }}
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('admin.email.rules.edit', $rule) }}" class="text-brand">Edit</a>
                    <form method="POST" action="{{ route('admin.email.rules.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No inbound rules yet.</p>
        @endforelse
    </div>
</x-app-layout>

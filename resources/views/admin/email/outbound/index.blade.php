<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Email</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 space-y-4">
        <x-email-tabs />

        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Sent notifications used for inbound reply matching.
        </p>

        @forelse($threads as $thread)
            <div class="bg-white dark:bg-brand-slate border p-4 space-y-1">
                <div class="font-medium">{{ $thread->subject ?: '(no subject)' }}</div>
                <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                    {{ \App\Enums\NotificationEventKey::tryFrom((string) $thread->event_key)?->label() ?? $thread->event_key }}
                    · {{ $thread->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                    @if($thread->asset)
                        · <a href="{{ route('assets.show', [$thread->asset, 'tab' => 'maintenance']) }}" class="text-brand">{{ $thread->asset->name }}</a>
                    @endif
                    @if($thread->sender)
                        · Sent by {{ $thread->sender->name }}
                    @endif
                </div>
                @if(is_array($thread->to_addresses) && count($thread->to_addresses) > 0)
                    <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                        To {{ implode(', ', $thread->to_addresses) }}
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No outbound threads yet.</p>
        @endforelse

        {{ $threads->links() }}
    </div>
</x-app-layout>

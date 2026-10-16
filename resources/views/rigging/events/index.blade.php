<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl">Rig Events</h2>
            <div class="flex gap-3 text-sm">
                <a href="{{ route('rigging.index') }}" class="text-brand-charcoal dark:text-brand-silver">Hub</a>
                <a href="{{ route('rigging.events.create') }}" class="text-brand font-medium">New Rig Event</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 space-y-4">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="overflow-x-auto border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Space</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Engineer</th>
                        <th class="px-3 py-2">Signed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                            <td class="px-3 py-2">
                                <a href="{{ route('rigging.events.show', $event) }}" class="text-brand font-medium">{{ $event->name }}</a>
                            </td>
                            <td class="px-3 py-2">{{ $event->event_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $event->venue_space ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $event->status?->label() ?? $event->status }}</td>
                            <td class="px-3 py-2">
                                @if($event->engineer_required)
                                    {{ $event->engineer_acquired ? 'Acquired' : 'Required' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $event->isSignedOff() ? 'Yes' : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No Rig Events yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $events->links() }}
    </div>
</x-app-layout>

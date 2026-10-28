<div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3">
    <h3 class="font-semibold">Team log — {{ \App\Support\RiggingCalculators::label($calculator) }}</h3>
    @if($logs->isEmpty())
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">No saved calculations yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                    <tr>
                        <th class="px-3 py-2">When</th>
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">By</th>
                        <th class="px-3 py-2">Rig Event</th>
                        <th class="px-3 py-2">Summary</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr class="border-t border-brand-silver dark:border-brand-charcoal align-top">
                            <td class="px-3 py-2 whitespace-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                            <td class="px-3 py-2">{{ $log->title ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $log->creator?->name ?? '—' }}</td>
                            <td class="px-3 py-2">
                                @if($log->rigEvent)
                                    <a href="{{ route('rigging.events.show', $log->rigEvent) }}" data-history-roundtrip class="text-brand">{{ $log->rigEvent->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-brand-charcoal dark:text-brand-silver">
                                <details>
                                    <summary class="cursor-pointer text-brand">Results</summary>
                                    <pre class="mt-1 whitespace-pre-wrap">{{ json_encode($log->results, JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

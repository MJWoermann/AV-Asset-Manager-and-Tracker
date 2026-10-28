<div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm space-y-3">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h3 class="font-semibold">Spares register</h3>
            @if($listedSpare ?? null)
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">This asset is listed in the spares register.</p>
            @else
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">List this asset as a replacement unit in the spares register.</p>
            @endif
        </div>
        @if($listedSpare ?? null)
            <a href="{{ route('spares.show', $listedSpare) }}" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm self-start sm:self-auto">
                Open {{ $listedSpare->code }}
            </a>
        @else
            @role('admin|inventory_manager')
                <form method="POST" action="{{ route('assets.spares.store', $asset) }}" class="self-start sm:self-auto">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-brand text-white text-sm">List in spares</button>
                </form>
            @endrole
        @endif
    </div>
</div>

<div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm space-y-3">
    <h3 class="font-semibold">Spares used / replacements</h3>
    <p class="text-xs text-brand-charcoal dark:text-brand-silver">Parts consumed on this asset and replacement units logged against it.</p>

    <table class="min-w-full text-left">
        <thead class="text-xs text-brand-charcoal dark:text-brand-silver">
            <tr>
                <th class="py-1 pr-2">When</th>
                <th class="py-1 pr-2">Spare</th>
                <th class="py-1 pr-2">Type</th>
                <th class="py-1 pr-2">Qty Δ</th>
                <th class="py-1 pr-2">Reason</th>
                <th class="py-1">Who</th>
            </tr>
        </thead>
        <tbody>
            @forelse($spareMovements as $movement)
                <tr class="border-t border-brand-silver/50">
                    <td class="py-1 pr-2">{{ $movement->recorded_at?->format('Y-m-d H:i') }}</td>
                    <td class="py-1 pr-2">
                        @if($movement->sparePart)
                            <a href="{{ route('spares.show', $movement->sparePart) }}" class="text-brand font-mono">{{ $movement->sparePart->code }}</a>
                            <span class="block">{{ $movement->sparePart->component }}</span>
                        @else — @endif
                    </td>
                    <td class="py-1 pr-2">{{ $movement->type?->label() }}</td>
                    <td class="py-1 pr-2">{{ $movement->quantity_delta }}</td>
                    <td class="py-1 pr-2">{{ $movement->reason ?: '—' }}</td>
                    <td class="py-1">{{ $movement->user?->name ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-2 text-brand-charcoal">No spare movements logged for this asset.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">{{ $group->name }}</h2>
            @can('spares:manage')
                <a href="{{ route('spares.groups.edit', $group) }}" class="px-3 py-2 border text-sm">Edit</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 space-y-4 text-sm">
        @if($group->description)<p>{{ $group->description }}</p>@endif
        @if($group->linkedSystem)<p>System: <a href="{{ route('assets.show', $group->linkedSystem) }}" class="text-brand">{{ $group->linkedSystem->name }}</a></p>@endif
        @if($group->location)<p>Store: {{ $group->location->name }}</p>@endif

        <table class="min-w-full border border-brand-silver dark:border-brand-charcoal">
            <thead class="bg-brand-charcoal/5 text-left">
                <tr>
                    <th class="p-2">Spare</th>
                    <th class="p-2">Required qty</th>
                    <th class="p-2">On hand</th>
                    <th class="p-2">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group->items as $item)
                    <tr class="border-t @if($item->isShort()) bg-red-50 dark:bg-red-950/20 @endif">
                        <td class="p-2">
                            <a href="{{ route('spares.show', $item->sparePart) }}" class="text-brand font-mono">{{ $item->sparePart?->code }}</a>
                            <span class="block">{{ $item->sparePart?->component }}</span>
                        </td>
                        <td class="p-2">{{ $item->quantity }}</td>
                        <td class="p-2">{{ $item->sparePart?->quantity_on_hand }}</td>
                        <td class="p-2">{{ $item->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>

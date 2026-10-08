<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">{{ $asset->name }}</h2>
            <div class="flex flex-wrap gap-2">
                @if($asset->ip_address)
                    <a href="http://{{ $asset->ip_address }}" target="_blank" rel="noopener" class="px-3 py-2 bg-brand text-white text-sm">Open IP</a>
                @endif
                <a href="{{ route('assets.edit', $asset) }}" class="px-3 py-2 border border-brand-charcoal dark:border-white text-sm">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal p-4 text-sm">
            <div class="md:col-span-2"><span class="text-brand-charcoal dark:text-brand-silver">Description</span><div>{{ $asset->description ?: '—' }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">Type</span><div>{{ $asset->itemType?->name }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">Status</span><div>{{ $asset->status?->label() }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">Manufacturer / Model</span><div>{{ $asset->manufacturer }} {{ $asset->model }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">Location</span><div>{{ $asset->location?->breadcrumb() ?? '—' }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">FMI AST#</span><div class="font-mono">{{ $asset->fmi_ast ?: '—' }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">TP Barcode</span><div class="font-mono">{{ $asset->tp_barcode ?: '—' }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">RIG Tag #</span><div class="font-mono">{{ $asset->rig_tag ?: '—' }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">Device SN</span><div class="font-mono">{{ $asset->device_sn ?: $asset->serial_number ?: '—' }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">IP / MAC</span><div class="font-mono">{{ $asset->ip_address ?: '—' }} / {{ $asset->mac_address ?: '—' }}</div></div>
            <div><span class="text-brand-charcoal dark:text-brand-silver">Test &amp; Tag</span><div>{{ $asset->test_tag_expiry?->format('Y-m-d') ?: '—' }}</div></div>
            @if($asset->parent)
                <div class="md:col-span-2"><span class="text-brand-charcoal dark:text-brand-silver">Parent</span><div><a class="text-brand" href="{{ route('assets.show', $asset->parent) }}">{{ $asset->parent->name }}</a></div></div>
            @endif
        </div>

        @if($asset->customFieldValues->isNotEmpty())
            <div>
                <h3 class="font-semibold mb-2">Custom fields</h3>
                <div class="bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal p-4 text-sm space-y-2">
                    @foreach($asset->customFieldValues as $value)
                        <div class="flex justify-between gap-4 border-b border-brand-silver/50 dark:border-brand-charcoal pb-1">
                            <span>{{ $value->definition?->name }}</span>
                            <span class="font-mono">{{ $value->value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($asset->children->isNotEmpty())
            <div>
                <h3 class="font-semibold mb-2">Child assets</h3>
                <ul class="bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal divide-y divide-brand-silver dark:divide-brand-charcoal">
                    @foreach($asset->children as $child)
                        <li class="px-4 py-2 pl-8 text-sm">
                            <a class="text-brand" href="{{ route('assets.show', $child) }}">{{ $child->name }}</a>
                            <span class="text-brand-charcoal dark:text-brand-silver"> — {{ $child->tp_barcode }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>

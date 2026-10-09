<li class="border-b border-brand-silver dark:border-brand-charcoal last:border-b-0">
    <div class="px-4 py-2 flex flex-wrap items-center justify-between gap-3">
        <div>
            <span class="font-medium">{{ $location->name }}</span>
            <span class="text-brand-charcoal dark:text-brand-silver"> — {{ $location->type->label() }}@if($location->is_portable) (portable)@endif</span>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a
                href="{{ route('assets.index', ['location_id' => $location->id]) }}"
                class="text-sm text-brand"
            >Assets</a>
            @role('admin|inventory_manager')
                <a href="{{ route('locations.edit', $location) }}" class="text-sm text-brand">Edit</a>
            @endrole
        </div>
    </div>
    @if ($location->children->isNotEmpty())
        <ul class="ml-4 border-l border-brand-silver dark:border-brand-charcoal">
            @foreach ($location->children as $child)
                @include('locations.partials.node', ['location' => $child])
            @endforeach
        </ul>
    @endif
</li>

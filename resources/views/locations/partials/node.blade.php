@php
    $alternate = (bool) ($alternate ?? false);

    $rowClasses = match ($location->type) {
        \App\Enums\LocationType::Site => $alternate
            ? 'bg-[#dcdcdc] dark:bg-[#3d4043] border-l-[3px] border-l-brand-charcoal'
            : 'bg-brand-silver dark:bg-brand-slate border-l-[3px] border-l-brand-charcoal',
        \App\Enums\LocationType::Level => $alternate
            ? 'bg-[#cfdce6] dark:bg-[#3a4650] border-l-[3px] border-l-brand-grey-blue'
            : 'bg-[#e4edf3] dark:bg-[#313940] border-l-[3px] border-l-brand-grey-blue',
        \App\Enums\LocationType::Room => $alternate
            ? 'bg-[#e8e8e8] dark:bg-[#35383b] border-l-[3px] border-l-brand-silver dark:border-l-brand-charcoal'
            : 'bg-white dark:bg-[#2f3235] border-l-[3px] border-l-brand-silver dark:border-l-brand-charcoal',
        \App\Enums\LocationType::Rack => $alternate
            ? 'bg-[#cce8eb] dark:bg-[#33494c] border-l-[3px] border-l-brand'
            : 'bg-[#e5f5f6] dark:bg-[#2b3c3e] border-l-[3px] border-l-brand',
    };
@endphp
<li class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0">
    <div class="{{ $rowClasses }} px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
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
        <ul class="ml-4 border-l border-brand-charcoal/30 dark:border-brand-charcoal">
            @php
                $previousType = null;
                $sameTypeRun = 0;
            @endphp
            @foreach ($location->children as $child)
                @php
                    if ($previousType === $child->type) {
                        $sameTypeRun++;
                    } else {
                        $previousType = $child->type;
                        $sameTypeRun = 0;
                    }
                @endphp
                @include('locations.partials.node', [
                    'location' => $child,
                    'alternate' => $sameTypeRun % 2 === 1,
                ])
            @endforeach
        </ul>
    @endif
</li>

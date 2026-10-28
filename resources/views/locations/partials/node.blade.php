@php
    $alternate = (bool) ($alternate ?? false);
    $hasChildren = $location->children->isNotEmpty();
    $hasRackChildren = $hasChildren && $location->children->contains(
        fn ($child): bool => $child->type === \App\Enums\LocationType::Rack
    );

    $rowClasses = match ($location->type) {
        \App\Enums\LocationType::Site => $alternate
            ? 'bg-[#dcdcdc] dark:bg-[#3d4043] border-l-[3px] border-l-brand-charcoal'
            : 'bg-brand-silver dark:bg-brand-slate border-l-[3px] border-l-brand-charcoal',
        \App\Enums\LocationType::Building => $alternate
            ? 'bg-[#ddd6cc] dark:bg-[#3f3a34] border-l-[3px] border-l-[#8a7f72]'
            : 'bg-[#efe9e0] dark:bg-[#342f2a] border-l-[3px] border-l-[#8a7f72]',
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
<li
    class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0"
    data-location-id="{{ $location->id }}"
    @if ($hasChildren)
        data-location-collapsible="1"
    @endif
    @if ($hasRackChildren)
        data-location-has-racks="1"
    @endif
>
    <div class="{{ $rowClasses }} px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-start gap-3 min-w-0">
            @if ($hasChildren)
                <button
                    type="button"
                    class="mt-0.5 shrink-0 p-0.5 text-brand-charcoal dark:text-brand-silver"
                    @click="toggle({{ $location->id }})"
                    :aria-expanded="isOpen({{ $location->id }}) ? 'true' : 'false'"
                    aria-label="Toggle {{ $location->name }} children"
                >
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': isOpen({{ $location->id }}) }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <span class="mt-0.5 inline-block w-5 shrink-0" aria-hidden="true"></span>
            @endif
            @role('admin|inventory_manager')
                <input
                    type="checkbox"
                    class="mt-1 rounded border-brand-charcoal/40 shrink-0"
                    data-bulk-location-id="{{ $location->id }}"
                    data-bulk-location-name="{{ mb_strtolower(trim($location->name)) }}"
                    data-bulk-location-assets="{{ (int) ($location->assets_count ?? 0) }}"
                    :checked="!!selected[{{ $location->id }}]"
                    @change="setSelected({{ $location->id }}, $event.target.checked)"
                    aria-label="Select {{ $location->name }}"
                >
            @endrole
            <div>
                <span class="font-medium">{{ $location->name }}</span>
                @if ($location->room_code)
                    <span class="text-brand-charcoal dark:text-brand-silver"> ({{ $location->room_code }})</span>
                @endif
                <span class="text-brand-charcoal dark:text-brand-silver"> — {{ $location->type->label() }}@if($location->is_portable) (portable)@endif
                    ({{ (int) ($location->assets_count ?? 0) }} {{ (int) ($location->assets_count ?? 0) === 1 ? 'asset' : 'assets' }})
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a
                href="{{ route('assets.index', ['location_id' => $location->id]) }}"
                class="text-sm text-brand"
            >Assets</a>
            @role('admin|inventory_manager')
                <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                <a href="{{ route('locations.edit', $location) }}" class="text-sm text-brand">Edit</a>
                @if ((int) ($location->assets_count ?? 0) > 0)
                    <template x-if="canDeleteLocation({{ $location->id }})">
                        <span class="inline-flex items-center gap-2">
                            <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                            <button
                                type="button"
                                class="text-sm text-red-700"
                                x-on:click.prevent="openDeleteLocation({
                                    id: {{ $location->id }},
                                    name: @js($location->name),
                                    assetsCount: {{ (int) $location->assets_count }},
                                    action: @js(route('locations.destroy', $location)),
                                })"
                            >Delete</button>
                        </span>
                    </template>
                @endif
            @endrole
        </div>
    </div>
    @if ($hasChildren)
        <ul
            class="ml-4 border-l border-brand-charcoal/30 dark:border-brand-charcoal"
            x-show="isOpen({{ $location->id }})"
        >
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

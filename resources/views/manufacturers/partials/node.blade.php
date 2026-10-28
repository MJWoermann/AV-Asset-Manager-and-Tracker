@php
    $alternate = (bool) ($alternate ?? false);
    $hasModels = (int) $manufacturer->models_count > 0;
    $rowClasses = $alternate
        ? 'bg-[#dcdcdc] dark:bg-[#3d4043] border-l-[3px] border-l-brand-charcoal'
        : 'bg-brand-silver dark:bg-brand-slate border-l-[3px] border-l-brand-charcoal';
@endphp
<li
    class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0"
    data-manufacturer-id="{{ $manufacturer->id }}"
    data-models-url="{{ route('manufacturers.models.index', $manufacturer) }}"
    @if ($hasModels)
        data-manufacturer-collapsible="1"
    @endif
>
    <div class="{{ $rowClasses }} px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-start gap-3 min-w-0">
            @if ($hasModels)
                <button
                    type="button"
                    class="mt-0.5 shrink-0 p-0.5 text-brand-charcoal dark:text-brand-silver"
                    @click="toggle({{ $manufacturer->id }})"
                    :aria-expanded="isOpen({{ $manufacturer->id }}) ? 'true' : 'false'"
                    aria-label="Toggle {{ $manufacturer->name }} models"
                >
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': isOpen({{ $manufacturer->id }}) }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <span class="mt-0.5 inline-block w-5 shrink-0" aria-hidden="true"></span>
            @endif
            <div class="min-w-0">
                <span class="font-medium">{{ $manufacturer->name }}</span>
                <span class="text-brand-charcoal dark:text-brand-silver">
                    — Manufacturer
                    ({{ $manufacturer->models_count }} model{{ $manufacturer->models_count === 1 ? '' : 's' }},
                    {{ $manufacturer->assets_count }} asset{{ $manufacturer->assets_count === 1 ? '' : 's' }})
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a
                href="{{ route('assets.index', ['manufacturer_id' => $manufacturer->id]) }}"
                class="text-sm text-brand"
            >Assets</a>
            @can('catalog:write')
                <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                <a href="{{ route('manufacturers.models.create', $manufacturer) }}" class="text-sm text-brand">Add model</a>
            @endcan
            @can('catalog:update')
                <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                <a href="{{ route('manufacturers.edit', $manufacturer) }}" class="text-sm text-brand">Edit</a>
                <template x-if="canDeleteManufacturer({{ $manufacturer->id }})">
                    <span class="inline-flex items-center gap-2">
                        <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                        <button
                            type="button"
                            class="text-sm text-red-700"
                            x-on:click.prevent="openDeleteManufacturer({
                                id: {{ $manufacturer->id }},
                                name: @js($manufacturer->name),
                                action: @js(route('manufacturers.destroy', $manufacturer)),
                            })"
                        >Delete</button>
                    </span>
                </template>
            @endcan
        </div>
    </div>

    @if ($hasModels)
        <ul
            class="ml-4 border-l border-brand-charcoal/30 dark:border-brand-charcoal"
            x-show="isOpen({{ $manufacturer->id }})"
            x-cloak
        >
            <template x-if="isLoadingModels({{ $manufacturer->id }})">
                <li class="px-4 py-2.5 text-brand-charcoal dark:text-brand-silver">Loading models…</li>
            </template>
            <template x-if="modelsError({{ $manufacturer->id }})">
                <li class="px-4 py-2.5 text-red-700">
                    <span x-text="modelsError({{ $manufacturer->id }})"></span>
                </li>
            </template>
            <template x-for="(model, modelIndex) in modelsFor({{ $manufacturer->id }})" :key="model.id">
                <li class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0">
                    <div
                        class="px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 border-l-[3px] border-l-brand"
                        :class="modelIndex % 2 === 1
                            ? 'bg-[#cce8eb] dark:bg-[#33494c]'
                            : 'bg-[#e5f5f6] dark:bg-[#2b3c3e]'"
                    >
                        <div class="min-w-0">
                            <span class="font-medium" x-text="model.name"></span>
                            <span class="text-brand-charcoal dark:text-brand-silver">
                                — Model (<span x-text="model.assets_count"></span>
                                <span x-text="model.assets_count === 1 ? 'asset' : 'assets'"></span>)
                            </span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a :href="model.assets_url" class="text-sm text-brand">Assets</a>
                            <template x-if="canManageCatalogue && model.edit_url">
                                <span class="inline-flex items-center gap-2">
                                    <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                                    <a :href="model.edit_url" class="text-sm text-brand">Edit</a>
                                </span>
                            </template>
                            <template x-if="canManageCatalogue && model.destroy_url && canDeleteModel(model.id)">
                                <span class="inline-flex items-center gap-2">
                                    <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                                    <button
                                        type="button"
                                        class="text-sm text-red-700"
                                        x-on:click.prevent="openDeleteModel({
                                            id: model.id,
                                            name: model.name,
                                            label: model.label,
                                            assetsCount: model.assets_count,
                                            action: model.destroy_url,
                                        })"
                                    >Delete</button>
                                </span>
                            </template>
                        </div>
                    </div>
                </li>
            </template>
        </ul>
    @endif
</li>

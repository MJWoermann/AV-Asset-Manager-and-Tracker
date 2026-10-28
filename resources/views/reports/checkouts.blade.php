<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Checkout holdings</h2></x-slot>

    @php
        $filterQuery = collect($query)->except('view')->all();
        $editingPurposeId = (string) old('_purpose_id');
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            See how many of each model are checked out or available, or list every item a person currently holds.
        </p>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('reports.checkouts', $filterQuery) }}"
                class="px-3 py-1.5 text-sm border {{ $filters['view'] === 'models' ? 'border-brand text-brand' : 'border-brand-silver dark:border-brand-charcoal' }}"
            >By model</a>
            <a
                href="{{ route('reports.checkouts', array_merge($filterQuery, ['view' => 'holders'])) }}"
                class="px-3 py-1.5 text-sm border {{ $filters['view'] === 'holders' ? 'border-brand text-brand' : 'border-brand-silver dark:border-brand-charcoal' }}"
            >By holder</a>
        </div>

        <form method="GET" action="{{ route('reports.checkouts') }}" class="grid gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 sm:grid-cols-2 lg:grid-cols-3">
            @if ($filters['view'] === 'holders')
                <input type="hidden" name="view" value="holders">
            @endif

            <div class="sm:col-span-2 lg:col-span-3">
                <x-input-label for="checkouts-q" value="Asset search" />
                <x-scannable-search
                    id="checkouts-q"
                    name="q"
                    :value="$filters['q']"
                    placeholder="Name, serial, TP barcode, MAC…"
                    class="mt-1"
                    scan-aria-label="Scan barcode into checkout search"
                />
            </div>

            <div>
                <x-input-label for="purpose" value="Purpose" />
                <x-searchable-select
                    name="purpose"
                    id="purpose"
                    :options="$purposeOptions"
                    :selected="$filters['purpose'] !== '' ? $filters['purpose'] : null"
                    placeholder="Any purpose"
                    nullable
                    auto-submit
                    aria-label="Filter by purpose"
                />
            </div>

            <div>
                <x-input-label for="manufacturer_id" value="Manufacturer" />
                <x-searchable-select
                    name="manufacturer_id"
                    id="manufacturer_id"
                    :options="$manufacturerOptions"
                    :selected="$filters['manufacturer_id']"
                    placeholder="Any manufacturer"
                    nullable
                    auto-submit
                    aria-label="Filter by manufacturer"
                />
            </div>

            <div>
                <x-input-label for="manufacturer_model_id" value="Model" />
                <x-searchable-select
                    name="manufacturer_model_id"
                    id="manufacturer_model_id"
                    :options="$modelOptions"
                    :selected="$filters['manufacturer_model_id']"
                    placeholder="Any model"
                    nullable
                    auto-submit
                    aria-label="Filter by model"
                />
            </div>

            <div>
                <x-input-label for="item_type_id" value="Item type" />
                <x-searchable-select
                    name="item_type_id"
                    id="item_type_id"
                    :options="$itemTypeOptions"
                    :selected="$filters['item_type_id']"
                    placeholder="Any item type"
                    nullable
                    auto-submit
                    aria-label="Filter by item type"
                />
            </div>

            <div>
                <x-input-label for="location_id" value="Location" />
                <x-searchable-select
                    name="location_id"
                    id="location_id"
                    :options="$locationOptions"
                    :selected="$filters['location_id']"
                    placeholder="Any location"
                    nullable
                    auto-submit
                    aria-label="Filter by location"
                />
            </div>

            <div>
                <x-input-label for="holder_user_id" value="Checked out to user" />
                <x-searchable-select
                    name="holder_user_id"
                    id="holder_user_id"
                    :options="$userOptions"
                    :selected="$filters['holder_user_id']"
                    placeholder="Any user"
                    nullable
                    auto-submit
                    aria-label="Filter by user"
                />
            </div>

            <div>
                <x-input-label for="guest" value="Guest name, company, or email" />
                <x-text-input id="guest" name="guest" class="mt-1 block w-full" :value="$filters['guest']" />
            </div>

            <div>
                <x-input-label for="checked_out_from" value="Checked out from" />
                <x-text-input id="checked_out_from" name="checked_out_from" type="date" class="mt-1 block w-full" :value="$filters['checked_out_from']" />
            </div>

            <div>
                <x-input-label for="checked_out_to" value="Checked out to" />
                <x-text-input id="checked_out_to" name="checked_out_to" type="date" class="mt-1 block w-full" :value="$filters['checked_out_to']" />
            </div>

            @if ($filters['view'] === 'holders')
                <div>
                    <x-input-label for="status" value="Checkout status" />
                    <x-searchable-select
                        name="status"
                        id="status"
                        :options="[
                            ['value' => 'open', 'label' => 'Open'],
                            ['value' => 'returned', 'label' => 'Returned'],
                            ['value' => 'all', 'label' => 'All'],
                        ]"
                        :selected="$filters['status']"
                        auto-submit
                        aria-label="Filter by checkout status"
                    />
                </div>
            @endif

            <div class="flex flex-wrap items-end gap-4 sm:col-span-2 lg:col-span-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="overdue" value="1" @checked($filters['overdue']) onchange="this.form.requestSubmit()">
                    Overdue only
                </label>
                @if ($filters['view'] === 'models')
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="hidden" name="checked_out_only" value="0">
                        <input type="checkbox" name="checked_out_only" value="1" @checked($filters['checked_out_only']) onchange="this.form.requestSubmit()">
                        Only models with items checked out
                    </label>
                @endif
            </div>

            <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-primary-button>Search</x-primary-button>
                    <x-clear-filters
                        :href="route('reports.checkouts', $filters['view'] === 'holders' ? ['view' => 'holders'] : [])"
                        :active="$filtersActive"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-brand-charcoal dark:text-brand-silver">Export</span>
                    <a href="{{ route('reports.checkouts.export', array_merge($query, ['format' => 'xlsx'])) }}" class="px-4 py-2 border text-sm">XLSX</a>
                    <a href="{{ route('reports.checkouts.export', array_merge($query, ['format' => 'csv'])) }}" class="px-4 py-2 border text-sm">CSV</a>
                    <a href="{{ route('reports.checkouts.export', array_merge($query, ['format' => 'pdf'])) }}" class="px-4 py-2 border text-sm">PDF</a>
                </div>
            </div>
        </form>

        @if ($errors->any())
            <div class="text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($filters['view'] === 'models')
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                Available and in register ignore purpose, holder, dates, and overdue. Those filters change the checked-out count. Disposed and lost assets are left out.
                @if ($filters['checked_out_only'])
                    Manufacturers with nothing checked out are hidden.
                @endif
            </p>

            @if ($modelGroups === [])
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    {{ $filters['checked_out_only'] && $modelRows !== [] ? 'No models have items checked out.' : 'No assets match these filters.' }}
                </p>
            @else
                <div
                    x-data="{
                        expanded: {},
                        isOpen(id) { return Boolean(this.expanded[id]); },
                        toggle(id) { this.expanded = { ...this.expanded, [id]: ! this.isOpen(id) }; },
                        expandAll() {
                            const next = {};
                            this.groupIds().forEach((id) => { next[id] = true; });
                            this.expanded = next;
                        },
                        collapseAll() { this.expanded = {}; },
                        groupIds() {
                            return [...this.$root.querySelectorAll('[data-report-group]')].map((el) => el.getAttribute('data-report-group'));
                        },
                    }"
                >
                    <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                        <button type="button" class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver" @click="expandAll()">Expand all</button>
                        <button type="button" class="px-3 py-1.5 border border-brand-charcoal dark:border-brand-silver" @click="collapseAll()">Collapse all</button>
                    </div>
                    <ul class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal text-sm overflow-hidden">
                        @foreach ($modelGroups as $index => $group)
                            @php
                                $alternate = $index % 2 === 1;
                                $rowClasses = $alternate
                                    ? 'bg-[#dcdcdc] dark:bg-[#3d4043] border-l-[3px] border-l-brand-charcoal'
                                    : 'bg-brand-silver dark:bg-brand-slate border-l-[3px] border-l-brand-charcoal';
                            @endphp
                            <li
                                class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0"
                                data-report-group="{{ $group['id'] }}"
                            >
                                <div class="{{ $rowClasses }} px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <button
                                            type="button"
                                            class="mt-0.5 shrink-0 p-0.5 text-brand-charcoal dark:text-brand-silver"
                                            @click="toggle(@js($group['id']))"
                                            :aria-expanded="isOpen(@js($group['id'])) ? 'true' : 'false'"
                                            aria-label="Toggle {{ $group['name'] }} models"
                                        >
                                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': isOpen(@js($group['id'])) }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                        <div class="min-w-0">
                                            <span class="font-medium">{{ $group['name'] }}</span>
                                            <span class="text-brand-charcoal dark:text-brand-silver">
                                                — Manufacturer
                                                ({{ $group['models_count'] }} {{ $group['models_count'] === 1 ? 'model' : 'models' }},
                                                {{ $group['checked_out'] }} checked out,
                                                {{ $group['available'] }} available)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <ul
                                    class="ml-4 border-l border-brand-charcoal/30 dark:border-brand-charcoal"
                                    x-show="isOpen(@js($group['id']))"
                                    x-cloak
                                >
                                    @foreach ($group['models'] as $modelIndex => $model)
                                        <li class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0">
                                            <div @class([
                                                'px-4 py-2.5 border-l-[3px] border-l-brand',
                                                'bg-[#cce8eb] dark:bg-[#33494c]' => $modelIndex % 2 === 1,
                                                'bg-[#e5f5f6] dark:bg-[#2b3c3e]' => $modelIndex % 2 === 0,
                                            ])>
                                                <span class="font-medium">{{ $model['model'] }}</span>
                                                <span class="text-brand-charcoal dark:text-brand-silver">
                                                    — Model
                                                    ({{ $model['checked_out'] }} checked out,
                                                    {{ $model['available'] }} available,
                                                    {{ $model['in_register'] }} in register)
                                                </span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <div class="space-y-4">
                @forelse ($holderGroups as $group)
                    <section class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                        <div class="px-3 py-2 border-b border-brand-silver dark:border-brand-charcoal">
                            <h3 class="font-semibold">{{ $group['label'] }}</h3>
                            @if ($group['detail'] !== '')
                                <p class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $group['detail'] }}</p>
                            @endif
                            <p class="text-xs text-brand-charcoal dark:text-brand-silver">{{ $group['checkouts']->count() }} {{ \Illuminate\Support\Str::plural('item', $group['checkouts']->count()) }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-brand-silver/40 dark:bg-brand-charcoal/40 text-left">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Asset</th>
                                        <th class="px-3 py-2 font-medium">Identifiers</th>
                                        <th class="px-3 py-2 font-medium">Model</th>
                                        <th class="px-3 py-2 font-medium">Purpose</th>
                                        <th class="px-3 py-2 font-medium">Checked out</th>
                                        <th class="px-3 py-2 font-medium">Expected return</th>
                                        <th class="px-3 py-2 font-medium">Location</th>
                                        <th class="px-3 py-2 font-medium">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-brand-silver dark:divide-brand-charcoal">
                                    @foreach ($group['checkouts'] as $checkout)
                                        @php
                                            $asset = $checkout->asset;
                                            $identifiers = collect([
                                                $asset?->serial_number,
                                                $asset?->tp_barcode,
                                                $asset?->fmi_ast,
                                                $asset?->rig_tag,
                                            ])->filter()->implode(' · ');
                                            $overdue = $checkout->returned_at === null
                                                && $checkout->expected_return_at !== null
                                                && $checkout->expected_return_at->isPast();
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-2">
                                                @if ($asset)
                                                    <a class="text-brand" href="{{ route('assets.show', $asset) }}">{{ $asset->name }}</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">{{ $identifiers !== '' ? $identifiers : '—' }}</td>
                                            <td class="px-3 py-2">
                                                {{ $asset?->manufacturer?->name ?? '—' }}
                                                {{ $asset?->manufacturerModel ? ' '.$asset->manufacturerModel->name : '' }}
                                            </td>
                                            <td class="px-3 py-2">{{ $checkout->purposeLabel() }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                {{ $checkout->created_at?->format('Y-m-d H:i') ?? '—' }}
                                                @if ($checkout->returned_at)
                                                    <div class="text-xs text-brand-charcoal dark:text-brand-silver">Returned {{ $checkout->returned_at->format('Y-m-d H:i') }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap {{ $overdue ? 'text-red-600' : '' }}">
                                                {{ $checkout->expected_return_at?->format('Y-m-d H:i') ?? '—' }}
                                            </td>
                                            <td class="px-3 py-2">{{ $asset?->location?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-brand-charcoal dark:text-brand-silver">{{ $checkout->notes ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @empty
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">No checkouts match these filters.</p>
                @endforelse
            </div>
        @endif

        <section class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-4">
            <div>
                <h3 class="font-semibold">Checkout options</h3>
                <p class="mt-1 text-sm text-brand-charcoal dark:text-brand-silver">
                    New options check equipment out the same way as Hire. Hire and Repair stay in the list. Deactivate an option to hide it from the checkout form.
                </p>
            </div>

            <form method="POST" action="{{ route('reports.checkouts.purposes.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <x-input-label for="new-purpose-name" value="New option" />
                    <x-text-input
                        id="new-purpose-name"
                        name="name"
                        class="mt-1 block w-full"
                        :value="$editingPurposeId === '' ? old('name') : ''"
                        required
                    />
                </div>
                <x-primary-button>Add option</x-primary-button>
            </form>

            <div class="divide-y divide-brand-silver dark:divide-brand-charcoal border border-brand-silver dark:border-brand-charcoal">
                @foreach ($purposes as $purpose)
                    @php
                        $editing = $editingPurposeId === (string) $purpose->id;
                    @endphp
                    <div class="p-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <form id="purpose-update-{{ $purpose->id }}" method="POST" action="{{ route('reports.checkouts.purposes.update', $purpose) }}" class="flex flex-1 flex-wrap items-end gap-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="_purpose_id" value="{{ $purpose->id }}">
                            <div class="min-w-[12rem] flex-1">
                                <x-input-label :for="'purpose-name-'.$purpose->id" value="Name" />
                                <x-text-input
                                    :id="'purpose-name-'.$purpose->id"
                                    name="name"
                                    class="mt-1 block w-full"
                                    :value="$editing ? old('name', $purpose->name) : $purpose->name"
                                    required
                                />
                            </div>
                            <div class="text-sm pb-2">
                                @if ($purpose->canDeactivate())
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($editing ? (bool) old('is_active') : $purpose->is_active)>
                                        Active
                                    </label>
                                @else
                                    <input type="hidden" name="is_active" value="1">
                                    <span class="text-brand-charcoal dark:text-brand-silver">Always available</span>
                                @endif
                            </div>
                        </form>
                        <div class="flex w-full items-center justify-between gap-2 lg:w-auto lg:min-w-[14rem]">
                            @if ($purpose->canDelete())
                                <form method="POST" action="{{ route('reports.checkouts.purposes.destroy', $purpose) }}" onsubmit="return confirm('Delete this checkout option?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button>Delete</x-danger-button>
                                </form>
                            @else
                                <span></span>
                            @endif
                            <x-primary-button :form="'purpose-update-'.$purpose->id">Save</x-primary-button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>

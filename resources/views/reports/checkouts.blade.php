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

            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="overdue" value="1" @checked($filters['overdue']) onchange="this.form.requestSubmit()">
                    Overdue only
                </label>
            </div>

            <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <x-clear-filters
                        :href="route('reports.checkouts', $filters['view'] === 'holders' ? ['view' => 'holders'] : [])"
                        :active="$filtersActive"
                    />
                    <a href="{{ route('reports.checkouts.export', array_merge($query, ['format' => 'xlsx'])) }}" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Export XLSX</a>
                    <a href="{{ route('reports.checkouts.export', array_merge($query, ['format' => 'csv'])) }}" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Export CSV</a>
                </div>
                <x-primary-button>Search</x-primary-button>
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
            </p>

            <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
                <table class="min-w-full text-sm">
                    <thead class="bg-brand-silver/40 dark:bg-brand-charcoal/40 text-left">
                        <tr>
                            <th class="px-3 py-2 font-medium">Manufacturer</th>
                            <th class="px-3 py-2 font-medium">Model</th>
                            <th class="px-3 py-2 font-medium">Available</th>
                            <th class="px-3 py-2 font-medium">Checked out</th>
                            <th class="px-3 py-2 font-medium">In register</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-silver dark:divide-brand-charcoal">
                        @forelse ($modelRows as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row['manufacturer'] }}</td>
                                <td class="px-3 py-2">{{ $row['model'] }}</td>
                                <td class="px-3 py-2">{{ $row['available'] }}</td>
                                <td class="px-3 py-2">{{ $row['checked_out'] }}</td>
                                <td class="px-3 py-2">{{ $row['in_register'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">No assets match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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

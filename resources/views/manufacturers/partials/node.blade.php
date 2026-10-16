@php
    $alternate = (bool) ($alternate ?? false);
    $rowClasses = $alternate
        ? 'bg-[#dcdcdc] dark:bg-[#3d4043] border-l-[3px] border-l-brand-charcoal'
        : 'bg-brand-silver dark:bg-brand-slate border-l-[3px] border-l-brand-charcoal';
@endphp
<li class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0">
    <div class="{{ $rowClasses }} px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <span class="font-medium">{{ $manufacturer->name }}</span>
            <span class="text-brand-charcoal dark:text-brand-silver">
                — Manufacturer
                ({{ $manufacturer->models_count }} model{{ $manufacturer->models_count === 1 ? '' : 's' }},
                {{ $manufacturer->assets_count }} asset{{ $manufacturer->assets_count === 1 ? '' : 's' }})
            </span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a
                href="{{ route('assets.index', ['manufacturer_id' => $manufacturer->id]) }}"
                class="text-sm text-brand"
            >Assets</a>
            @role('admin|inventory_manager')
                <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                <a href="{{ route('manufacturers.models.create', $manufacturer) }}" class="text-sm text-brand">Add model</a>
                <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                <a href="{{ route('manufacturers.edit', $manufacturer) }}" class="text-sm text-brand">Edit</a>
                @if($replacementManufacturers->where('id', '!=', $manufacturer->id)->isNotEmpty())
                    <span class="text-brand-charcoal dark:text-brand-silver" aria-hidden="true">|</span>
                    <button
                        type="button"
                        class="text-sm text-red-700"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'delete-manufacturer-{{ $manufacturer->id }}')"
                    >Delete</button>
                @endif
            @endrole
        </div>
    </div>

    @role('admin|inventory_manager')
        @php
            $otherManufacturers = $replacementManufacturers->where('id', '!=', $manufacturer->id);
        @endphp
        @if($otherManufacturers->isNotEmpty())
            <x-modal name="delete-manufacturer-{{ $manufacturer->id }}" :show="$errors->has('replacement_manufacturer_id') && (int) old('deleting_manufacturer_id') === (int) $manufacturer->id" focusable maxWidth="md">
                <form method="POST" action="{{ route('manufacturers.destroy', $manufacturer) }}" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="deleting_manufacturer_id" value="{{ $manufacturer->id }}">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “{{ $manufacturer->name }}”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        Models under this manufacturer are moved to the replacement (merged by name).
                        Assets are reassigned to the replacement manufacturer.
                    </p>
                    <div>
                        <x-input-label for="replacement_manufacturer_id_{{ $manufacturer->id }}" value="Replacement manufacturer" />
                        <select
                            id="replacement_manufacturer_id_{{ $manufacturer->id }}"
                            name="replacement_manufacturer_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            required
                        >
                            <option value="">Select a manufacturer…</option>
                            @foreach($otherManufacturers as $option)
                                <option value="{{ $option->id }}" @selected((string) old('replacement_manufacturer_id') === (string) $option->id && (int) old('deleting_manufacturer_id') === (int) $manufacturer->id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('replacement_manufacturer_id')" class="mt-2" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-danger-button>Delete permanently</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endrole

    @if ($manufacturer->models->isNotEmpty())
        <ul class="ml-4 border-l border-brand-charcoal/30 dark:border-brand-charcoal">
            @foreach ($manufacturer->models as $modelIndex => $manufacturerModel)
                @include('manufacturers.partials.model-node', [
                    'manufacturer' => $manufacturer,
                    'manufacturerModel' => $manufacturerModel,
                    'alternate' => $modelIndex % 2 === 1,
                    'allModels' => $allModels,
                ])
            @endforeach
        </ul>
    @endif
</li>

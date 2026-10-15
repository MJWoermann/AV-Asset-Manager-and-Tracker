@php
    $alternate = (bool) ($alternate ?? false);
    $rowClasses = $alternate
        ? 'bg-[#cce8eb] dark:bg-[#33494c] border-l-[3px] border-l-brand'
        : 'bg-[#e5f5f6] dark:bg-[#2b3c3e] border-l-[3px] border-l-brand';
    $otherModels = collect($allModels ?? [])->where('id', '!=', $manufacturerModel->id)->values();
@endphp
<li class="border-b border-brand-charcoal/25 dark:border-brand-charcoal last:border-b-0">
    <div class="{{ $rowClasses }} px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <span class="font-medium">{{ $manufacturerModel->name }}</span>
            <span class="text-brand-charcoal dark:text-brand-silver">
                — Model ({{ $manufacturerModel->assets_count }} asset{{ $manufacturerModel->assets_count === 1 ? '' : 's' }})
            </span>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a
                href="{{ route('assets.index', ['manufacturer_model_id' => $manufacturerModel->id]) }}"
                class="text-sm text-brand"
            >Assets</a>
            @role('admin|inventory_manager')
                <a href="{{ route('manufacturers.models.edit', [$manufacturer, $manufacturerModel]) }}" class="text-sm text-brand">Edit</a>
                @if($otherModels->isNotEmpty())
                    <button
                        type="button"
                        class="text-sm text-red-700"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'delete-model-{{ $manufacturerModel->id }}')"
                    >Delete</button>
                @endif
            @endrole
        </div>
    </div>

    @role('admin|inventory_manager')
        @if($otherModels->isNotEmpty())
            <x-modal name="delete-model-{{ $manufacturerModel->id }}" :show="$errors->has('replacement_manufacturer_model_id') && (int) old('deleting_model_id') === (int) $manufacturerModel->id" focusable maxWidth="md">
                <form method="POST" action="{{ route('manufacturers.models.destroy', [$manufacturer, $manufacturerModel]) }}" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="deleting_model_id" value="{{ $manufacturerModel->id }}">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Delete “{{ $manufacturer->name }} / {{ $manufacturerModel->name }}”?
                    </h2>
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                        @if($manufacturerModel->assets_count > 0)
                            This model is used by {{ $manufacturerModel->assets_count }} {{ $manufacturerModel->assets_count === 1 ? 'asset' : 'assets' }}.
                            Choose a replacement model for those assets, then delete.
                        @else
                            No assets use this model. Choose a replacement model, then delete.
                        @endif
                    </p>
                    <div>
                        <x-input-label for="replacement_manufacturer_model_id_{{ $manufacturerModel->id }}" value="Replacement model" />
                        <select
                            id="replacement_manufacturer_model_id_{{ $manufacturerModel->id }}"
                            name="replacement_manufacturer_model_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            required
                        >
                            <option value="">Select a model…</option>
                            @foreach($otherModels as $option)
                                <option value="{{ $option['id'] }}" @selected((string) old('replacement_manufacturer_model_id') === (string) $option['id'] && (int) old('deleting_model_id') === (int) $manufacturerModel->id)>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('replacement_manufacturer_model_id')" class="mt-2" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-danger-button>Delete permanently</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endrole
</li>

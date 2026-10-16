<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $group->exists ? 'Edit group' : 'New spare group' }}</h2></x-slot>

    <div class="max-w-3xl mx-auto px-4" x-data="{
        items: @js(collect(old('items', $group->items->map(fn ($i) => ['spare_part_id' => $i->spare_part_id, 'quantity' => $i->quantity, 'notes' => $i->notes])->values()->all() ?: [['spare_part_id' => '', 'quantity' => 1, 'notes' => '']]))->values(),
        addRow() { this.items.push({ spare_part_id: '', quantity: 1, notes: '' }); },
        removeRow(index) { this.items.splice(index, 1); }
    }">
        <form method="post" action="{{ $group->exists ? route('spares.groups.update', $group) : route('spares.groups.store') }}" class="space-y-4 text-sm">
            @csrf
            @if($group->exists) @method('PUT') @endif

            <div>
                <label class="block text-xs">Name</label>
                <input name="name" value="{{ old('name', $group->name) }}" required class="w-full border px-2 py-1">
            </div>
            <div>
                <label class="block text-xs">Description</label>
                <textarea name="description" rows="2" class="w-full border px-2 py-1">{{ old('description', $group->description) }}</textarea>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs">Location</label>
                    <select name="location_id" class="w-full border px-2 py-1">
                        <option value="">—</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected((string) old('location_id', $group->location_id) === (string) $loc->id)>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs">Linked system asset</label>
                    <select name="asset_id" class="w-full border px-2 py-1">
                        <option value="">—</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" @selected((string) old('asset_id', $group->asset_id) === (string) $asset->id)>{{ $asset->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <h3 class="font-medium">Members</h3>
                    <button type="button" @click="addRow()" class="text-xs px-2 py-1 border">Add line</button>
                </div>
                <template x-for="(row, index) in items" :key="index">
                    <div class="grid sm:grid-cols-12 gap-2 items-end border border-brand-silver p-2">
                        <div class="sm:col-span-6">
                            <label class="block text-xs">Spare</label>
                            <select :name="`items[${index}][spare_part_id]`" x-model="row.spare_part_id" class="w-full border px-2 py-1" required>
                                <option value="">Select…</option>
                                @foreach($spareOptions as $spare)
                                    <option value="{{ $spare->id }}">{{ $spare->code }} — {{ $spare->component }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs">Qty</label>
                            <input type="number" min="1" :name="`items[${index}][quantity]`" x-model="row.quantity" class="w-full border px-2 py-1">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-xs">Notes</label>
                            <input :name="`items[${index}][notes]`" x-model="row.notes" class="w-full border px-2 py-1">
                        </div>
                        <div class="sm:col-span-1">
                            <button type="button" @click="removeRow(index)" class="text-xs text-red-600">×</button>
                        </div>
                    </div>
                </template>
            </div>

            <button type="submit" class="px-4 py-2 bg-brand text-white">Save</button>
        </form>
    </div>
</x-app-layout>

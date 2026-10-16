<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $procedure->exists ? 'Edit procedure' : 'New repair procedure' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4" x-data="{
        steps: @js(collect(old('steps', $procedure->exists ? $procedure->steps->map(fn ($s) => [
            'title' => $s->title,
            'body' => $s->body,
            'is_required' => $s->is_required,
        ])->values()->all() : [['title' => '', 'body' => '', 'is_required' => true]]))->values()),
        parts: @js(collect(old('recommended_parts', $procedure->exists ? $procedure->recommendedSpareParts->map(fn ($p) => [
            'spare_part_id' => $p->id,
            'quantity' => $p->pivot->quantity,
        ])->values()->all() : []))->values()),
        addStep() { this.steps.push({ title: '', body: '', is_required: true }); },
        removeStep(i) { this.steps.splice(i, 1); },
        addPart() { this.parts.push({ spare_part_id: '', quantity: 1 }); },
        removePart(i) { this.parts.splice(i, 1); },
    }">
        @if($errors->any())
            <div class="mb-3 text-sm text-red-600 space-y-1">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form
            method="post"
            action="{{ $procedure->exists ? route('repair-procedures.update', $procedure) : route('repair-procedures.store') }}"
            class="space-y-4 text-sm"
        >
            @csrf
            @if($procedure->exists) @method('PUT') @endif

            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $procedure->title)" required maxlength="255" />
            </div>
            <div>
                <x-input-label for="summary" value="Summary" />
                <textarea id="summary" name="summary" rows="2" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">{{ old('summary', $procedure->summary) }}</textarea>
            </div>
            <div>
                <x-input-label for="safety_notes" value="Safety notes" />
                <textarea id="safety_notes" name="safety_notes" rows="3" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">{{ old('safety_notes', $procedure->safety_notes) }}</textarea>
            </div>
            <div>
                <x-input-label for="tools_required" value="Tools required" />
                <textarea id="tools_required" name="tools_required" rows="2" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">{{ old('tools_required', $procedure->tools_required) }}</textarea>
            </div>
            <div>
                <x-input-label for="estimated_minutes" value="Estimated minutes" />
                <x-text-input id="estimated_minutes" name="estimated_minutes" type="number" min="1" class="mt-1 block w-full" :value="old('estimated_minutes', $procedure->estimated_minutes)" />
            </div>

            <div class="grid sm:grid-cols-3 gap-3">
                <div>
                    <x-input-label for="manufacturer_model_id" value="Manufacturer model" />
                    <select id="manufacturer_model_id" name="manufacturer_model_id" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">
                        <option value="">—</option>
                        @foreach($models as $model)
                            <option value="{{ $model->id }}" @selected((string) old('manufacturer_model_id', $procedure->manufacturer_model_id) === (string) $model->id)>
                                {{ $model->manufacturer?->name }} {{ $model->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="item_type_id" value="Item type" />
                    <select id="item_type_id" name="item_type_id" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">
                        <option value="">—</option>
                        @foreach($itemTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('item_type_id', $procedure->item_type_id) === (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="spare_part_id" value="Spare part" />
                    <select id="spare_part_id" name="spare_part_id" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">
                        <option value="">—</option>
                        @foreach($spareOptions as $spare)
                            <option value="{{ $spare->id }}" @selected((string) old('spare_part_id', $procedure->spare_part_id) === (string) $spare->id)>{{ $spare->code }} — {{ $spare->component }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <section class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-medium">Checklist steps</h3>
                    <button type="button" @click="addStep()" class="text-xs px-2 py-1 border">Add step</button>
                </div>
                <template x-for="(step, index) in steps" :key="index">
                    <div class="space-y-2 border border-brand-silver/60 p-2">
                        <input type="text" :name="`steps[${index}][title]`" x-model="step.title" placeholder="Step title" class="w-full border px-2 py-1" required>
                        <textarea :name="`steps[${index}][body]`" x-model="step.body" rows="2" placeholder="Details" class="w-full border px-2 py-1"></textarea>
                        <div class="flex justify-between items-center">
                            <label class="inline-flex items-center gap-2 text-xs">
                                <input type="hidden" :name="`steps[${index}][is_required]`" value="0">
                                <input type="checkbox" :name="`steps[${index}][is_required]`" value="1" x-model="step.is_required">
                                Required
                            </label>
                            <button type="button" @click="removeStep(index)" class="text-xs text-red-600">Remove</button>
                        </div>
                    </div>
                </template>
            </section>

            <section class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-medium">Recommended parts</h3>
                    <button type="button" @click="addPart()" class="text-xs px-2 py-1 border">Add part</button>
                </div>
                <template x-for="(row, index) in parts" :key="'p'+index">
                    <div class="grid sm:grid-cols-12 gap-2 items-end">
                        <div class="sm:col-span-8">
                            <select :name="`recommended_parts[${index}][spare_part_id]`" x-model="row.spare_part_id" class="w-full border px-2 py-1" required>
                                <option value="">Select spare…</option>
                                @foreach($spareOptions as $spare)
                                    <option value="{{ $spare->id }}">{{ $spare->code }} — {{ $spare->component }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <input type="number" min="1" :name="`recommended_parts[${index}][quantity]`" x-model="row.quantity" class="w-full border px-2 py-1">
                        </div>
                        <div class="sm:col-span-1">
                            <button type="button" @click="removePart(index)" class="text-xs text-red-600">×</button>
                        </div>
                    </div>
                </template>
            </section>

            <div class="flex justify-between gap-2">
                <a href="{{ $procedure->exists ? route('repair-procedures.show', $procedure) : route('repair-procedures.index') }}" class="px-4 py-2 border">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

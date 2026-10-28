<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">{{ $procedure->title }}</h2>
                @if($procedure->summary)
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $procedure->summary }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('repair-procedures.edit', $procedure) }}" data-history-roundtrip class="px-3 py-2 border">Edit</a>
                <a href="{{ route('repair-procedures.index') }}" class="px-3 py-2 border">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-6 text-sm">
        @if(session('status'))
            <p class="text-brand">{{ session('status') }}</p>
        @endif

        <dl class="grid sm:grid-cols-2 gap-3 border border-brand-silver dark:border-brand-charcoal p-4">
            <div><dt class="text-xs text-brand-charcoal">Model</dt><dd>{{ $procedure->manufacturerModel?->name ?: '—' }}</dd></div>
            <div><dt class="text-xs text-brand-charcoal">Item type</dt><dd>{{ $procedure->itemType?->name ?: '—' }}</dd></div>
            <div><dt class="text-xs text-brand-charcoal">Spare</dt><dd>{{ $procedure->sparePart?->displayLabel() ?: '—' }}</dd></div>
            <div><dt class="text-xs text-brand-charcoal">Est. minutes</dt><dd>{{ $procedure->estimated_minutes ?: '—' }}</dd></div>
        </dl>

        @if($procedure->safety_notes)
            <section class="border border-amber-500/50 bg-amber-50 dark:bg-amber-950/20 p-4">
                <h3 class="font-semibold mb-1">Safety</h3>
                <p class="whitespace-pre-wrap">{{ $procedure->safety_notes }}</p>
            </section>
        @endif

        @if($procedure->tools_required)
            <section class="border border-brand-silver dark:border-brand-charcoal p-4">
                <h3 class="font-semibold mb-1">Tools</h3>
                <p class="whitespace-pre-wrap">{{ $procedure->tools_required }}</p>
            </section>
        @endif

        <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-2">
            <h3 class="font-semibold">Steps</h3>
            <ol class="list-decimal list-inside space-y-2">
                @forelse($procedure->steps as $step)
                    <li>
                        <span class="font-medium">{{ $step->title }}</span>
                        @if($step->is_required)<span class="text-xs text-brand ml-1">required</span>@endif
                        @if($step->body)<p class="ml-5 text-brand-charcoal dark:text-brand-silver whitespace-pre-wrap">{{ $step->body }}</p>@endif
                    </li>
                @empty
                    <li class="list-none text-brand-charcoal">No steps defined.</li>
                @endforelse
            </ol>
        </section>

        <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-2">
            <h3 class="font-semibold">Recommended parts</h3>
            <ul class="space-y-1">
                @forelse($procedure->recommendedSpareParts as $part)
                    <li>
                        <a href="{{ route('spares.show', $part) }}" class="text-brand">{{ $part->code }}</a>
                        — {{ $part->component }} × {{ $part->pivot->quantity }}
                    </li>
                @empty
                    <li class="text-brand-charcoal">None</li>
                @endforelse
            </ul>
        </section>

        <section class="border border-brand-silver dark:border-brand-charcoal p-4">
            <h3 class="font-semibold mb-2">Attachments</h3>
            <x-file-attachments
                linkable-type="repair_procedure"
                :linkable-id="$procedure->id"
                :rows="$fileRows"
                :can-mutate="true"
                :categories="$fileCategories"
            />
        </section>

        <form method="post" action="{{ route('repair-procedures.destroy', $procedure) }}" onsubmit="return confirm('Delete this procedure?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600">Delete procedure</button>
        </form>
    </div>
</x-app-layout>

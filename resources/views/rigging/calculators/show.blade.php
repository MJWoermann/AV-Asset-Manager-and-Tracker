@php
    $eventsPayload = $events->map(fn ($e) => [
        'id' => $e->id,
        'label' => $e->name.($e->event_date ? ' ('.$e->event_date->format('Y-m-d').')' : ''),
    ])->values()->all();
    $itemTypesPayload = $itemTypes->map(fn ($t) => [
        'id' => $t->id,
        'name' => $t->name,
        'rigging_weight_kg' => $t->rigging_weight_kg,
    ])->values()->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl">{{ $label }}</h2>
            <a href="{{ route('rigging.calculators.index') }}" class="text-sm text-brand">All calculators</a>
        </div>
    </x-slot>

    <div
        class="max-w-5xl mx-auto px-4 space-y-6"
        x-data="riggingCalculator({
            calculator: @js($calculator),
            computeUrl: @js($computeUrl),
            saveUrl: @js($saveUrl),
            csrf: @js(csrf_token()),
            events: @js($eventsPayload),
            itemTypes: @js($itemTypesPayload),
        })"
    >
        <p class="text-xs text-brand-charcoal dark:text-brand-silver border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate px-3 py-2">
            Safety disclaimer: planning / process aid only — not a substitute for certified structural engineering, venue manuals, or Permit to Work procedures.
        </p>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3">
                <h3 class="font-semibold">Inputs</h3>
                @include('rigging.calculators.partials.inputs')
                <p class="text-sm text-red-600" x-show="error" x-text="error" x-cloak></p>
                <div class="flex flex-wrap gap-2">
                    <x-primary-button type="button" @click="recompute()">Update</x-primary-button>
                </div>
            </div>

            <div class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3">
                <h3 class="font-semibold">Diagram &amp; results</h3>
                <div class="bg-brand-silver/30 dark:bg-black/40 p-2 overflow-x-auto" x-html="diagramSvg"></div>
                <dl class="text-sm space-y-1" x-show="results">
                    <template x-for="([key, value]) in resultEntries" :key="key">
                        <div class="flex justify-between gap-3 border-b border-brand-silver/60 dark:border-brand-charcoal py-1">
                            <dt class="text-brand-charcoal dark:text-brand-silver" x-text="key"></dt>
                            <dd class="font-medium" x-text="formatValue(value)"></dd>
                        </div>
                    </template>
                </dl>
            </div>
        </div>

        <form method="POST" :action="saveUrl" class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3" @submit="prepareSave($event)">
            @csrf
            <h3 class="font-semibold">Save to team log</h3>
            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="mt-1 block w-full" x-model="title" />
            </div>
            <div>
                <x-input-label for="rig_event_id" value="Link to Rig Event (optional)" />
                <select id="rig_event_id" name="rig_event_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" x-model="rigEventId">
                    <option value="">— None —</option>
                    <template x-for="ev in events" :key="ev.id">
                        <option :value="ev.id" x-text="ev.label"></option>
                    </template>
                </select>
            </div>
            <input type="hidden" name="inputs" :value="JSON.stringify(inputs)">
            <x-primary-button type="submit" x-bind:disabled="!results">Save calculation</x-primary-button>
        </form>

        @include('rigging.calculators.partials.team-log', ['logs' => $logs, 'calculator' => $calculator])
    </div>
</x-app-layout>

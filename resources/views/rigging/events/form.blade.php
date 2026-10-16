@php
    $checklistLabels = [
        'contractor_docs' => 'Contractor docs supplied (load plan ≥10 working days, method statement + WH permit, risk assessment if needed, equipment serviceability)',
        'avpm_review' => 'AVPM / Project Coordinator initial review vs Venue Rigging Manual',
        'head_rigger_escalation' => 'Head Rigger escalation if needed (non-straight truss / cantilevers; any point >80% manual limit; complex configs)',
        'engineer_review' => 'Engineer review when required (flag + evidence)',
        'approved_plan_returned' => 'Approved plan returned to contractor / latest version noted',
        'security_ptw' => 'Security issues plan + Working-at-Heights permit (external reminder)',
        'variations_noted' => 'Variations noted (supervising rigger + venue head rigger / Senior AV PM)',
        'structure_signoff' => 'Works complete structure sign-off',
        'return_to_security' => 'Return signed plan + permit to Security / Momentus upload (external reminder)',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl">{{ $event->exists ? $event->name : 'New Rig Event' }}</h2>
            <a href="{{ route('rigging.events.index') }}" class="text-sm text-brand">All events</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 space-y-8 pb-10">
        <p class="text-xs text-brand-charcoal dark:text-brand-silver border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate px-3 py-2">
            Process aid only — incomplete checklist items never block save or sign-off. Not a substitute for Momentus / Security PTW.
        </p>

        {{-- Overview --}}
        <section class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3">
            <h3 class="font-semibold text-lg">Overview</h3>
            <form method="POST" action="{{ $event->exists ? route('rigging.events.update', $event) : route('rigging.events.store') }}" class="space-y-3">
                @csrf
                @if($event->exists) @method('PUT') @endif

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $event->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <x-input-label for="venue_space" value="Venue space" />
                        <x-text-input id="venue_space" name="venue_space" class="mt-1 block w-full" :value="old('venue_space', $event->venue_space)" />
                    </div>
                    <div>
                        <x-input-label for="event_date" value="Event date" />
                        <x-text-input id="event_date" name="event_date" type="date" class="mt-1 block w-full" :value="old('event_date', optional($event->event_date)->format('Y-m-d'))" />
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $event->status?->value ?? 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="asset_list_id" value="Scanned / event list (optional)" />
                        <select id="asset_list_id" name="asset_list_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            <option value="">— None —</option>
                            @foreach($eventLists as $list)
                                <option value="{{ $list->id }}" @selected((string) old('asset_list_id', $event->asset_list_id) === (string) $list->id)>{{ $list->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-3">
                    <p class="text-sm font-medium">Engineer</p>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="engineer_required" value="0">
                        <input type="checkbox" name="engineer_required" value="1" class="rounded text-brand" @checked(old('engineer_required', $event->engineer_required))>
                        Engineer review required
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="engineer_acquired" value="0">
                        <input type="checkbox" name="engineer_acquired" value="1" class="rounded text-brand" @checked(old('engineer_acquired', $event->engineer_acquired))>
                        Engineer approval acquired
                    </label>
                    <div>
                        <x-input-label for="engineer_notes" value="Engineer notes" />
                        <textarea id="engineer_notes" name="engineer_notes" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('engineer_notes', $event->engineer_notes) }}</textarea>
                    </div>
                </div>

                <div>
                    <x-input-label for="variations_notes" value="Variations" />
                    <textarea id="variations_notes" name="variations_notes" rows="3" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('variations_notes', $event->variations_notes) }}</textarea>
                </div>
                <div>
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('notes', $event->notes) }}</textarea>
                </div>

                @if($event->exists)
                    @foreach(($checklist ?? []) as $key => $checked)
                        <input type="hidden" name="process_checklist[{{ $key }}]" value="{{ $checked ? '1' : '0' }}">
                    @endforeach
                @endif

                <x-primary-button>Save overview</x-primary-button>
            </form>
        </section>

        @if($event->exists)
            {{-- Checklist --}}
            <section class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3">
                <h3 class="font-semibold text-lg">Process checklist</h3>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">Recommended only — never blocks save or sign-off.</p>
                <form method="POST" action="{{ route('rigging.events.checklist', $event) }}" class="space-y-2">
                    @csrf
                    @method('PUT')
                    @foreach($checklistLabels as $key => $label)
                        <label class="flex items-start gap-2 text-sm">
                            <input type="hidden" name="process_checklist[{{ $key }}]" value="0">
                            <input type="checkbox" name="process_checklist[{{ $key }}]" value="1" class="rounded text-brand mt-0.5" @checked(!empty($checklist[$key]))>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    <x-secondary-button type="submit">Save checklist</x-secondary-button>
                </form>
            </section>

            {{-- Escalation callouts --}}
            <section class="border border-amber-600/40 bg-white dark:bg-brand-slate p-4 text-sm space-y-1">
                <h3 class="font-semibold">Escalation guidance</h3>
                <ul class="list-disc pl-5 text-brand-charcoal dark:text-brand-silver space-y-1">
                    <li>Non-straight truss / cantilevers</li>
                    <li>Any point load &gt;80% of Venue Rigging Manual limit</li>
                    <li>Complex configurations — escalate to Head Rigger</li>
                </ul>
            </section>

            {{-- Files --}}
            <section class="space-y-3">
                <h3 class="font-semibold text-lg">Plan documents &amp; photos</h3>
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">Use category “Rigging plan” for plans; “Photo” for site photos (mobile capture supported).</p>
                <x-file-attachments
                    linkable-type="rig_event"
                    :linkable-id="$event->id"
                    :rows="$fileRows"
                    :categories="$categories ?? \App\Enums\FileCategory::options()"
                />
            </section>

            {{-- Motor patches --}}
            <section
                class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3"
                x-data="rigEventMotorPatches(@js($motorPatches))"
            >
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-lg">Motor patching</h3>
                    <button type="button" class="text-sm text-brand" @click="addRow()">+ Add row</button>
                </div>
                <form method="POST" action="{{ route('rigging.events.motor-patches.sync', $event) }}">
                    @csrf
                    @method('PUT')
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left">
                                <tr>
                                    <th class="px-2 py-1">#</th>
                                    <th class="px-2 py-1">Motor / point</th>
                                    <th class="px-2 py-1">Patch to</th>
                                    <th class="px-2 py-1">Notes</th>
                                    <th class="px-2 py-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, idx) in rows" :key="idx">
                                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                                        <td class="px-2 py-1">
                                            <input type="hidden" :name="'motor_patches['+idx+'][sort_order]'" :value="idx">
                                            <span x-text="idx + 1"></span>
                                        </td>
                                        <td class="px-2 py-1">
                                            <input type="text" class="w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" :name="'motor_patches['+idx+'][motor_label]'" x-model="row.motor_label" required>
                                        </td>
                                        <td class="px-2 py-1">
                                            <input type="text" class="w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" :name="'motor_patches['+idx+'][patch_to]'" x-model="row.patch_to">
                                        </td>
                                        <td class="px-2 py-1">
                                            <input type="text" class="w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" :name="'motor_patches['+idx+'][notes]'" x-model="row.notes">
                                        </td>
                                        <td class="px-2 py-1">
                                            <button type="button" class="text-red-600 text-xs" @click="removeRow(idx)">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <x-secondary-button type="submit">Save motor patches</x-secondary-button>
                    </div>
                </form>
            </section>

            {{-- Linked calculations --}}
            <section class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3">
                <h3 class="font-semibold text-lg">Linked calculations</h3>
                @if($calculationLogs->isEmpty())
                    <p class="text-sm text-brand-charcoal dark:text-brand-silver">No calculations linked yet.</p>
                @else
                    <ul class="divide-y divide-brand-silver dark:divide-brand-charcoal text-sm">
                        @foreach($calculationLogs as $log)
                            <li class="py-2 flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <a href="{{ route('rigging.calculators.show', $log->calculator) }}" class="text-brand font-medium">{{ \App\Support\RiggingCalculators::label($log->calculator) }}</a>
                                    <span class="text-brand-charcoal dark:text-brand-silver"> — {{ $log->title }}</span>
                                    <div class="text-xs text-brand-charcoal dark:text-brand-silver">{{ $log->creator?->name }} · {{ $log->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                <form method="POST" action="{{ route('rigging.events.calculations.detach', [$event, $log]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600">Detach</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('rigging.events.calculations.attach', $event) }}" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <div class="flex-1 min-w-[12rem]">
                        <x-input-label for="rigging_calculation_log_id" value="Attach from team log" />
                        <select id="rigging_calculation_log_id" name="rigging_calculation_log_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm" required>
                            <option value="">Select unlinked calculation…</option>
                            @foreach($unlinkedLogs as $log)
                                <option value="{{ $log->id }}">
                                    {{ \App\Support\RiggingCalculators::label($log->calculator) }} — {{ $log->title }} ({{ $log->created_at?->format('Y-m-d') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <x-secondary-button type="submit">Attach</x-secondary-button>
                </form>
            </section>

            {{-- Sign-off --}}
            <section
                class="border border-brand-silver dark:border-brand-charcoal bg-white dark:bg-brand-slate p-4 space-y-3"
                x-data="rigEventSignOff()"
            >
                <h3 class="font-semibold text-lg">Structure sign-off</h3>
                @if($event->isSignedOff())
                    <div class="border border-brand bg-brand/5 px-3 py-2 text-sm">
                        Signed by <strong>{{ $event->structure_signed_name }}</strong>
                        at {{ $event->structure_signed_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}.
                        @if($event->structure_notes)
                            <div class="mt-1 text-brand-charcoal dark:text-brand-silver">{{ $event->structure_notes }}</div>
                        @endif
                    </div>
                @endif
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                    Acknowledges works completed per approved plan and noted variations. Planning aid only.
                </p>
                <form method="POST" action="{{ route('rigging.events.sign-off', $event) }}" class="space-y-3" @submit="beforeSubmit">
                    @csrf
                    @if($event->isSignedOff())
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="replace" value="1" class="rounded text-brand" required>
                            Replace existing sign-off
                        </label>
                    @endif
                    <div>
                        <x-input-label for="structure_signed_name" value="Printed name" />
                        <x-text-input id="structure_signed_name" name="structure_signed_name" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="structure_notes" value="Notes" />
                        <textarea id="structure_notes" name="structure_notes" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"></textarea>
                    </div>
                    <div>
                        <p class="text-sm font-medium mb-1">Signature</p>
                        <canvas
                            x-ref="pad"
                            class="w-full h-40 border border-brand-charcoal dark:border-brand-silver bg-white touch-none"
                            width="600"
                            height="160"
                            @pointerdown="startStroke($event)"
                            @pointermove="drawStroke($event)"
                            @pointerup="endStroke"
                            @pointerleave="endStroke"
                        ></canvas>
                        <input type="hidden" name="signature_data" x-ref="signatureData">
                        <button type="button" class="mt-1 text-xs text-brand" @click="clearPad()">Clear signature</button>
                    </div>
                    <x-primary-button type="submit">Record sign-off</x-primary-button>
                </form>
            </section>

            @role('admin|inventory_manager')
                <form method="POST" action="{{ route('rigging.events.destroy', $event) }}" onsubmit="return confirm('Delete this Rig Event?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600">Delete Rig Event</button>
                </form>
            @endrole
        @endif
    </div>
</x-app-layout>

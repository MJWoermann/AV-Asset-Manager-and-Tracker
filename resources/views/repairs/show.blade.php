@php
    use App\Enums\RepairStatus;
    $isActive = $repair->isActive();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">{{ $repair->summary }}</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    <a href="{{ route('assets.show', $repair->asset) }}" class="text-brand">{{ $repair->asset?->name }}</a>
                    @if($repair->asset?->fmi_ast) · {{ $repair->asset->fmi_ast }} @endif
                    · {{ $repair->status?->label() }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('assets.show', [$repair->asset, 'tab' => 'maintenance']) }}" class="px-3 py-2 border">Maintenance</a>
                <a href="{{ route('repairs.index') }}" class="px-3 py-2 border">All repairs</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-6 text-sm">
        @if(session('status'))
            <p class="text-brand">{{ session('status') }}</p>
        @endif
        @if($errors->any())
            <div class="text-red-600 space-y-1">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-2">
            <h3 class="font-semibold">Fault</h3>
            <p>{{ $repair->maintenanceLog?->summary }}</p>
            @if($repair->maintenanceLog?->body)
                <p class="text-brand-charcoal dark:text-brand-silver whitespace-pre-wrap">{{ $repair->maintenanceLog->body }}</p>
            @endif
            <dl class="grid sm:grid-cols-2 gap-2 pt-2 text-xs">
                <div><dt class="text-brand-charcoal">Assignee</dt><dd>{{ $repair->assignee?->name ?: '—' }}</dd></div>
                <div><dt class="text-brand-charcoal">Started</dt><dd>{{ $repair->started_at?->format('Y-m-d H:i') ?: '—' }}</dd></div>
                @if($repair->procedure)
                    <div class="sm:col-span-2">
                        <dt class="text-brand-charcoal">Procedure</dt>
                        <dd>
                            @role('admin|inventory_manager')
                                <a href="{{ route('repair-procedures.show', $repair->procedure) }}" class="text-brand">{{ $repair->procedure->title }}</a>
                            @else
                                {{ $repair->procedure->title }}
                            @endrole
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        @if($repair->procedure?->safety_notes || $repair->procedure?->tools_required)
            <section class="space-y-3">
                @if($repair->procedure->safety_notes)
                    <div class="border border-amber-500/50 bg-amber-50 dark:bg-amber-950/20 p-4">
                        <h3 class="font-semibold mb-1">Safety</h3>
                        <p class="whitespace-pre-wrap">{{ $repair->procedure->safety_notes }}</p>
                    </div>
                @endif
                @if($repair->procedure->tools_required)
                    <div class="border border-brand-silver dark:border-brand-charcoal p-4">
                        <h3 class="font-semibold mb-1">Tools</h3>
                        <p class="whitespace-pre-wrap">{{ $repair->procedure->tools_required }}</p>
                    </div>
                @endif
            </section>
        @endif

        <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
            <h3 class="font-semibold">Checklist</h3>
            @forelse($repair->steps as $step)
                <div class="border border-brand-silver/60 p-3 space-y-2">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $step->title }}</p>
                            @if($step->is_required)<span class="text-xs text-brand">Required</span>@endif
                            @if($step->body)<p class="mt-1 text-brand-charcoal dark:text-brand-silver whitespace-pre-wrap">{{ $step->body }}</p>@endif
                            @if($step->completed_at)
                                <p class="mt-1 text-xs text-brand-charcoal">Done {{ $step->completed_at->format('Y-m-d H:i') }} by {{ $step->completedBy?->name ?? '—' }}</p>
                            @endif
                        </div>
                        @if($isActive)
                            <form method="post" action="{{ route('repairs.steps.update', [$repair, $step]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="completed" value="{{ $step->completed_at ? '0' : '1' }}">
                                <button type="submit" class="px-3 py-1 border text-xs {{ $step->completed_at ? '' : 'bg-brand text-white border-brand' }}">
                                    {{ $step->completed_at ? 'Undo' : 'Done' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-brand-charcoal dark:text-brand-silver">No procedure steps — use the blank checklist below and reference docs.</p>
            @endforelse

            @if($isActive)
                <form method="post" action="{{ route('repairs.steps.store', $repair) }}" class="space-y-2 pt-2 border-t border-brand-silver dark:border-brand-charcoal">
                    @csrf
                    <x-input-label for="adhoc_title" value="Add checklist step" />
                    <x-text-input id="adhoc_title" name="title" class="mt-1 block w-full" required maxlength="255" />
                    <textarea name="body" rows="2" class="block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white" placeholder="Optional details"></textarea>
                    <label class="inline-flex items-center gap-2 text-xs">
                        <input type="hidden" name="is_required" value="0">
                        <input type="checkbox" name="is_required" value="1" checked>
                        Required
                    </label>
                    <div class="flex justify-end">
                        <x-primary-button>Add step</x-primary-button>
                    </div>
                </form>
            @endif
        </section>

        @if($guidanceFiles->isNotEmpty())
            <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-2">
                <h3 class="font-semibold">Reference docs</h3>
                <ul class="space-y-1">
                    @foreach($guidanceFiles as $file)
                        <li class="flex flex-wrap gap-2 items-center">
                            <span>{{ $file->title }}</span>
                            <span class="text-xs text-brand-charcoal">({{ $file->category?->label() }})</span>
                            <a href="{{ route('files.download', $file) }}" class="text-xs text-brand">Download</a>
                            @if($file->isPreviewableImage() || $file->isUrl())
                                <a href="{{ route('files.preview', $file) }}" class="text-xs text-brand" target="_blank" rel="noopener">Preview</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
            <h3 class="font-semibold">Parts</h3>

            @if($repair->procedure?->recommendedSpareParts?->isNotEmpty())
                <div>
                    <h4 class="text-xs font-medium text-brand-charcoal mb-1">Recommended</h4>
                    <ul class="space-y-1">
                        @foreach($repair->procedure->recommendedSpareParts as $part)
                            <li>
                                <a href="{{ route('spares.show', $part) }}" class="text-brand">{{ $part->code }}</a>
                                — {{ $part->component }} × {{ $part->pivot->quantity }}
                                (on hand: {{ $part->quantity_on_hand }})
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($isActive)
                <form method="post" action="{{ route('repairs.parts.store', $repair) }}" class="space-y-2">
                    @csrf
                    <div class="grid sm:grid-cols-2 gap-2">
                        <div>
                            <x-input-label for="spare_part_id" value="Spare" />
                            <select id="spare_part_id" name="spare_part_id" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white" required>
                                <option value="">Select…</option>
                                @if($compatibleSpares->isNotEmpty())
                                    <optgroup label="Compatible">
                                        @foreach($compatibleSpares as $spare)
                                            <option value="{{ $spare->id }}">{{ $spare->code }} — {{ $spare->component }} ({{ $spare->quantity_on_hand }})</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                <optgroup label="All active">
                                    @foreach($spareOptions as $spare)
                                        <option value="{{ $spare->id }}">{{ $spare->code }} — {{ $spare->component }} ({{ $spare->quantity_on_hand }})</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="part_type" value="Type" />
                            <select id="part_type" name="type" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white" required>
                                @foreach($movementTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="part_qty" value="Quantity" />
                            <x-text-input id="part_qty" name="quantity" type="number" min="1" value="1" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="part_reason" value="Reason" />
                            <x-text-input id="part_reason" name="reason" class="mt-1 block w-full" :value="old('reason', $repair->summary)" required />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <x-primary-button>Issue spare</x-primary-button>
                    </div>
                </form>
            @endif

            <div>
                <h4 class="text-xs font-medium text-brand-charcoal mb-1">Issued on this repair</h4>
                <ul class="space-y-1">
                    @forelse($repair->movements as $movement)
                        <li>
                            {{ $movement->recorded_at?->format('Y-m-d H:i') }}
                            — {{ $movement->type?->label() }}
                            {{ $movement->sparePart?->code }}
                            ({{ $movement->quantity_delta }})
                            — {{ $movement->user?->name }}
                        </li>
                    @empty
                        <li class="text-brand-charcoal">No parts issued yet.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        <section class="border border-brand-silver dark:border-brand-charcoal p-4">
            <h3 class="font-semibold mb-2">Photos / notes files</h3>
            <x-file-attachments
                linkable-type="repair"
                :linkable-id="$repair->id"
                :rows="$fileRows"
                :can-mutate="$isActive"
                :categories="$fileCategories"
            />
        </section>

        @if($repair->resolution_summary)
            <section class="border border-brand-silver dark:border-brand-charcoal p-4">
                <h3 class="font-semibold mb-1">{{ $repair->status === RepairStatus::Cancelled ? 'Cancellation' : 'Resolution' }}</h3>
                <p class="whitespace-pre-wrap">{{ $repair->resolution_summary }}</p>
                @if($repair->completed_at)
                    <p class="mt-1 text-xs text-brand-charcoal">{{ $repair->completed_at->format('Y-m-d H:i') }} by {{ $repair->completedBy?->name ?? '—' }}</p>
                @endif
            </section>
        @endif

        @if($isActive)
            <section class="space-y-4" x-data="{ mode: 'complete' }">
                <div class="flex gap-2">
                    <button type="button" @click="mode = 'complete'" class="px-3 py-1 border text-xs" :class="mode === 'complete' && 'bg-brand text-white border-brand'">Complete</button>
                    @unlessrole('external_repairer')
                        <button type="button" @click="mode = 'cancel'" class="px-3 py-1 border text-xs" :class="mode === 'cancel' && 'border-red-600 text-red-600'">Cancel repair</button>
                    @endunlessrole
                </div>

                <form x-show="mode === 'complete'" method="post" action="{{ route('repairs.complete', $repair) }}" class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="resolution_summary" value="Resolution summary" />
                        <textarea id="resolution_summary" name="resolution_summary" rows="3" required class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">{{ old('resolution_summary') }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="asset_status" value="Set asset status (optional)" />
                        <select id="asset_status" name="asset_status" class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">
                            <option value="">No change</option>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('asset_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span></span>
                        <x-primary-button>Complete repair</x-primary-button>
                    </div>
                </form>

                @unlessrole('external_repairer')
                <form x-show="mode === 'cancel'" x-cloak method="post" action="{{ route('repairs.cancel', $repair) }}" class="border border-red-600/40 p-4 space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="cancel_reason" value="Cancellation reason" />
                        <textarea id="cancel_reason" name="reason" rows="3" required class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white">{{ old('reason') }}</textarea>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-xs text-brand-charcoal">Stock is not reversed automatically.</span>
                        <button type="submit" class="px-4 py-2 border border-red-600 text-red-600">Cancel repair</button>
                    </div>
                </form>
                @endunlessrole
            </section>
        @endif
    </div>
</x-app-layout>

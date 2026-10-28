<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl font-mono">{{ $spare->code }}</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $spare->component }}</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                @can('spares:manage')
                    <a href="{{ route('spares.edit', $spare) }}" class="px-3 py-2 border">Edit</a>
                @endcan
                @can('labels:read')
                    @feature('shelf_tag_generator')
                        <a href="{{ route('spares.label', $spare) }}" class="px-3 py-2 border">Print label</a>
                    @endfeature
                @endcan
                <a href="{{ route('spares.index') }}" class="px-3 py-2 border">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 space-y-6">
        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif

        @if($spare->supersededBy)
            <div class="border border-amber-500 bg-amber-50 dark:bg-amber-950/30 px-4 py-3 text-sm">
                Superseded — use
                <a href="{{ route('spares.show', $spare->supersededBy) }}" class="text-brand font-semibold">
                    {{ $spare->supersededBy->code }} {{ $spare->supersededBy->component }}
                </a>
                instead.
            </div>
        @endif

        @if($spare->supersedes->isNotEmpty())
            <div class="border border-brand-silver dark:border-brand-charcoal px-4 py-3 text-sm">
                <p class="font-medium">Supersedes</p>
                <ul class="mt-1 list-disc list-inside">
                    @foreach($spare->supersedes as $older)
                        <li><a href="{{ route('spares.show', $older) }}" class="text-brand">{{ $older->code }}</a> — {{ $older->component }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <dl class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-4">
                <div><dt class="text-xs text-brand-charcoal dark:text-brand-silver">Kind</dt><dd>{{ $spare->kind?->label() }}</dd></div>
                @if($spare->linkedAsset)
                    <div>
                        <dt class="text-xs text-brand-charcoal dark:text-brand-silver">Listed asset</dt>
                        <dd><a href="{{ route('assets.show', $spare->linkedAsset) }}" class="text-brand hover:underline">{{ $spare->linkedAsset->name }}</a></dd>
                    </div>
                @endif
                <div><dt class="text-xs">Brand / model</dt><dd>{{ $spare->manufacturer?->name }} {{ $spare->manufacturerModel?->name }}</dd></div>
                <div><dt class="text-xs">Product code</dt><dd>{{ $spare->product_code ?: '—' }}</dd></div>
                <div><dt class="text-xs">Severity</dt><dd>{{ $spare->severity?->label() }}</dd></div>
                <div><dt class="text-xs">Lifecycle</dt><dd>{{ $spare->lifecycle_status?->label() }}</dd></div>
                <div><dt class="text-xs">Location</dt><dd>{{ $spare->location?->name }} @if($spare->shelf_location)<span class="text-brand-charcoal">/ {{ $spare->shelf_location }}</span>@endif</dd></div>
                @if($suggestedDeployed !== null)
                    <div><dt class="text-xs">Deployed (sheet)</dt><dd>{{ $spare->quantity_deployed }} <span class="text-xs text-brand-charcoal">(assets on model: {{ $suggestedDeployed }})</span></dd></div>
                @endif
            </dl>
            <dl class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-4">
                <div><dt class="text-xs">Supplier</dt><dd>{{ $spare->supplier ?: '—' }}</dd></div>
                <div><dt class="text-xs">Unit cost</dt><dd>{{ $spare->unit_cost !== null ? number_format((float) $spare->unit_cost, 2) : '—' }}</dd></div>
                <div><dt class="text-xs">Lead time</dt><dd>{{ $spare->lead_time ?: '—' }}</dd></div>
                <div><dt class="text-xs">Replacement time</dt><dd>{{ $spare->replacement_time ?: '—' }}</dd></div>
                @if($spare->notes)<div><dt class="text-xs">Notes</dt><dd class="whitespace-pre-wrap">{{ $spare->notes }}</dd></div>@endif
            </dl>
        </div>

        @include('spares.partials.stock-panel', ['spare' => $spare, 'chartPayload' => $chartPayload, 'movementAction' => $movementAction, 'assetOptions' => $assetOptions])

        <div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm" x-data="{ open: {{ $errors->has('asset_id') || old('summary') ? 'true' : 'false' }} }">
            <div class="flex justify-between items-center gap-2">
                <h3 class="font-semibold">Start repair</h3>
                <button type="button" @click="open = !open" class="px-3 py-1 border text-xs" x-text="open ? 'Hide' : 'Start'"></button>
            </div>
            <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Creates a Fault on the asset and opens a guided repair with this spare available to issue.</p>
            <form
                x-show="open"
                x-cloak
                method="post"
                action="{{ route('spares.repairs.store', $spare) }}"
                class="mt-3 space-y-3"
            >
                @csrf
                <div>
                    <label class="block text-xs">Asset</label>
                    <select name="asset_id" required class="mt-1 w-full border px-2 py-1 dark:bg-brand-slate">
                        <option value="">Select asset…</option>
                        @foreach(($repairAssetOptions ?? collect())->concat($assetOptions)->unique('id') as $asset)
                            <option value="{{ $asset->id }}" @selected((string) old('asset_id') === (string) $asset->id)>{{ $asset->name }}</option>
                        @endforeach
                    </select>
                    @error('asset_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs">Summary</label>
                    <input name="summary" value="{{ old('summary', 'Repair: '.$spare->displayLabel()) }}" class="mt-1 w-full border px-2 py-1" maxlength="255">
                </div>
                @if(($suggestedProcedures ?? collect())->isNotEmpty())
                    <div>
                        <label class="block text-xs">Procedure (optional)</label>
                        <select name="repair_procedure_id" class="mt-1 w-full border px-2 py-1 dark:bg-brand-slate">
                            <option value="">Auto-match / blank checklist</option>
                            @foreach($suggestedProcedures as $procedure)
                                <option value="{{ $procedure->id }}" @selected((string) old('repair_procedure_id') === (string) $procedure->id)>{{ $procedure->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-xs">Notes</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full border px-2 py-1">{{ old('notes') }}</textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-3 py-2 bg-brand text-white">Start repair</button>
                </div>
            </form>
        </div>

        <div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm">
            <h3 class="font-semibold mb-2">Movement history</h3>
            <table class="min-w-full text-left">
                <thead class="text-xs text-brand-charcoal dark:text-brand-silver">
                    <tr><th class="py-1 pr-2">When</th><th class="py-1 pr-2">Type</th><th class="py-1 pr-2">Qty</th><th class="py-1 pr-2">Asset</th><th class="py-1 pr-2">Reason</th><th class="py-1">Who</th></tr>
                </thead>
                <tbody>
                    @forelse($movementHistory as $movement)
                        <tr class="border-t border-brand-silver/50">
                            <td class="py-1 pr-2">{{ $movement->recorded_at?->format('Y-m-d H:i') }}</td>
                            <td class="py-1 pr-2">{{ $movement->type?->label() }}</td>
                            <td class="py-1 pr-2">{{ $movement->quantity_before }}→{{ $movement->quantity_after }}</td>
                            <td class="py-1 pr-2">
                                @if($movement->againstAsset)
                                    <a href="{{ route('assets.show', $movement->againstAsset) }}" class="text-brand">{{ $movement->againstAsset->name }}</a>
                                @else — @endif
                            </td>
                            <td class="py-1 pr-2">{{ $movement->reason ?: '—' }}</td>
                            <td class="py-1">{{ $movement->user?->name ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-2 text-brand-charcoal">No movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            <h3 class="font-semibold text-sm mb-2">Procedure documents</h3>
            <x-file-attachments
                linkable-type="spare_part"
                :linkable-id="$spare->id"
                :rows="$fileRows"
                :can-mutate="auth()->user()?->can('files:write') ?? false"
            />
        </div>

        @can('spares:manage')
            <div class="border border-brand-silver dark:border-brand-charcoal p-4 text-sm">
                <h3 class="font-semibold">Mark superseded</h3>
                <form method="post" action="{{ route('spares.supersede', $spare) }}" class="mt-2 flex flex-wrap gap-2 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs">Successor spare</label>
                        <select name="superseded_by_id" required class="border px-2 py-1 min-w-[12rem]">
                            <option value="">Select…</option>
                            @foreach(\App\Models\SparePart::query()->where('id', '!=', $spare->id)->activeLifecycle()->orderBy('code')->get() as $option)
                                <option value="{{ $option->id }}">{{ $option->code }} — {{ $option->component }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-3 py-2 bg-black text-white">Record supersession</button>
                </form>
            </div>
        @endcan
    </div>
</x-app-layout>

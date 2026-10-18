@php
    $chart = $chartPayload ?? [];
    $initialAction = old('type', $movementAction ?? 'count');
@endphp

<div
    class="space-y-6"
    x-data="inventoryChartPanel(@js([
        'chart' => $chart,
        'initialAction' => $initialAction,
        'showAnnotationLabels' => true,
        'showLegend' => true,
    ]))"
>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs text-brand-charcoal dark:text-brand-silver">On hand</p>
            <p class="text-2xl font-semibold">{{ $chart['current_quantity'] ?? $spare->quantity_on_hand }}</p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs">Min</p>
            <p class="text-2xl font-semibold">{{ $chart['min_holding'] ?? '—' }}</p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs">Max</p>
            <p class="text-2xl font-semibold">{{ $chart['max_holding'] ?? '—' }}</p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs">Reorder ETA</p>
            <p class="text-sm font-semibold mt-1">{{ $chart['reorder_eta_label'] ?? '—' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 border border-brand-silver dark:border-brand-charcoal p-3 bg-white dark:bg-brand-slate">
            <canvas x-ref="chart" height="220"></canvas>
            <p class="text-xs text-brand-charcoal dark:text-brand-silver mt-2">Click a point for movement details.</p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3 text-sm space-y-2 min-h-[12rem]">
            <h3 class="font-semibold">Movement detail</h3>
            <template x-if="!selected"><p class="text-brand-charcoal dark:text-brand-silver">Select a chart point.</p></template>
            <template x-if="selected">
                <div class="space-y-1">
                    <p><span class="text-brand-charcoal dark:text-brand-silver">When:</span> <span x-text="selected.recorded_at"></span></p>
                    <p><span class="text-brand-charcoal dark:text-brand-silver">Type:</span> <span x-text="selected.type_label"></span></p>
                    <p><span class="text-brand-charcoal dark:text-brand-silver">Who:</span> <span x-text="selected.user_name || '—'"></span></p>
                    <p x-show="selected.against_asset_name"><span class="text-brand-charcoal dark:text-brand-silver">Asset:</span> <span x-text="selected.against_asset_name"></span></p>
                    <p x-show="selected.reason"><span class="text-brand-charcoal dark:text-brand-silver">Reason:</span> <span x-text="selected.reason"></span></p>
                    <p>
                        <span class="text-brand-charcoal dark:text-brand-silver">Qty:</span>
                        <span x-text="selected.quantity_before + ' → ' + selected.quantity_after"></span>
                    </p>
                </div>
            </template>
        </div>
    </div>

    <form method="post" action="{{ route('spares.movements.store', $spare) }}" enctype="multipart/form-data" class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3 text-sm">
        @csrf
        <h3 class="font-semibold">Record movement</h3>
        @if($spare->isNonActiveLifecycle())
            <p class="text-amber-700 text-xs">This line is {{ $spare->lifecycle_status?->label() }}. Use/replace of remaining stock is allowed; prefer restocking the successor.</p>
        @endif
        <div class="flex flex-wrap gap-2">
            @foreach(\App\Enums\SparePartMovementType::options() as $value => $label)
                <label class="inline-flex items-center gap-1 px-2 py-1 border cursor-pointer" :class="action === '{{ $value }}' ? 'border-brand bg-brand/10' : 'border-brand-silver'">
                    <input type="radio" name="type" value="{{ $value }}" x-model="action" class="sr-only">
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div x-show="['count','restock','use','replace'].includes(action)">
                <label class="block text-xs">Quantity</label>
                <input type="number" min="1" name="quantity" value="{{ old('quantity') }}" class="w-full border px-2 py-1">
            </div>
            <div x-show="action === 'adjustment'">
                <label class="block text-xs">Quantity delta</label>
                <input type="number" name="quantity_delta" value="{{ old('quantity_delta') }}" class="w-full border px-2 py-1">
            </div>
            <div x-show="['use','replace'].includes(action)">
                <label class="block text-xs">Against asset</label>
                <select name="against_asset_id" class="w-full border px-2 py-1">
                    <option value="">Select asset…</option>
                    @foreach($assetOptions as $asset)
                        <option value="{{ $asset->id }}" @selected((string) old('against_asset_id') === (string) $asset->id)>{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="['use','replace'].includes(action)" class="sm:col-span-2">
                <label class="block text-xs">Reason</label>
                <input name="reason" value="{{ old('reason') }}" class="w-full border px-2 py-1">
            </div>
            <div>
                <label class="block text-xs">Ticket ref</label>
                <input name="ticket_ref" value="{{ old('ticket_ref') }}" class="w-full border px-2 py-1">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs">Notes</label>
                <textarea name="notes" rows="2" class="w-full border px-2 py-1">{{ old('notes') }}</textarea>
            </div>
            <div x-show="action === 'restock'">
                <label class="block text-xs">Restock PDFs</label>
                <input type="file" name="pdfs[]" accept="application/pdf" multiple>
            </div>
        </div>
        @if($errors->any())
            <ul class="text-red-600 text-xs space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif
        <button type="submit" class="px-4 py-2 bg-brand text-white">Save movement</button>
    </form>
</div>

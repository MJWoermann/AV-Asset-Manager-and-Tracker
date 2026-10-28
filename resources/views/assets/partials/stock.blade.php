@php
    $chart = $stockChartPayload ?? [];
    $tokens = $stockTokens ?? collect();
    $reorder = $stockReorderMeta ?? [];
    $initialAction = old('type', $stockAction ?? 'count');
    $canManageTags = auth()->user()?->hasAnyRole(['admin', 'inventory_manager']) ?? false;
    $canReviseMovements = auth()->user()?->hasAnyRole(['admin']) ?? false;
    $reviseOld = ($canReviseMovements && old('movement_id') && $errors->any()) ? [
        'id' => old('movement_id'),
        'type' => old('type'),
        'quantity' => old('quantity', old('quantity_delta')),
        'recorded_at' => old('recorded_at'),
        'notes' => old('notes'),
        'external_po_number' => old('external_po_number'),
        'external_po_url' => old('external_po_url'),
    ] : null;
@endphp

<div
    class="space-y-6"
    x-data="inventoryChartPanel(@js([
        'chart' => $chart,
        'initialAction' => $initialAction,
        'openDetailId' => old('highlight_movement_id'),
        'showAnnotationLabels' => true,
        'showLegend' => true,
        'actionHints' => [
            'count' => 'Absolute on-hand quantity after counting',
            'restock' => 'Units added from a restock / PO',
            'breakage' => 'Units removed due to breakage',
        ],
        'revise' => $canReviseMovements ? [
            'enabled' => true,
            'updateUrl' => route('assets.stock-movements.update', [$asset, 0]),
            'deleteUrl' => route('assets.stock-movements.destroy', [$asset, 0]),
            'old' => $reviseOld,
        ] : null,
    ]))"
>
    @if (session('status'))
        <p class="text-sm text-brand">{{ session('status') }}</p>
    @endif

    @if (session('kanban_guest_url'))
        <div class="border border-brand bg-brand/5 px-3 py-2 text-sm space-y-1">
            <p class="font-medium">New Kanban URL for {{ session('kanban_label') }} (shown once):</p>
            <p class="break-all font-mono text-xs">{{ session('kanban_guest_url') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs text-brand-charcoal dark:text-brand-silver">On hand</p>
            <p class="text-2xl font-semibold">{{ $chart['current_quantity'] ?? $asset->quantity ?? 0 }}</p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs text-brand-charcoal dark:text-brand-silver">Min</p>
            <p class="text-2xl font-semibold">{{ $chart['min_stock_level'] ?? '—' }}</p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs text-brand-charcoal dark:text-brand-silver">Max</p>
            <p class="text-2xl font-semibold">{{ $chart['max_stock_level'] ?? '—' }}</p>
        </div>
        <div class="border border-brand-silver dark:border-brand-charcoal p-3">
            <p class="text-xs text-brand-charcoal dark:text-brand-silver">Reorder ETA</p>
            <p class="text-sm font-semibold mt-1">{{ $chart['reorder_eta_label'] ?? '—' }}</p>
        </div>
    </div>

    @if(! empty($reorder['sku']) || ! empty($reorder['supplier']) || ! empty($reorder['url']))
        <div class="text-sm text-brand-charcoal dark:text-brand-silver flex flex-wrap gap-x-4 gap-y-1">
            @if(! empty($reorder['sku']))
                <span>SKU: <span class="text-black dark:text-white">{{ $reorder['sku'] }}</span></span>
            @endif
            @if(! empty($reorder['supplier']))
                <span>Supplier: <span class="text-black dark:text-white">{{ $reorder['supplier'] }}</span></span>
            @endif
            @if(! empty($reorder['url']))
                <a href="{{ $reorder['url'] }}" target="_blank" rel="noopener" class="text-brand">Reorder link</a>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 border border-brand-silver dark:border-brand-charcoal p-3 bg-white dark:bg-brand-slate">
            {{-- Inline height: Chart.js responsive + maintainAspectRatio:false grows unboundedly without a bounded parent --}}
            <div class="relative w-full overflow-hidden" style="height: 240px;">
                <canvas x-ref="chart"></canvas>
            </div>
            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-3 text-xs text-brand-charcoal dark:text-brand-silver">
                <template x-for="item in movementLegend" :key="item.type">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-block w-2.5 h-2.5 rounded-full" :style="`background-color: ${item.color}`"></span>
                        <span x-text="item.label"></span>
                    </span>
                </template>
            </div>
            <p class="text-xs text-brand-charcoal dark:text-brand-silver mt-2">Click a point or an event below for movement details.</p>
            <ul class="mt-2 max-h-40 overflow-y-auto text-sm divide-y divide-brand-silver dark:divide-brand-charcoal">
                <template x-for="point in (chartPayload.points || [])" :key="point.id">
                    <li>
                        <button
                            type="button"
                            class="w-full text-left py-1.5"
                            :class="selected && String(selected.id) === String(point.id) ? 'text-brand' : ''"
                            @click="selected = point; cancelEdit()"
                        >
                            <span class="inline-block w-2.5 h-2.5 rounded-full align-middle" :style="`background-color: ${pointColor(point.type)}`"></span>
                            <span x-text="point.recorded_at"></span>
                            · <span x-text="point.type_label"></span>
                            · <span x-text="point.quantity_before + ' → ' + point.quantity_after"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        <div class="border border-brand-silver dark:border-brand-charcoal p-3 bg-white dark:bg-brand-slate text-sm space-y-2 min-h-[12rem]">
            <h3 class="font-semibold">Movement detail</h3>
            <template x-if="!selected">
                <p class="text-brand-charcoal dark:text-brand-silver">Select a chart point.</p>
            </template>
            <template x-if="selected">
                <div class="space-y-2">
                    <p><span class="text-brand-charcoal dark:text-brand-silver">When:</span> <span x-text="selected.recorded_at"></span></p>
                    <p><span class="text-brand-charcoal dark:text-brand-silver">Type:</span> <span x-text="selected.type_label"></span></p>
                    <p><span class="text-brand-charcoal dark:text-brand-silver">Who:</span> <span x-text="selected.user_name || 'Guest / system'"></span></p>
                    <p>
                        <span class="text-brand-charcoal dark:text-brand-silver">Qty:</span>
                        <span x-text="selected.quantity_before + ' → ' + selected.quantity_after + ' (' + selected.quantity_delta + ')'"></span>
                    </p>
                    <p x-show="selected.notes"><span class="text-brand-charcoal dark:text-brand-silver">Notes:</span> <span x-text="selected.notes"></span></p>
                    <div x-show="selected.external_po_number || selected.external_po_url" class="space-y-1">
                        <p class="font-medium">PO</p>
                        <template x-if="selected.external_po_url">
                            <a :href="selected.external_po_url" target="_blank" rel="noopener" class="text-brand" x-text="selected.external_po_number || 'Open PO'"></a>
                        </template>
                        <template x-if="!selected.external_po_url && selected.external_po_number">
                            <span x-text="selected.external_po_number"></span>
                        </template>
                    </div>
                    <div x-show="selected.files && selected.files.length">
                        <p class="font-medium">PDFs</p>
                        <ul class="list-disc pl-4 space-y-1">
                            <template x-for="file in selected.files" :key="file.id">
                                <li>
                                    <a :href="file.download_url" class="text-brand" x-text="file.title"></a>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <template x-if="revise && revise.enabled && !editing">
                        <div class="flex items-center justify-between gap-2 pt-2">
                            <form method="POST" :action="movementUrl('deleteUrl')" onsubmit="return confirm('Delete this stock event?')">
                                @csrf
                                @method('DELETE')
                                <x-danger-button>Delete</x-danger-button>
                            </form>
                            <x-secondary-button type="button" @click="beginEdit()">Update</x-secondary-button>
                        </div>
                    </template>
                    <template x-if="editing && revise && revise.enabled">
                        <form method="POST" :action="movementUrl('updateUrl')" class="space-y-3 border-t border-brand-silver dark:border-brand-charcoal pt-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="movement_id" :value="selected.id">
                            @if($errors->any() && old('movement_id'))
                                <div class="text-sm text-red-600 space-y-1">
                                    @foreach($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif
                            <div>
                                <label class="text-xs text-brand-charcoal dark:text-brand-silver" for="edit_stock_type">Type</label>
                                <select id="edit_stock_type" name="type" x-model="editForm.type" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                                    <option value="count">Count</option>
                                    <option value="restock">Restock</option>
                                    <option value="breakage">Breakage</option>
                                    <option value="adjustment">Adjustment</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-brand-charcoal dark:text-brand-silver" for="edit_stock_quantity">Quantity</label>
                                <input
                                    id="edit_stock_quantity"
                                    :name="editForm.type === 'adjustment' ? 'quantity_delta' : 'quantity'"
                                    type="number"
                                    x-model="editForm.quantity"
                                    :min="editForm.type === 'adjustment' ? null : 0"
                                    class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                                    required
                                >
                                <p class="text-xs text-brand-charcoal dark:text-brand-silver mt-1" x-text="editForm.type === 'count' ? 'Absolute on-hand quantity' : (editForm.type === 'restock' ? 'Units added to the previous on-hand quantity' : (editForm.type === 'breakage' ? 'Units removed' : 'Signed change from the previous quantity'))"></p>
                            </div>
                            <div>
                                <label class="text-xs text-brand-charcoal dark:text-brand-silver" for="edit_stock_recorded_at">Date</label>
                                <input id="edit_stock_recorded_at" name="recorded_at" type="date" x-model="editForm.recorded_at" max="{{ now()->format('Y-m-d') }}" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                            </div>
                            <div x-show="editForm.type === 'restock'" x-cloak class="space-y-2">
                                <input name="external_po_number" x-model="editForm.external_po_number" placeholder="PO number" class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                                <input name="external_po_url" type="url" x-model="editForm.external_po_url" placeholder="PO URL" class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs text-brand-charcoal dark:text-brand-silver" for="edit_stock_notes">Notes</label>
                                <textarea id="edit_stock_notes" name="notes" rows="2" x-model="editForm.notes" :required="editForm.type === 'breakage'" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <x-secondary-button type="button" @click="cancelEdit()">Cancel</x-secondary-button>
                                <x-primary-button>Save event</x-primary-button>
                            </div>
                        </form>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <form
            method="POST"
            action="{{ route('assets.stock-movements.store', $asset) }}"
            enctype="multipart/form-data"
            class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3 bg-white dark:bg-brand-slate"
        >
            @csrf
            <h3 class="font-semibold text-sm">Record stock movement</h3>

            <div class="flex flex-wrap gap-2">
                @foreach(['count' => 'Count', 'restock' => 'Restock', 'breakage' => 'Breakage'] as $value => $label)
                    <label class="inline-flex items-center gap-1 text-sm border px-2 py-1 cursor-pointer"
                           :class="action === '{{ $value }}' ? 'border-brand text-brand' : 'border-brand-silver dark:border-brand-charcoal'">
                        <input type="radio" name="type" value="{{ $value }}" class="sr-only" x-model="action" @checked(old('type', $initialAction) === $value)>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('type')" class="mt-1" />

            <div>
                <x-input-label for="stock_quantity" value="Quantity" />
                <x-text-input
                    id="stock_quantity"
                    name="quantity"
                    type="number"
                    min="0"
                    class="mt-1 block w-full"
                    :value="old('quantity')"
                    required
                />
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mt-1" x-text="quantityHint"></p>
                <x-input-error :messages="$errors->get('quantity')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="stock_recorded_at" value="Date" />
                <x-text-input
                    id="stock_recorded_at"
                    name="recorded_at"
                    type="date"
                    class="mt-1 block w-full"
                    :value="old('recorded_at', now()->format('Y-m-d'))"
                    :max="now()->format('Y-m-d')"
                    required
                />
                <p class="text-xs text-brand-charcoal dark:text-brand-silver mt-1">Use a past date when entering historical stock movements.</p>
                <x-input-error :messages="$errors->get('recorded_at')" class="mt-1" />
            </div>

            <div x-show="action === 'restock'" x-cloak class="space-y-3">
                <div>
                    <x-input-label for="external_po_number" value="PO number" />
                    <x-text-input id="external_po_number" name="external_po_number" class="mt-1 block w-full" :value="old('external_po_number')" />
                </div>
                <div>
                    <x-input-label for="external_po_url" value="PO URL" />
                    <x-text-input id="external_po_url" name="external_po_url" type="url" class="mt-1 block w-full" :value="old('external_po_url')" />
                </div>
                <div>
                    <x-input-label for="pdfs" value="PO PDF exports" />
                    <input id="pdfs" name="pdfs[]" type="file" accept="application/pdf" multiple class="mt-1 block w-full text-sm">
                    <x-input-error :messages="$errors->get('pdfs')" class="mt-1" />
                    <x-input-error :messages="$errors->get('pdfs.*')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="stock_notes" value="Notes" />
                <textarea id="stock_notes" name="notes" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" :required="action === 'breakage'">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-1" />
            </div>

            <x-primary-button>Save movement</x-primary-button>
        </form>

        <div class="space-y-4">
            <form method="POST" action="{{ route('assets.stock-levels.update', $asset) }}" class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3 bg-white dark:bg-brand-slate">
                @csrf
                @method('PATCH')
                <h3 class="font-semibold text-sm">Min / max levels</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="min_stock_level" value="Min" />
                        <x-text-input id="min_stock_level" name="min_stock_level" type="number" min="0" class="mt-1 block w-full" :value="old('min_stock_level', $asset->min_stock_level)" />
                    </div>
                    <div>
                        <x-input-label for="max_stock_level" value="Max" />
                        <x-text-input id="max_stock_level" name="max_stock_level" type="number" min="0" class="mt-1 block w-full" :value="old('max_stock_level', $asset->max_stock_level)" />
                    </div>
                </div>
                <x-input-error :messages="$errors->get('max_stock_level')" class="mt-1" />
                <x-primary-button>Update levels</x-primary-button>
            </form>

            @if($canManageTags)
                <div class="border border-brand-silver dark:border-brand-charcoal p-4 space-y-3 bg-white dark:bg-brand-slate">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-sm">Kanban tags</h3>
                        <a href="{{ route('assets.kanban-tags.print', $asset) }}" class="text-sm text-brand" target="_blank">Print tags</a>
                    </div>

                    <form method="POST" action="{{ route('assets.stock-tokens.rotate', $asset) }}" class="flex flex-wrap gap-2 items-end">
                        @csrf
                        <div class="grow">
                            <x-input-label for="kanban_label" value="Label" />
                            <select id="kanban_label" name="label" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                                <option value="kanban-a">kanban-a</option>
                                <option value="kanban-b">kanban-b</option>
                            </select>
                        </div>
                        <x-primary-button>Create / rotate</x-primary-button>
                    </form>

                    <ul class="text-sm space-y-2">
                        @forelse($tokens as $token)
                            <li class="flex flex-wrap items-center justify-between gap-2 border-t border-brand-silver dark:border-brand-charcoal pt-2">
                                <div>
                                    <span class="font-medium">{{ $token->label }}</span>
                                    @if($token->revoked_at)
                                        <span class="text-xs text-red-600">revoked</span>
                                    @else
                                        <span class="text-xs text-brand-charcoal dark:text-brand-silver">active</span>
                                    @endif
                                    @if($token->last_used_at)
                                        <span class="block text-xs text-brand-charcoal dark:text-brand-silver">Last used {{ $token->last_used_at->format('Y-m-d H:i') }}</span>
                                    @endif
                                </div>
                                @if(! $token->revoked_at)
                                    <form method="POST" action="{{ route('assets.stock-tokens.revoke', [$asset, $token]) }}" onsubmit="return confirm('Revoke this Kanban token?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600">Revoke</button>
                                    </form>
                                @endif
                            </li>
                        @empty
                            <li class="text-brand-charcoal dark:text-brand-silver">No tokens yet. Create or print tags to generate them.</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

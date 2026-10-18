@php
    $openCheckout = $openCheckout ?? null;
    $checkoutHistory = $checkoutHistory ?? null;
    $canManageCheckouts = $canManageCheckouts ?? false;
    $checkoutPurposes = $checkoutPurposes ?? [];
    $checkoutAssigneeOptions = $checkoutAssigneeOptions ?? collect();
    $checkoutOpenRepairs = $checkoutOpenRepairs ?? collect();
@endphp

<div class="space-y-8">
    @if (session('status'))
        <p class="text-sm text-brand">{{ session('status') }}</p>
    @endif

    <div class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-4" x-data="{
        purpose: @js(old('purpose', 'hire')),
        mode: @js(old('checked_out_to_user_id') ? 'user' : 'guest'),
        createRepair: @js((bool) old('create_repair'))
    }">
        <div class="flex items-center justify-between gap-2">
            <h3 class="font-semibold">Checkout</h3>
        </div>

        @if($openCheckout)
            <div class="text-sm space-y-2">
                <p>
                    <span class="text-brand-charcoal dark:text-brand-silver">Checked out to</span>
                    <span class="font-medium">{{ $openCheckout->assigneeLabel() }}</span>
                </p>
                <p>
                    <span class="text-brand-charcoal dark:text-brand-silver">Purpose</span>
                    {{ $openCheckout->purpose?->label() ?? $openCheckout->purpose }}
                    @if($openCheckout->repair)
                        · <a class="text-brand" href="{{ route('repairs.show', $openCheckout->repair) }}">Repair #{{ $openCheckout->repair->id }}</a>
                    @endif
                </p>
                @if($openCheckout->expected_return_at)
                    <p>
                        <span class="text-brand-charcoal dark:text-brand-silver">Expected return</span>
                        {{ $openCheckout->expected_return_at->format('Y-m-d H:i') }}
                    </p>
                @endif
                @if($openCheckout->notes)
                    <p class="text-brand-charcoal dark:text-brand-silver">{{ $openCheckout->notes }}</p>
                @endif

                @if($canManageCheckouts)
                    <form method="POST" action="{{ route('assets.checkouts.return', [$asset, $openCheckout]) }}" class="flex flex-col sm:flex-row gap-2 items-start sm:items-end pt-2">
                        @csrf
                        <div class="flex-1 w-full">
                            <x-input-label for="return_notes" value="Return notes" />
                            <x-text-input id="return_notes" name="notes" class="mt-1 block w-full" :value="old('notes')" />
                        </div>
                        <x-primary-button>Return</x-primary-button>
                    </form>
                    @error('checkout')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        @elseif($canManageCheckouts)
            <form method="POST" action="{{ route('assets.checkouts.store', $asset) }}" class="space-y-3 text-sm">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="purpose" value="Purpose" />
                        <select id="purpose" name="purpose" x-model="purpose" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                            @foreach($checkoutPurposes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('purpose')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="expected_return_at" value="Expected return" />
                        <x-text-input id="expected_return_at" name="expected_return_at" type="datetime-local" class="mt-1 block w-full" :value="old('expected_return_at')" />
                    </div>
                </div>

                <div class="flex gap-4">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="assignee_mode" value="user" x-model="mode">
                        User
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="assignee_mode" value="guest" x-model="mode">
                        Guest
                    </label>
                </div>

                <div x-show="mode === 'user'" x-cloak>
                    <x-input-label for="checked_out_to_user_id" value="Checked out to (user)" />
                    <select id="checked_out_to_user_id" name="checked_out_to_user_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" x-bind:disabled="mode !== 'user'">
                        <option value="">Select user…</option>
                        @foreach($checkoutAssigneeOptions as $option)
                            <option value="{{ $option->id }}" @selected((string) old('checked_out_to_user_id') === (string) $option->id)>
                                {{ $option->name }} ({{ $option->email }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('checked_out_to_user_id')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" x-show="mode === 'guest'" x-cloak>
                    <div>
                        <x-input-label for="checked_out_to_name" value="Guest name" />
                        <x-text-input id="checked_out_to_name" name="checked_out_to_name" class="mt-1 block w-full" :value="old('checked_out_to_name')" x-bind:disabled="mode !== 'guest'" />
                        <x-input-error :messages="$errors->get('checked_out_to_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="checked_out_to_email" value="Guest email" />
                        <x-text-input id="checked_out_to_email" name="checked_out_to_email" type="email" class="mt-1 block w-full" :value="old('checked_out_to_email')" x-bind:disabled="mode !== 'guest'" />
                        <x-input-error :messages="$errors->get('checked_out_to_email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="checked_out_to_company" value="Company" />
                        <x-text-input id="checked_out_to_company" name="checked_out_to_company" class="mt-1 block w-full" :value="old('checked_out_to_company')" x-bind:disabled="mode !== 'guest'" />
                    </div>
                </div>

                <div x-show="purpose === 'repair'" x-cloak class="space-y-3 border-t border-brand-silver dark:border-brand-charcoal pt-3">
                    <div>
                        <x-input-label for="repair_id" value="Link existing repair" />
                        <select id="repair_id" name="repair_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" :disabled="createRepair">
                            <option value="">Select open repair…</option>
                            @foreach($checkoutOpenRepairs as $repair)
                                <option value="{{ $repair->id }}" @selected((string) old('repair_id') === (string) $repair->id)>
                                    #{{ $repair->id }} — {{ $repair->summary }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('repair_id')" class="mt-1" />
                    </div>
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="create_repair" value="0">
                        <input type="checkbox" name="create_repair" value="1" x-model="createRepair">
                        Create new repair with this checkout
                    </label>
                    <div x-show="createRepair" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="repair_summary" value="Repair summary" />
                            <x-text-input id="repair_summary" name="repair_summary" class="mt-1 block w-full" :value="old('repair_summary')" />
                        </div>
                        <div>
                            <x-input-label for="repair_notes" value="Repair notes" />
                            <x-text-input id="repair_notes" name="repair_notes" class="mt-1 block w-full" :value="old('repair_notes')" />
                        </div>
                    </div>
                </div>

                <div>
                    <x-input-label for="checkout_notes" value="Notes" />
                    <textarea id="checkout_notes" name="notes" rows="2" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Check out</x-primary-button>
                </div>
            </form>
        @else
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No open checkout.</p>
        @endif
    </div>

    <section class="space-y-3">
        <h3 class="font-semibold text-lg">Checkout history</h3>
        <div class="overflow-x-auto bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal">
            <table class="min-w-full text-sm">
                <thead class="bg-brand-silver/40 dark:bg-brand-charcoal/40 text-left">
                    <tr>
                        <th class="px-3 py-2 font-medium">Checked out</th>
                        <th class="px-3 py-2 font-medium">Returned</th>
                        <th class="px-3 py-2 font-medium">Assignee</th>
                        <th class="px-3 py-2 font-medium">Purpose</th>
                        <th class="px-3 py-2 font-medium">By</th>
                        <th class="px-3 py-2 font-medium">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-silver dark:divide-brand-charcoal">
                    @forelse($checkoutHistory ?? [] as $row)
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $row->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if($row->returned_at)
                                    {{ $row->returned_at->format('Y-m-d H:i') }}
                                    @if($row->returnedBy)
                                        <div class="text-xs text-brand-charcoal dark:text-brand-silver">{{ $row->returnedBy->name }}</div>
                                    @endif
                                @else
                                    <span class="text-brand">Open</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $row->assigneeLabel() }}</td>
                            <td class="px-3 py-2">
                                {{ $row->purpose?->label() ?? $row->purpose }}
                                @if($row->repair)
                                    <div>
                                        <a class="text-brand" href="{{ route('repairs.show', $row->repair) }}">Repair #{{ $row->repair->id }}</a>
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $row->checkedOutBy?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-brand-charcoal dark:text-brand-silver">{{ $row->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-brand-charcoal dark:text-brand-silver">
                                No checkout history yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($checkoutHistory)
            <div>{{ $checkoutHistory->links() }}</div>
        @endif
    </section>
</div>

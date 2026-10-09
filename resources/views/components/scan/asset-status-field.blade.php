@props([
    'selected' => null,
    'id' => 'asset_status',
])

@php
    $initialStatus = old('asset_status', $selected);
    $initialStatus = $initialStatus instanceof \App\Enums\AssetStatus
        ? $initialStatus->value
        : ($initialStatus === null || $initialStatus === '' ? '' : (string) $initialStatus);
@endphp

<div>
    <x-input-label :for="$id" value="Status for next items" />
    <select
        id="{{ $id }}"
        name="asset_status"
        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
    >
        <option value="">— No change</option>
        @foreach(\App\Enums\AssetStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected($initialStatus === $status->value)>
                {{ $status->label() }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
        Applied to each asset when scanned (e.g. On Event, Available).
    </p>
    <x-input-error :messages="$errors->get('asset_status')" class="mt-1" />
</div>

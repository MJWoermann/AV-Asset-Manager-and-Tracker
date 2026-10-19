<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Stock update — {{ $asset->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-brand-silver/40 text-black">
    <div
        class="min-h-screen max-w-lg mx-auto px-4 py-6 space-y-4"
        x-data="{ action: @js(old('type', $action ?? 'count')) }"
    >
        <header class="space-y-1">
            <p class="text-xs uppercase tracking-wide text-brand-charcoal">Kanban stock update</p>
            <h1 class="text-2xl font-semibold">{{ $asset->name }}</h1>
            @if(! empty($sku))
                <p class="text-sm text-brand-charcoal">SKU {{ $sku }}</p>
            @endif
            <p class="text-lg">On hand: <span class="font-semibold text-brand">{{ $asset->quantity ?? 0 }}</span></p>
        </header>

        @if (session('status'))
            <p class="text-sm text-brand border border-brand px-3 py-2 bg-white">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('stock.guest.store', ['token' => $token]) }}" class="bg-white border border-brand-charcoal/20 p-4 space-y-4">
            @csrf

            <div class="grid grid-cols-3 gap-2">
                @foreach(['count' => 'Count', 'restock' => 'Restock', 'breakage' => 'Breakage'] as $value => $label)
                    <label class="text-center text-sm border px-2 py-3 cursor-pointer"
                           :class="action === '{{ $value }}' ? 'border-brand text-brand bg-brand/5' : 'border-brand-charcoal/20'">
                        <input type="radio" name="type" value="{{ $value }}" class="sr-only" x-model="action" @checked(old('type', $action) === $value)>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('type')" />

            <div>
                <x-input-label for="quantity" value="Quantity" />
                <x-text-input id="quantity" name="quantity" type="number" min="0" class="mt-1 block w-full text-lg" :value="old('quantity')" required />
                <p class="text-xs text-brand-charcoal mt-1" x-text="action === 'count' ? 'Set absolute on-hand quantity' : (action === 'restock' ? 'Units added' : 'Units broken / removed')"></p>
                <x-input-error :messages="$errors->get('quantity')" class="mt-1" />
            </div>

            <div x-show="action === 'restock'" x-cloak class="space-y-3">
                <div>
                    <x-input-label for="external_po_number" value="PO number (optional)" />
                    <x-text-input id="external_po_number" name="external_po_number" class="mt-1 block w-full" :value="old('external_po_number')" />
                </div>
                <div>
                    <x-input-label for="external_po_url" value="PO URL (optional)" />
                    <x-text-input id="external_po_url" name="external_po_url" type="url" class="mt-1 block w-full" :value="old('external_po_url')" />
                </div>
                <p class="text-xs text-brand-charcoal">PDF attachments can be added when signed in.</p>
            </div>

            <div>
                <x-input-label for="notes" value="Notes" />
                <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded border-gray-300" :required="action === 'breakage'">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-1" />
            </div>

            <x-primary-button class="w-full justify-center">Update stock</x-primary-button>
        </form>
    </div>
</body>
</html>

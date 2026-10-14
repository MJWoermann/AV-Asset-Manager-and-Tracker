<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kanban tags — {{ $asset->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .tag {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-white text-black">
    <div class="max-w-4xl mx-auto px-4 py-6 space-y-4">
        <div class="no-print flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">Kanban tags — {{ $asset->name }}</h1>
                <p class="text-sm text-brand-charcoal">Print this page for shelf tags. QR codes open the guest stock update URL.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="px-3 py-2 bg-brand text-white text-sm">Print</button>
                <a href="{{ route('assets.show', [$asset, 'tab' => 'stock']) }}" class="px-3 py-2 border text-sm">Back</a>
            </div>
        </div>

        @php
            $printable = collect($tags ?? [])->filter(fn ($row) => ! empty($row['guest_url']));
        @endphp

        @if($printable->isEmpty())
            <p class="text-sm text-brand-charcoal">
                No fresh guest URLs available. Use Create / rotate on the Stock tab, then print again so QR codes can be generated.
            </p>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($printable as $row)
                <div
                    class="tag border-2 border-black p-4 flex flex-col items-center gap-3 text-center"
                    x-data="kanbanQr(@js($row['guest_url']))"
                >
                    <p class="text-lg font-semibold leading-tight">{{ $asset->name }}</p>
                    @if(! empty($row['sku'] ?? $sku))
                        <p class="text-sm">SKU {{ $row['sku'] ?? $sku }}</p>
                    @endif
                    <canvas x-ref="qr" width="160" height="160" class="border border-black"></canvas>
                    <p class="text-xs uppercase tracking-wide">{{ $row['token']->label }}</p>
                    @if(! empty($row['supplier'] ?? $supplier))
                        <p class="text-xs text-brand-charcoal">{{ $row['supplier'] ?? $supplier }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>

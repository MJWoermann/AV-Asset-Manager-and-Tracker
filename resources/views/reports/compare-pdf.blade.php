<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Comparison report — {{ $source->name }} vs {{ $target->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #444; margin-bottom: 18px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #00adb7; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background: #f3f3f3; font-weight: bold; }
        .child { padding-left: 18px; }
        .empty { color: #666; font-style: italic; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Comparison report</h1>
    <p class="meta">
        Scanned / event: <strong>{{ $source->name }}</strong><br>
        Inventory / source: <strong>{{ $target->name }}</strong><br>
        Generated: {{ now()->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
    </p>

    @foreach([
        'matched' => 'Matched',
        'inventory_only' => 'Inventory only',
        'scanned_only' => 'Scanned only (not on inventory)',
    ] as $key => $title)
        <h2>{{ $title }} ({{ $result[$key]->count() }})</h2>
        @if($result[$key]->isEmpty())
            <p class="empty">None</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>FMI AST#</th>
                        <th>TP Barcode</th>
                        @if($key === 'matched')
                            <th>Inv qty</th>
                            <th>Scan qty</th>
                        @else
                            <th>Qty</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($result[$key] as $row)
                        <tr>
                            <td class="{{ ! empty($row->parent_id) ? 'child' : '' }}">{{ $row->name }}</td>
                            <td class="mono">{{ $row->fmi_ast ?? '' }}</td>
                            <td class="mono">{{ $row->tp_barcode ?? '' }}</td>
                            @if($key === 'matched')
                                <td>{{ $row->inventory_qty ?? '' }}</td>
                                <td>{{ $row->scanned_qty ?? '' }}</td>
                            @else
                                <td>{{ $row->quantity ?? '' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>

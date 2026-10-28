<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout holdings</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 4px; }
        .meta { color: #444; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background: #f3f3f3; font-weight: bold; }
        .empty { color: #666; font-style: italic; }
        .detail { color: #444; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Checkout holdings</h1>
    <p class="meta">
        {{ $filters['view'] === 'holders' ? 'By holder' : 'By model' }}<br>
        Generated: {{ now()->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
    </p>

    @if ($filters['view'] === 'models')
        @if ($modelRows === [])
            <p class="empty">No assets match these filters.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Manufacturer</th>
                        <th>Model</th>
                        <th>Available</th>
                        <th>Checked out</th>
                        <th>In register</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modelRows as $row)
                        <tr>
                            <td>{{ $row['manufacturer'] }}</td>
                            <td>{{ $row['model'] }}</td>
                            <td>{{ $row['available'] }}</td>
                            <td>{{ $row['checked_out'] }}</td>
                            <td>{{ $row['in_register'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        @forelse ($holderGroups as $group)
            <h2>
                {{ $group['label'] }}
                <span class="detail">
                    @if ($group['detail'] !== '')
                        — {{ $group['detail'] }}
                    @endif
                    ({{ $group['checkouts']->count() }})
                </span>
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Identifiers</th>
                        <th>Model</th>
                        <th>Purpose</th>
                        <th>Checked out</th>
                        <th>Expected return</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['checkouts'] as $checkout)
                        @php
                            $asset = $checkout->asset;
                            $identifiers = collect([
                                $asset?->serial_number,
                                $asset?->tp_barcode,
                                $asset?->fmi_ast,
                                $asset?->rig_tag,
                            ])->filter()->implode(' · ');
                        @endphp
                        <tr>
                            <td>{{ $asset?->name ?? '—' }}</td>
                            <td>{{ $identifiers !== '' ? $identifiers : '—' }}</td>
                            <td>{{ trim(($asset?->manufacturer?->name ?? '').' '.($asset?->manufacturerModel?->name ?? '')) ?: '—' }}</td>
                            <td>{{ $checkout->purposeLabel() }}</td>
                            <td>{{ $checkout->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ $checkout->expected_return_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ $asset?->location?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p class="empty">No checkouts match these filters.</p>
        @endforelse
    @endif
</body>
</html>

@php
    /** @var array<string, mixed> $element */
    $type = $element['type'] ?? 'text';
    $props = is_array($element['props'] ?? null) ? $element['props'] : [];
    $value = (string) ($element['value'] ?? '');
    $style = sprintf(
        'left:%smm;top:%smm;width:%smm;height:%smm;z-index:%d;transform:rotate(%sdeg);',
        $element['x'] ?? 0,
        $element['y'] ?? 0,
        $element['width'] ?? 10,
        $element['height'] ?? 10,
        (int) ($element['z'] ?? 0),
        $element['rotation'] ?? 0,
    );
    $fontSize = (float) ($props['fontSize'] ?? 10);
    $align = (string) ($props['align'] ?? 'left');
    $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
    $weight = ($props['fontWeight'] ?? 'normal') === 'bold' ? 'bold' : 'normal';
    $color = (string) ($props['color'] ?? '#000000');
    $color = in_array($color, ['#000000', '#444444', '#888888'], true) ? $color : '#000000';
@endphp

<div class="absolute overflow-hidden" style="{{ $style }}">
    @if(in_array($type, ['text', 'field'], true))
        <div style="font-size: {{ $fontSize }}pt; font-weight: {{ $weight }}; text-align: {{ $align }}; color: {{ $color }}; line-height: 1.1; width: 100%; height: 100%;">
            {{ $value }}
        </div>
    @elseif($type === 'barcode')
        @if(!empty($forPdf) && !empty($element['image_data_uri']))
            <img src="{{ $element['image_data_uri'] }}" alt="" style="width:100%;height:100%;object-fit:contain;">
        @else
            <svg
                class="w-full h-full shelf-tag-barcode"
                data-value="{{ $value }}"
                data-symbology="{{ $props['symbology'] ?? 'code128' }}"
                data-show-text="{{ !empty($props['showText']) ? '1' : '0' }}"
            ></svg>
        @endif
    @elseif($type === 'qr')
        @if(!empty($forPdf) && !empty($element['image_data_uri']))
            <img src="{{ $element['image_data_uri'] }}" alt="" style="width:100%;height:100%;object-fit:contain;">
        @else
            <canvas
                class="w-full h-full shelf-tag-qr"
                data-value="{{ $value }}"
            ></canvas>
        @endif
    @elseif($type === 'logo')
        @php
            $src = !empty($forPdf) ? ($element['image_data_uri'] ?? null) : ($logoUrl ?? null);
        @endphp
        @if($src)
            <img src="{{ $src }}" alt="" style="width:100%;height:100%;object-fit:contain;">
        @endif
    @elseif($type === 'rect')
        <div style="width:100%;height:100%;border:1px solid {{ $props['stroke'] ?? '#000000' }};background:{{ $props['fill'] ?? 'transparent' }};"></div>
    @elseif($type === 'line')
        <div style="width:100%;border-top:2px solid {{ $props['stroke'] ?? '#000000' }};margin-top:{{ max(0, (($element['height'] ?? 1) / 2) - 0.5) }}mm;"></div>
    @endif
</div>

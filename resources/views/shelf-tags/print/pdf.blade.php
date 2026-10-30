<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Labels</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; color: #000; }
        .label {
            position: relative;
            width: {{ $layout->displayWidthMm() }}mm;
            height: {{ $layout->displayHeightMm() }}mm;
            page-break-after: always;
            overflow: hidden;
        }
        .label:last-child { page-break-after: auto; }
        .el { position: absolute; overflow: hidden; }
        .el img { width: 100%; object-fit: contain; }
    </style>
</head>
<body>
@foreach($labels as $label)
    <div class="label">
        @foreach($label['elements'] as $element)
            @include('shelf-tags.print.partials.element', ['element' => $element, 'logoUrl' => null, 'forPdf' => true])
        @endforeach
    </div>
@endforeach
</body>
</html>

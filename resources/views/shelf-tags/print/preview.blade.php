@php
    $previewLabels = array_slice($labels, 0, $previewLimit);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">Shelf tag preview</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    {{ $count }} label{{ $count === 1 ? '' : 's' }}
                    · {{ $layout->name }}
                    · {{ $list->name }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                <button type="button" onclick="window.print()" class="px-3 py-2 bg-brand text-white text-sm">Print</button>
                <a href="{{ route('shelf-tags.print.pdf') }}" class="px-3 py-2 border text-sm">Download PDF</a>
                <a href="{{ route('shelf-tags.index', ['asset_list_id' => $list->id, 'shelf_tag_layout_id' => $layout->id]) }}" class="px-3 py-2 border text-sm">Back</a>
            </div>
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4 print:max-w-none print:px-0"
        x-data="shelfTagPrintPreview(@js($labels), @js((float) $layout->width_mm), @js((float) $layout->height_mm), @js($layout->logoUrl()))"
        x-init="renderAll()"
    >
        <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver print:hidden">
            Showing first {{ count($previewLabels) }} of {{ $count }} on screen. Print/PDF includes the full batch.
            Select the matching Brother DK size, Actual size, Cut every for continuous.
        </p>

        <div class="space-y-6 print:space-y-0">
            @foreach($labels as $index => $label)
                <div
                    class="label-sheet mx-auto border border-brand-silver bg-white shadow-sm print:border-0 print:shadow-none {{ $index >= $previewLimit ? 'hidden print:block' : '' }}"
                    style="width: {{ $label['width_mm'] }}mm; height: {{ $label['height_mm'] }}mm;"
                    data-label-index="{{ $index }}"
                >
                    <div class="relative w-full h-full overflow-hidden">
                        @foreach($label['elements'] as $element)
                            @include('shelf-tags.print.partials.element', ['element' => $element, 'logoUrl' => $layout->logoUrl(), 'forPdf' => false])
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            .label-sheet, .label-sheet * { visibility: visible; }
            .label-sheet {
                position: relative;
                page-break-after: always;
                break-after: page;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .label-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }
            @page {
                size: {{ $layout->width_mm }}mm {{ $layout->height_mm }}mm;
                margin: 0;
            }
            nav, header, .print\\:hidden { display: none !important; }
        }
    </style>
</x-app-layout>

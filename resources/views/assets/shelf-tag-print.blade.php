<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">Print label</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $asset->name }}</p>
            </div>
            <div class="flex flex-wrap justify-end gap-2 print:hidden">
                <a href="{{ route('assets.show', $asset) }}" class="px-3 py-2 border text-sm">Back to asset</a>
                @if($layout)
                    <a href="{{ route('assets.label.pdf', [$asset, 'layout' => $layout->id]) }}" class="px-3 py-2 border text-sm">Download PDF</a>
                    <button type="button" onclick="window.print()" class="px-3 py-2 bg-brand text-white text-sm">Print</button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 print:max-w-none print:px-0 space-y-6">
        <div class="print:hidden bg-white dark:bg-brand-slate border p-4 max-w-md">
            @if($layouts->isEmpty())
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Create a <a href="{{ route('labels.layouts.create') }}" data-history-roundtrip class="text-brand underline">label layout</a> before printing.
                </p>
            @else
                <form method="GET" action="{{ route('assets.label', $asset) }}">
                    <x-input-label for="layout" value="Layout" />
                    <x-searchable-select
                        name="layout"
                        id="layout"
                        :options="$layouts->map(fn ($option) => [
                            'value' => $option->id,
                            'label' => $option->name.' ('.$option->width_mm.'×'.$option->height_mm.' mm)',
                        ])->values()"
                        :selected="$layout?->id"
                        auto-submit
                        aria-label="Select label layout"
                    />
                </form>
            @endif
        </div>

        @if($layout && count($labels) > 0)
            <div
                class="print:max-w-none"
                x-data="shelfTagPrintPreview(@js($labels), @js($layout->displayWidthMm()), @js($layout->displayHeightMm()), @js($layout->logoUrl()))"
                x-init="renderAll()"
            >
                <p class="mb-4 text-sm text-brand-charcoal dark:text-brand-silver print:hidden">
                    Select the matching Brother DK size, Actual size, Cut every for continuous.
                    @if($layout->isLandscape())
                        Landscape · {{ $layout->displayWidthMm() }}×{{ $layout->displayHeightMm() }} mm.
                    @endif
                </p>

                <div class="label-print-root space-y-6 print:space-y-0">
                    @foreach($labels as $index => $label)
                        <div
                            class="label-sheet mx-auto border border-brand-silver bg-white shadow-sm print:border-0 print:shadow-none"
                            style="width: {{ $label['width_mm'] }}mm; height: {{ $label['height_mm'] }}mm;"
                            data-label-index="{{ $index }}"
                            data-orientation="{{ $label['orientation'] ?? $layout->orientation }}"
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
        @elseif($layouts->isNotEmpty())
            <p class="text-sm text-brand-charcoal dark:text-brand-silver print:hidden">Choose a layout to preview this asset’s label.</p>
        @endif
    </div>

    @if($layout)
        <style>
            @media print {
                @page {
                    size: {{ $layout->displayWidthMm() }}mm {{ $layout->displayHeightMm() }}mm;
                    margin: 0;
                }
                html, body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #fff !important;
                }
                body > div {
                    min-height: 0 !important;
                    background: #fff !important;
                }
                nav,
                header,
                [data-flash-banner],
                .print\\:hidden,
                .fixed {
                    display: none !important;
                }
                main,
                .label-print-root {
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .label-sheet {
                    position: relative;
                    break-inside: avoid;
                    page-break-inside: avoid;
                    break-after: page;
                    page-break-after: always;
                    margin: 0 !important;
                    box-shadow: none !important;
                }
                .label-sheet:last-child {
                    break-after: auto;
                    page-break-after: auto;
                }
            }
        </style>
        @include('shelf-tags.print.partials.debug-print-log')
    @endif
</x-app-layout>

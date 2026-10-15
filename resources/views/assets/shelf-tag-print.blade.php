<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl">Print shelf tag</h2>
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">{{ $asset->name }}</p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                @if($layout)
                    <button type="button" onclick="window.print()" class="px-3 py-2 bg-brand text-white text-sm">Print</button>
                    <a href="{{ route('assets.shelf-tag.pdf', [$asset, 'layout' => $layout->id]) }}" class="px-3 py-2 border text-sm">Download PDF</a>
                @endif
                <a href="{{ route('assets.show', $asset) }}" class="px-3 py-2 border text-sm">Back to asset</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 print:max-w-none print:px-0 space-y-6">
        <div class="print:hidden bg-white dark:bg-brand-slate border p-4 max-w-md">
            @if($layouts->isEmpty())
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    Create a <a href="{{ route('shelf-tags.layouts.create') }}" class="text-brand underline">shelf tag layout</a> before printing.
                </p>
            @else
                <form method="GET" action="{{ route('assets.shelf-tag', $asset) }}">
                    <x-input-label for="layout" value="Layout" />
                    <select
                        id="layout"
                        name="layout"
                        class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                        onchange="this.form.submit()"
                    >
                        @foreach($layouts as $option)
                            <option value="{{ $option->id }}" @selected($layout && $layout->id == $option->id)>
                                {{ $option->name }} ({{ $option->width_mm }}×{{ $option->height_mm }} mm)
                            </option>
                        @endforeach
                    </select>
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

                <div class="space-y-6 print:space-y-0">
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
            <p class="text-sm text-brand-charcoal dark:text-brand-silver print:hidden">Choose a layout to preview this asset’s shelf tag.</p>
        @endif
    </div>

    @if($layout)
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
                    size: {{ $layout->displayWidthMm() }}mm {{ $layout->displayHeightMm() }}mm;
                    margin: 0;
                }
                nav, header, .print\\:hidden { display: none !important; }
            }
        </style>
    @endif
</x-app-layout>

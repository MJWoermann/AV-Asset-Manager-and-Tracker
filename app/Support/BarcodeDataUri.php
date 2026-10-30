<?php

namespace App\Support;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeDataUri
{
    public static function png(string $value, string $symbology = 'code128'): ?string
    {
        try {
            $generator = new BarcodeGeneratorPNG;
            $type = self::type($generator, $symbology);
            // High-res barcode PNG; print views use object-fit:contain so it fills without cropping.
            $png = $generator->getBarcode($value, $type, 3, 120);

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Barcode bars for a PDF label. The human-readable line is HTML in the label view, because
     * stretching this image to the element box clips a caption drawn inside the graphic.
     */
    public static function forLabel(string $value, string $symbology = 'code128'): ?string
    {
        return self::png($value, $symbology) ?? self::svg($value, $symbology);
    }

    public static function svg(string $value, string $symbology = 'code128'): ?string
    {
        try {
            $generator = new BarcodeGeneratorSVG;
            $svg = $generator->getBarcode($value, self::type($generator, $symbology), 2, 90, 'black');

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function type(BarcodeGeneratorPNG|BarcodeGeneratorSVG $generator, string $symbology): string
    {
        return match (strtolower($symbology)) {
            'code39' => $generator::TYPE_CODE_39,
            default => $generator::TYPE_CODE_128,
        };
    }
}

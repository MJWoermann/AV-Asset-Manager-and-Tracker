<?php

namespace App\Support;

use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeDataUri
{
    public static function png(string $value, string $symbology = 'code128'): ?string
    {
        try {
            $generator = new BarcodeGeneratorPNG;
            $type = match (strtolower($symbology)) {
                'code39' => $generator::TYPE_CODE_39,
                default => $generator::TYPE_CODE_128,
            };
            // High-res barcode PNG; print views use object-fit:contain so it fills without cropping.
            $png = $generator->getBarcode($value, $type, 3, 120);

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable) {
            return null;
        }
    }
}

<?php

namespace Tests\Unit;

use App\Support\BarcodeDataUri;
use Tests\TestCase;

class BarcodeDataUriTest extends TestCase
{
    public function test_label_barcode_is_an_image_of_bars(): void
    {
        $uri = BarcodeDataUri::forLabel('AV3555-006187', 'code128');

        $this->assertNotNull($uri);
        $this->assertMatchesRegularExpression('#^data:image/(png|svg\+xml);base64,#', (string) $uri);
    }

    public function test_svg_barcode_does_not_embed_a_caption(): void
    {
        $uri = BarcodeDataUri::svg('AV3555-006187', 'code128');

        $this->assertNotNull($uri);

        $svg = base64_decode(substr((string) $uri, strlen('data:image/svg+xml;base64,')), true);

        $this->assertIsString($svg);
        $this->assertStringNotContainsString('<text', $svg);
    }
}

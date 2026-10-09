<?php

$dir = __DIR__.'/../public/icons';
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

/**
 * Draw a barcode-scanner glyph on a brand turquoise square.
 */
function makeIcon(string $path, int $size): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    $bg = imagecolorallocate($img, 0x00, 0xad, 0xb7);
    $white = imagecolorallocate($img, 255, 255, 255);
    $scan = imagecolorallocate($img, 0x00, 0x55, 0x56);

    imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $bg);

    $scale = $size / 32;
    $thickness = max(1, (int) round(2 * $scale));
    $barTop = (int) round(12 * $scale);
    $barHeight = (int) round(8 * $scale);
    $radius = (int) round(7 * $scale);

    // Soft rounded look via corner fill (approximate).
    imagefilledellipse($img, $radius, $radius, $radius * 2, $radius * 2, $bg);
    imagefilledellipse($img, $size - $radius - 1, $radius, $radius * 2, $radius * 2, $bg);
    imagefilledellipse($img, $radius, $size - $radius - 1, $radius * 2, $radius * 2, $bg);
    imagefilledellipse($img, $size - $radius - 1, $size - $radius - 1, $radius * 2, $radius * 2, $bg);

    // Scan frame corners.
    $corners = [
        [(int) round(8 * $scale), (int) round(9 * $scale), (int) round(11.5 * $scale), (int) round(9 * $scale)],
        [(int) round(8 * $scale), (int) round(9 * $scale), (int) round(8 * $scale), (int) round(11.5 * $scale)],
        [(int) round(20.5 * $scale), (int) round(9 * $scale), (int) round(24 * $scale), (int) round(9 * $scale)],
        [(int) round(24 * $scale), (int) round(9 * $scale), (int) round(24 * $scale), (int) round(11.5 * $scale)],
        [(int) round(8 * $scale), (int) round(23 * $scale), (int) round(11.5 * $scale), (int) round(23 * $scale)],
        [(int) round(8 * $scale), (int) round(20.5 * $scale), (int) round(8 * $scale), (int) round(23 * $scale)],
        [(int) round(20.5 * $scale), (int) round(23 * $scale), (int) round(24 * $scale), (int) round(23 * $scale)],
        [(int) round(24 * $scale), (int) round(20.5 * $scale), (int) round(24 * $scale), (int) round(23 * $scale)],
    ];

    imagesetthickness($img, $thickness);
    foreach ($corners as [$x1, $y1, $x2, $y2]) {
        imageline($img, $x1, $y1, $x2, $y2, $white);
    }

    // Barcode bars (x, width) in 32px design space.
    $bars = [
        [10, 1.4],
        [12.2, 0.8],
        [13.8, 2],
        [16.6, 0.8],
        [18.2, 1.6],
        [20.6, 0.8],
        [22, 1.4],
    ];

    foreach ($bars as [$x, $w]) {
        $rx = (int) round($x * $scale);
        $rw = max(1, (int) round($w * $scale));
        imagefilledrectangle($img, $rx, $barTop, $rx + $rw - 1, $barTop + $barHeight - 1, $white);
    }

    // Scan line.
    $scanX = (int) round(9 * $scale);
    $scanY = (int) round(15.4 * $scale);
    $scanW = (int) round(14 * $scale);
    $scanH = max(1, (int) round(1.2 * $scale));
    imagefilledrectangle($img, $scanX, $scanY, $scanX + $scanW - 1, $scanY + $scanH - 1, $scan);

    imagepng($img, $path);
}

/**
 * Write a simple multi-size ICO containing PNG payloads (Vista+).
 *
 * @param  list<string>  $pngPaths
 */
function writeIco(string $icoPath, array $pngPaths): void
{
    $images = [];

    foreach ($pngPaths as $pngPath) {
        $data = file_get_contents($pngPath);
        if ($data === false) {
            throw new RuntimeException("Unable to read {$pngPath}");
        }

        $info = getimagesizefromstring($data);
        if ($info === false) {
            throw new RuntimeException("Invalid PNG: {$pngPath}");
        }

        $images[] = [
            'width' => $info[0] >= 256 ? 0 : $info[0],
            'height' => $info[1] >= 256 ? 0 : $info[1],
            'data' => $data,
        ];
    }

    $count = count($images);
    $offset = 6 + (16 * $count);
    $ico = pack('vvv', 0, 1, $count);

    foreach ($images as $image) {
        $size = strlen($image['data']);
        $ico .= pack(
            'CCCCvvVV',
            $image['width'],
            $image['height'],
            0,
            0,
            1,
            32,
            $size,
            $offset
        );
        $offset += $size;
    }

    foreach ($images as $image) {
        $ico .= $image['data'];
    }

    file_put_contents($icoPath, $ico);
}

$tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'av-asset-icons';
if (! is_dir($tmpDir)) {
    mkdir($tmpDir, 0777, true);
}

makeIcon($dir.'/icon-192.png', 192);
makeIcon($dir.'/icon-512.png', 512);

$favicon16 = $tmpDir.DIRECTORY_SEPARATOR.'favicon-16.png';
$favicon32 = $tmpDir.DIRECTORY_SEPARATOR.'favicon-32.png';
makeIcon($favicon16, 16);
makeIcon($favicon32, 32);
writeIco(__DIR__.'/../public/favicon.ico', [$favicon16, $favicon32]);

echo "icons ok\n";

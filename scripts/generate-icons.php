<?php

$dir = __DIR__.'/../public/icons';
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

function makeIcon(string $path, int $size, string $hex): void
{
    $img = imagecreatetruecolor($size, $size);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $bg = imagecolorallocate($img, $r, $g, $b);
    imagefill($img, 0, 0, $bg);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagestring($img, 5, (int) ($size / 2 - 10), (int) ($size / 2 - 7), 'AV', $white);
    imagepng($img, $path);
    imagedestroy($img);
}

makeIcon($dir.'/icon-192.png', 192, '00adb7');
makeIcon($dir.'/icon-512.png', 512, '00adb7');
echo "icons ok\n";

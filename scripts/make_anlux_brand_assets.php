<?php

declare(strict_types=1);

/**
 * Regenera logos Anlux desde los PNG generados en assets/.
 * Uso: php scripts/make_anlux_brand_assets.php
 */

$root = dirname(__DIR__);
$imgDir = $root.'/public/legacy/public/img';

// Fuentes: copia local del proyecto + assets de Cursor si existen
$candidatesLogo = [
    $root.'/storage/app/brand_source/anlux-logo-wordmark.png',
    'C:/Users/xblack/.cursor/projects/c-laragon-www-anlux-os/assets/anlux-logo-wordmark.png',
];
$candidatesIcon = [
    $root.'/storage/app/brand_source/anlux-app-icon.png',
    'C:/Users/xblack/.cursor/projects/c-laragon-www-anlux-os/assets/anlux-app-icon.png',
];

$srcLogo = null;
foreach ($candidatesLogo as $p) {
    if (is_file($p)) {
        $srcLogo = $p;
        break;
    }
}
$srcIcon = null;
foreach ($candidatesIcon as $p) {
    if (is_file($p)) {
        $srcIcon = $p;
        break;
    }
}

if ($srcLogo === null || $srcIcon === null) {
    fwrite(STDERR, "Faltan PNG fuente Anlux.\n");
    exit(1);
}

if (! extension_loaded('gd')) {
    fwrite(STDERR, "Extension GD no disponible.\n");
    exit(1);
}

if (! is_dir($imgDir)) {
    mkdir($imgDir, 0755, true);
}

function loadPng(string $path): GdImage
{
    $im = @imagecreatefrompng($path);
    if ($im === false) {
        throw new RuntimeException('No se pudo leer: '.$path);
    }

    return $im;
}

function fitOnCanvas(GdImage $src, int $outW, int $outH, bool $whiteBg): GdImage
{
    $lw = imagesx($src);
    $lh = imagesy($src);
    $canvas = imagecreatetruecolor($outW, $outH);
    if ($whiteBg) {
        $bg = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $outW, $outH, $bg);
        imagealphablending($canvas, true);
    } else {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $outW, $outH, $transparent);
        imagealphablending($canvas, true);
    }
    $pad = (int) max(8, min($outW, $outH) * 0.04);
    $maxW = $outW - $pad * 2;
    $maxH = $outH - $pad * 2;
    $scale = min($maxW / $lw, $maxH / $lh);
    $nw = (int) max(1, round($lw * $scale));
    $nh = (int) max(1, round($lh * $scale));
    $dx = (int) (($outW - $nw) / 2);
    $dy = (int) (($outH - $nh) / 2);
    imagecopyresampled($canvas, $src, $dx, $dy, 0, 0, $nw, $nh, $lw, $lh);

    return $canvas;
}

$logo = loadPng($srcLogo);
$icon = loadPng($srcIcon);

// logo.jpeg for PDFs / login
$logoCanvas = fitOnCanvas($logo, 900, 220, true);
$logoJpeg = $imgDir.'/logo.jpeg';
imagejpeg($logoCanvas, $logoJpeg, 93);
imagedestroy($logoCanvas);
echo "OK {$logoJpeg}\n";

// also keep a png wordmark
$logoPngCanvas = fitOnCanvas($logo, 1200, 360, true);
imagepng($logoPngCanvas, $imgDir.'/logo-anlux.png', 6);
imagedestroy($logoPngCanvas);
echo "OK {$imgDir}/logo-anlux.png\n";

// app icons
foreach ([1024, 512, 180, 32] as $size) {
    $iconCanvas = fitOnCanvas($icon, $size, $size, false);
    $path = $imgDir.'/anlux-icon-'.$size.'.png';
    imagepng($iconCanvas, $path, 6);
    imagedestroy($iconCanvas);
    echo "OK {$path}\n";
}

// canonical favicon source
copy($imgDir.'/anlux-icon-1024.png', $imgDir.'/anlux-icon-1024.png');

// public favicon.png
$public = $root.'/public';
copy($imgDir.'/anlux-icon-32.png', $public.'/favicon.png');

// favicon.ico (PNG packed as ICO)
$png = (string) file_get_contents($imgDir.'/anlux-icon-32.png');
$ico = pack('vvv', 0, 1, 1);
$ico .= pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png), 22);
$ico .= $png;
file_put_contents($public.'/favicon.ico', $ico);
echo "OK {$public}/favicon.ico\n";

imagedestroy($logo);
imagedestroy($icon);

echo "Brand assets Anlux regenerados.\n";

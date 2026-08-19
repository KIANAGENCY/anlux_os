<?php

declare(strict_types=1);

/**
 * Genera og-exacto.jpg (1200x630) con el logo centrado para Meta / Open Graph.
 * Ejecutar: php scripts/make_og_image.php
 */

$imgDir = dirname(__DIR__).'/public/legacy/public/img';
$logoPath = $imgDir.'/logo.jpeg';
$outPath = $imgDir.'/og-exacto.jpg';

if (! is_file($logoPath)) {
    fwrite(STDERR, "No se encontro: {$logoPath}\n");
    exit(1);
}

if (! extension_loaded('gd')) {
    fwrite(STDERR, "Extension GD no disponible.\n");
    exit(1);
}

$logo = @imagecreatefromjpeg($logoPath);
if ($logo === false) {
    $logo = @imagecreatefrompng($logoPath);
}
if ($logo === false) {
    fwrite(STDERR, "No se pudo leer el logo.\n");
    exit(1);
}

$canvasW = 1200;
$canvasH = 630;
$padding = 80;

$logoW = imagesx($logo);
$logoH = imagesy($logo);
$maxW = $canvasW - ($padding * 2);
$maxH = $canvasH - ($padding * 2);
$scale = min($maxW / $logoW, $maxH / $logoH, 1.0);
$newW = (int) max(1, round($logoW * $scale));
$newH = (int) max(1, round($logoH * $scale));
$dstX = (int) (($canvasW - $newW) / 2);
$dstY = (int) (($canvasH - $newH) / 2);

$canvas = imagecreatetruecolor($canvasW, $canvasH);
$white = imagecolorallocate($canvas, 255, 255, 255);
imagefilledrectangle($canvas, 0, 0, $canvasW, $canvasH, $white);
imagecopyresampled($canvas, $logo, $dstX, $dstY, 0, 0, $newW, $newH, $logoW, $logoH);
imagejpeg($canvas, $outPath, 92);

imagedestroy($logo);
imagedestroy($canvas);

echo "Generado: {$outPath} ({$canvasW}x{$canvasH})\n";

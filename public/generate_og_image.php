<?php

declare(strict_types=1);

/**
 * Genera og-anlux.jpg (1200x630) en el servidor. Ejecutar una vez:
 * https://soporte.anlux.mx/generate_og_image.php?key=anlux99
 * Borrar este archivo despues.
 */

const OG_GEN_KEY = 'anlux99';

if (($_GET['key'] ?? '') !== OG_GEN_KEY) {
    http_response_code(403);
    exit('Forbidden — usa ?key='.OG_GEN_KEY);
}

header('Content-Type: text/plain; charset=utf-8');

$imgDir = __DIR__.'/legacy/public/img';
$logoPath = $imgDir.'/logo.jpeg';
$outPath = $imgDir.'/og-anlux.jpg';

if (! is_file($logoPath)) {
    exit("[FALLO] No existe: {$logoPath}\nSube logo.jpeg primero.\n");
}

if (! extension_loaded('gd')) {
    exit("[FALLO] PHP GD no esta activo en el servidor.\n");
}

$logo = @imagecreatefromjpeg($logoPath);
if ($logo === false) {
    exit("[FALLO] No se pudo leer logo.jpeg\n");
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

$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    .'://'.($_SERVER['HTTP_HOST'] ?? 'localhost');

echo "[OK] Imagen Open Graph generada\n";
echo "Archivo: {$outPath}\n";
echo "Tamano: 1200 x 630 px\n";
echo "URL publica: {$base}/legacy/public/img/og-anlux.jpg\n";
echo "\nAhora en Meta Depurador:\n";
echo "1. https://soporte.anlux.mx/aviso-de-privacidad\n";
echo "2. Clic en Volver a extraer\n";
echo "\nBorra generate_og_image.php cuando termines.\n";

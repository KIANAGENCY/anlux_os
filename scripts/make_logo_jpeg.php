<?php

declare(strict_types=1);

$dir = __DIR__.'/../public/legacy/public/img';
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$path = $dir.'/logo.jpeg';
$w = 900;
$h = 220;
$im = imagecreatetruecolor($w, $h);
$white = imagecolorallocate($im, 255, 255, 255);
$blue = imagecolorallocate($im, 37, 99, 235);
imagefilledrectangle($im, 0, 0, $w, $h, $white);
$font = 'C:\\Windows\\Fonts\\arialbd.ttf';
$text = 'ANLUX';
if (is_file($font) && function_exists('imagettfbbox')) {
    $size = 86;
    $bbox = imagettfbbox($size, 0, $font, $text);
    $textW = (int) abs(($bbox[2] ?? 0) - ($bbox[0] ?? 0));
    $textH = (int) abs(($bbox[7] ?? 0) - ($bbox[1] ?? 0));
    $x = (int) (($w - $textW) / 2);
    $y = (int) (($h + $textH) / 2);
    imagettftext($im, $size, 0, $x, $y, $blue, $font, $text);
} else {
    imagestring($im, 5, 360, 100, $text, $blue);
}
imagejpeg($im, $path, 92);
imagedestroy($im);
echo "wrote $path\n";

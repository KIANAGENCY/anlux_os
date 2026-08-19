<?php

declare(strict_types=1);

$path = __DIR__.'/../resources/views/orders/orden_form.blade.php';
$s = file_get_contents($path);
if ($s === false) {
    fwrite(STDERR, "read fail\n");
    exit(1);
}

$b = preg_replace_callback('/\xC3\x83\xC2([\x80-\xBF])/', function (array $m): string {
    return "\xC3".$m[1];
}, $s);

$b = str_replace("\xC3\x83\xE2\x80\x9C", "\xC3\x93", $b); // Ó
$b = str_replace("\xC3\x83\xE2\x80\xB0", "\xC3\x89", $b); // É
$b = str_replace("\xC3\x83\xE2\x80\x98", "\xC3\x81", $b); // Á

$before = substr_count($s, 'Ã');
$after = substr_count($b, 'Ã');

echo "Ã count: {$before} -> {$after}\n";
echo 'UTF-8 valid: '.(mb_check_encoding($b, 'UTF-8') ? 'yes' : 'no')."\n";

file_put_contents($path, $b);
echo "Wrote {$path}\n";

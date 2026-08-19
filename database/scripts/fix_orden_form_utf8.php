<?php

declare(strict_types=1);

$path = dirname(__DIR__, 2) . '/resources/views/orders/orden_form.blade.php';
$content = file_get_contents($path);
if ($content === false) {
    fwrite(STDERR, "No se pudo leer: {$path}\n");
    exit(1);
}

$before = substr_count($content, "\xC3\x83");

// Si aún hay mojibake UTF-8 (Ã³), revertir una capa
if ($before > 0) {
    $step = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $content);
    if ($step !== false) {
        $content = $step;
    }
}

// Si quedó Latin-1 (ó = 0xF3), volver a UTF-8
if (!mb_check_encoding($content, 'UTF-8')) {
    $utf8 = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $content);
    if ($utf8 !== false) {
        $content = $utf8;
    }
}

$content = str_replace("SECCI\xC3\x4EN", "SECCI\xC3\x93N", $content);

file_put_contents($path, $content);

$after = substr_count($content, "\xC3\x83");
echo "C3 83 restantes: {$after}, UTF-8 válido: " . (mb_check_encoding($content, 'UTF-8') ? 'sí' : 'no') . PHP_EOL;
$i = strpos($content, 'Descripci');
if ($i !== false) {
    echo 'Muestra: ' . substr($content, $i, 22) . PHP_EOL;
}
$i2 = strpos($content, 'SECCI');
if ($i2 !== false) {
    echo 'SECCI: ' . substr($content, $i2, 12) . PHP_EOL;
}

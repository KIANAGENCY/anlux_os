<?php

declare(strict_types=1);

$path = dirname(__DIR__, 2) . '/resources/views/orders/orden_form.blade.php';
$content = file_get_contents($path);
if ($content === false) {
    fwrite(STDERR, "No se pudo leer {$path}\n");
    exit(1);
}

$map = [
    'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
    'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N', 'Ü' => 'U',
    '&aacute;' => 'a', '&eacute;' => 'e', '&iacute;' => 'i', '&oacute;' => 'o', '&uacute;' => 'u', '&ntilde;' => 'n',
    '&Aacute;' => 'A', '&Eacute;' => 'E', '&Iacute;' => 'I', '&Oacute;' => 'O', '&Uacute;' => 'U', '&Ntilde;' => 'N',
    '¿' => '?', '¡' => '!',
];

$content = strtr($content, $map);

file_put_contents($path, $content);
echo "Texto convertido a ASCII sin acentos.\n";

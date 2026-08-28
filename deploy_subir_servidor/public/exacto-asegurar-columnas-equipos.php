<?php

declare(strict_types=1);

/**
 * Crea columnas de entrega por equipo SIN SSH.
 * URL: https://TU-DOMINIO/exacto-asegurar-columnas-equipos.php?key=exacto2026diag
 * BORRAR este archivo cuando termines.
 */

const DIAG_KEY = 'exacto2026diag';

if (($_GET['key'] ?? '') !== DIAG_KEY) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font:14px monospace">';

$root = dirname(__DIR__);
echo "=== Exacto: columnas entrega por equipo ===\n\n";

$configCache = $root.'/bootstrap/cache/config.php';
if (is_file($configCache)) {
    @unlink($configCache);
    echo "[OK] Se eliminó bootstrap/cache/config.php\n\n";
}

if (! is_file($root.'/vendor/autoload.php')) {
    echo "[ERROR] Falta vendor/. Sube vendor o ejecuta composer install.\n";
    exit;
}

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $columnas = [
        'entrega_receptor_tipo',
        'entrega_recibido_cliente',
        'entrega_firma_cliente',
        'entrega_firma_tecnico',
        'entrega_tecnico',
        'entrega_fecha',
    ];

    echo "Antes:\n";
    foreach ($columnas as $col) {
        $ok = Illuminate\Support\Facades\Schema::hasColumn('equipos_orden', $col);
        echo ($ok ? '[OK] ' : '[FALTA] ')."equipos_orden.{$col}\n";
    }

    App\Support\EquiposOrdenEntregaSchema::ensure();

    echo "\nDespués:\n";
    $faltan = 0;
    foreach ($columnas as $col) {
        $ok = Illuminate\Support\Facades\Schema::hasColumn('equipos_orden', $col);
        if (! $ok) {
            $faltan++;
        }
        echo ($ok ? '[OK] ' : '[FALTA] ')."equipos_orden.{$col}\n";
    }

    if ($faltan === 0) {
        echo "\nListo. Las firmas por equipo ya pueden guardarse en equipos_orden.\n";
        echo "Haz una entrega nueva de prueba (cada equipo con su firma).\n";
    } else {
        echo "\n[ERROR] Aún faltan columnas. Revisa permisos MySQL del usuario de la BD.\n";
    }
} catch (Throwable $e) {
    echo "[ERROR] ".$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}

echo "\nBORRA public/exacto-asegurar-columnas-equipos.php cuando termines.\n";
echo '</pre>';

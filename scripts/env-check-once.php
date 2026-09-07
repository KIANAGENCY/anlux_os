<?php

declare(strict_types=1);

/**
 * Diagnóstico .env en el servidor (sin SSH).
 * Copiar a: public/env-check-once.php
 * Abrir: https://soporte.anlux.mx/env-check-once.php?key=TU_SECRETO
 * BORRAR el archivo después.
 */

const ENV_CHECK_SECRET = 'CAMBIAR_por_secreto_largo';

$key = (string) ($_GET['key'] ?? '');
if ($key === '' || ! hash_equals(ENV_CHECK_SECRET, $key)) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$envPath = $root.'/.env';
$configCache = $root.'/bootstrap/cache/config.php';

echo "=== Rutas ===\n";
echo "Raíz Laravel: {$root}\n";
echo "Este script: ".__FILE__."\n";
echo ".env esperado: {$envPath}\n";
echo ".env existe: ".(is_file($envPath) ? 'SÍ' : 'NO')."\n";
echo "config.php cache: ".(is_file($configCache) ? 'SÍ (¡bórralo!)' : 'no')."\n\n";

if (is_file($envPath)) {
    echo "=== Valores en archivo .env (DB_*) ===\n";
    $lines = file($envPath, FILE_IGNORE_NEW_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (preg_match('/^(DB_|APP_ENV|APP_DEBUG|APP_URL)=/i', $line)) {
            if (preg_match('/^DB_PASSWORD=(.*)$/i', $line)) {
                echo "DB_PASSWORD=***\n";
            } else {
                echo $line."\n";
            }
        }
    }
    echo "\n";
}

if (! is_file($root.'/vendor/autoload.php')) {
    echo "ERROR: no hay vendor/ en {$root}\n";
    exit;
}

require $root.'/vendor/autoload.php';

if (is_file($configCache)) {
    @unlink($configCache);
    echo "Se eliminó bootstrap/cache/config.php\n\n";
}

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Lo que Laravel usa ahora ===\n";
echo 'APP_ENV: '.config('app.env')."\n";
echo 'APP_DEBUG: '.(config('app.debug') ? 'true' : 'false')."\n";
echo 'APP_URL: '.config('app.url')."\n";
echo 'DB_HOST: '.config('database.connections.mysql.host')."\n";
echo 'DB_DATABASE: '.config('database.connections.mysql.database')."\n";
echo 'DB_USERNAME: '.config('database.connections.mysql.username')."\n";
echo 'DB_PASSWORD set: '.(config('database.connections.mysql.password') !== '' && config('database.connections.mysql.password') !== null ? 'SÍ' : 'NO')."\n\n";

try {
    Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "Conexión MySQL: OK\n";
} catch (Throwable $e) {
    echo "Conexión MySQL: FALLO\n";
    echo $e->getMessage()."\n";
}

echo "\nBORRA public/env-check-once.php cuando termines.\n";

<?php

declare(strict_types=1);

/**
 * Ver el error real del 500 (solo diagnóstico).
 * Copiar a: public/show-error-once.php
 * Abrir: https://soporte.anlux.mx/show-error-once.php?key=TU_SECRETO
 * BORRAR después.
 */

const SHOW_ERROR_SECRET = 'CAMBIAR_por_secreto_largo';

$key = (string) ($_GET['key'] ?? '');
if ($key === '' || ! hash_equals(SHOW_ERROR_SECRET, $key)) {
    http_response_code(403);
    exit('Forbidden');
}

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$envPath = $root.'/.env';

echo "=== Archivo .env ===\n";
echo is_file($envPath) ? "OK en {$envPath}\n" : "NO ENCONTRADO en {$envPath}\n";

$configCache = $root.'/bootstrap/cache/config.php';
if (is_file($configCache)) {
    @unlink($configCache);
    echo "Eliminado bootstrap/cache/config.php\n";
}

echo "\n=== Arranque Laravel ===\n";

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "Bootstrap: OK\n";
    echo 'APP_ENV: '.config('app.env')."\n";
    echo 'DB_DATABASE: '.config('database.connections.mysql.database')."\n";
    echo 'DB_USERNAME: '.config('database.connections.mysql.username')."\n";
    echo 'DB_PASSWORD: '.(config('database.connections.mysql.password') !== '' ? 'configurada' : 'VACÍA')."\n";
    echo 'SESSION_DRIVER: '.config('session.driver')."\n";

    Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "MySQL: OK\n";

    $tables = ['login', 'sessions', 'cache', 'jobs'];
    foreach ($tables as $table) {
        $exists = Illuminate\Support\Facades\Schema::hasTable($table);
        echo "Tabla {$table}: ".($exists ? 'existe' : 'NO EXISTE')."\n";
    }

    $request = Illuminate\Http\Request::create('/login', 'GET');
    $response = $kernel->handle($request);
    echo "\nGET /login → HTTP ".$response->getStatusCode()."\n";
} catch (Throwable $e) {
    echo "\n=== ERROR ===\n";
    echo $e::class.': '.$e->getMessage()."\n\n";
    echo $e->getFile().':'.$e->getLine()."\n\n";
    echo $e->getTraceAsString();
}

$log = $root.'/storage/logs/laravel.log';
if (is_file($log)) {
    echo "\n\n=== Últimas líneas laravel.log ===\n";
    $lines = file($log) ?: [];
    echo implode('', array_slice($lines, -40));
}

echo "\n\nBORRA public/show-error-once.php\n";

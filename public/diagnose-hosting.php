<?php

declare(strict_types=1);

/**
 * Diagnóstico 500 en hosting (NO requiere Laravel).
 * URL: https://soporte.anlux.mx/diagnose-hosting.php?key=anlux2026diag
 * BORRAR este archivo cuando termines.
 */

const DIAG_KEY = 'anlux2026diag';

if (($_GET['key'] ?? '') !== DIAG_KEY) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font:14px monospace">';

$root = dirname(__DIR__);
echo "=== Diagnóstico Anlux Laravel ===\n\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'Raíz proyecto: '.$root."\n\n";

$checks = [
    'vendor/autoload.php' => $root.'/vendor/autoload.php',
    '.env' => $root.'/.env',
    'artisan' => $root.'/artisan',
    'public/index.php' => __DIR__.'/index.php',
    'storage (escribible)' => $root.'/storage',
    'bootstrap/cache (escribible)' => $root.'/bootstrap/cache',
];

foreach ($checks as $label => $path) {
    if (str_contains($label, 'escribible')) {
        $ok = is_dir($path) && is_writable($path);
    } else {
        $ok = is_file($path) || (is_dir($path) && $label === 'storage (escribible)');
    }
    echo ($ok ? '[OK] ' : '[FALTA/NO ESCRIBIBLE] ').$label."\n";
    if (! $ok && $label === 'vendor/autoload.php') {
        echo "    → Sube la carpeta vendor/ o ejecuta: composer install --no-dev\n";
    }
}

echo "\n=== .env (DB) ===\n";
$envPath = $root.'/.env';
$db = ['DB_HOST' => 'localhost', 'DB_DATABASE' => '', 'DB_USERNAME' => '', 'DB_PASSWORD' => ''];
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $m)) {
            $k = $m[1];
            $v = trim($m[2], " \t\"'");
            if (isset($db[$k]) || $k === 'APP_KEY' || $k === 'APP_DEBUG') {
                if ($k === 'DB_PASSWORD') {
                    echo "DB_PASSWORD: ".($v !== '' ? '***' : 'VACÍA')."\n";
                } elseif ($k === 'APP_KEY') {
                    echo 'APP_KEY: '.($v !== '' ? 'definida' : 'VACÍA (causa 500)')."\n";
                } else {
                    echo "{$k}={$v}\n";
                }
                if (isset($db[$k])) {
                    $db[$k] = $v;
                }
            }
        }
    }
} else {
    echo "No hay .env\n";
}

$configCache = $root.'/bootstrap/cache/config.php';
if (is_file($configCache)) {
    echo "\n[AVISO] Existe bootstrap/cache/config.php — bórralo si cambiaste .env\n";
}

echo "\n=== MySQL directo (PDO) ===\n";
if ($db['DB_DATABASE'] === '' || $db['DB_USERNAME'] === '') {
    echo "Faltan DB_DATABASE o DB_USERNAME en .env\n";
} else {
    try {
        $dsn = 'mysql:host='.$db['DB_HOST'].';dbname='.$db['DB_DATABASE'].';charset=utf8mb4';
        $pdo = new PDO($dsn, $db['DB_USERNAME'], $db['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "Conexión PDO: OK\n";
        $n = (int) $pdo->query('SELECT COUNT(*) FROM login')->fetchColumn();
        echo "Filas en tabla login: {$n}\n";
    } catch (Throwable $e) {
        echo "Conexión PDO: FALLO\n";
        echo $e->getMessage()."\n";
        echo "\n→ Revisa en cPanel: usuario MySQL, contraseña y que el usuario tenga acceso a anlux_os.\n";
        echo "  El usuario NO siempre es 'joses16'; mira el nombre anlux en Bases de datos MySQL.\n";
    }
}

echo "\n=== Laravel bootstrap ===\n";
if (! is_file($root.'/vendor/autoload.php')) {
    echo "Omitido (no hay vendor/)\n";
} else {
    if (is_file($configCache)) {
        @unlink($configCache);
        echo "config.php cache eliminado.\n";
    }
    try {
        require $root.'/vendor/autoload.php';
        $app = require $root.'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        echo "Bootstrap Laravel: OK\n";
        echo 'config DB_DATABASE: '.config('database.connections.mysql.database')."\n";
    } catch (Throwable $e) {
        echo "Bootstrap Laravel: FALLO\n";
        echo $e::class.': '.$e->getMessage()."\n";
        echo $e->getFile().':'.$e->getLine()."\n";
    }
}

$log = $root.'/storage/logs/laravel.log';
if (is_file($log)) {
    echo "\n=== Últimas 25 líneas laravel.log ===\n";
    $lines = file($log) ?: [];
    echo htmlspecialchars(implode('', array_slice($lines, -25)), ENT_QUOTES, 'UTF-8');
}

echo "\n\n=== Fin. BORRA public/diagnose-hosting.php ===\n";
echo '</pre>';

<?php

declare(strict_types=1);

/**
 * Diagnóstico del 500 en /admin/usuarios (ejecutar EN EL SERVIDOR).
 * URL: https://soporte.anlux.mx/diagnose-admin-usuarios.php?key=anlux2026usuarios
 * BORRAR cuando termines.
 */

const DIAG_KEY = 'anlux2026usuarios';

if (($_GET['key'] ?? '') !== DIAG_KEY) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font:13px monospace;white-space:pre-wrap">';

$root = dirname(__DIR__);
echo "=== Diagnóstico /admin/usuarios (500) ===\n\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'Raíz: '.$root."\n\n";

$mustExist = [
    'app/Http/Controllers/AdminUsersController.php',
    'resources/views/admin/users.blade.php',
    'resources/views/partials/nav-admin.blade.php',
    'resources/views/partials/admin-page-open.blade.php',
    'resources/views/partials/admin-page-close.blade.php',
    'routes/web.php',
    'public/legacy/assets/js/admin_users.js',
];

echo "=== Archivos críticos ===\n";
foreach ($mustExist as $rel) {
    $path = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    echo (is_file($path) ? '[OK] ' : '[FALTA] ').$rel."\n";
}

$ctrlPath = $root.'/app/Http/Controllers/AdminUsersController.php';
if (is_file($ctrlPath)) {
    $src = file_get_contents($ctrlPath) ?: '';
    $count = preg_match_all('/\bclass\s+AdminUsersController\b/', $src, $m);
    echo $count === 1
        ? "[OK] AdminUsersController.php — una sola declaración de clase\n"
        : "[ERROR] AdminUsersController.php — declaraciones de clase: {$count} (archivo duplicado/corrupto)\n";
}

$bladePath = $root.'/resources/views/admin/users.blade.php';
if (is_file($bladePath)) {
    $src = file_get_contents($bladePath) ?: '';
    $extends = substr_count($src, "@extends('layouts.anlux_app')");
    echo $extends === 1
        ? "[OK] users.blade.php — un solo @extends\n"
        : "[ERROR] users.blade.php — @extends repetido: {$extends}\n";
}

$configCache = $root.'/bootstrap/cache/config.php';
$routesCache = $root.'/bootstrap/cache/routes-v7.php';
foreach ([$configCache, $routesCache] as $cache) {
    if (is_file($cache)) {
        echo '[AVISO] Caché: '.basename($cache)." — se intentará borrar.\n";
        @unlink($cache);
    }
}

$viewsCache = $root.'/storage/framework/views';
if (is_dir($viewsCache) && is_writable($viewsCache)) {
    $cleared = 0;
    foreach (glob($viewsCache.'/*.php') ?: [] as $f) {
        if (@unlink($f)) {
            $cleared++;
        }
    }
    echo "Vistas compiladas borradas: {$cleared}\n";
}

echo "\n=== Bootstrap Laravel ===\n";
if (! is_file($root.'/vendor/autoload.php')) {
    echo "[FALTA] vendor/autoload.php\n";
    echo "\n=== Fin. BORRA public/diagnose-admin-usuarios.php ===\n";
    echo '</pre>';
    exit;
}

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $routeNames = [
        'admin.users.index',
        'admin.users.updatePassword',
        'admin.users.toggleActivo',
        'admin.users.destroy',
    ];

    echo "\n=== Rutas nombradas (admin usuarios) ===\n";
    foreach ($routeNames as $name) {
        $has = Illuminate\Support\Facades\Route::has($name);
        echo ($has ? '[OK] ' : '[FALTA] ').$name."\n";
        if (! $has) {
            echo "  → Sube routes/web.php y ejecuta: php artisan route:clear\n";
        }
    }

    echo "\n=== Tabla login ===\n";
    if (Illuminate\Support\Facades\Schema::hasTable('login')) {
        $n = Illuminate\Support\Facades\DB::table('login')->count();
        echo "[OK] login — {$n} fila(s)\n";
        if (Illuminate\Support\Facades\Schema::hasColumn('login', 'activo')) {
            echo "[OK] columna activo\n";
        } else {
            echo "[AVISO] sin columna activo (la página debería cargar igual)\n";
        }
    } else {
        echo "[FALTA] tabla login\n";
    }

    echo "\n=== Compilar vista admin.users ===\n";
    try {
        $usuarios = App\Models\User::query()
            ->select(['id_tecnico', 'nombre_tecnico', 'correo', 'perfil', 'activo'])
            ->orderBy('id_tecnico')
            ->get();

        $html = view('admin.users', [
            'pageTitle' => 'Test',
            'nombreTecnico' => 'Admin',
            'nav_admin_activo' => 'usuarios',
            'usuarios' => $usuarios,
        ])->render();
        echo '[OK] Vista admin.users compilada ('.strlen($html)." bytes, {$usuarios->count()} usuario(s))\n";
    } catch (Throwable $e) {
        echo '[ERROR VISTA] '.$e::class.': '.$e->getMessage()."\n";
        echo $e->getFile().':'.$e->getLine()."\n";
        if ($e->getPrevious() instanceof Throwable) {
            $p = $e->getPrevious();
            echo 'Causa: '.$p::class.': '.$p->getMessage()."\n";
        }
    }

} catch (Throwable $e) {
    echo "\n[FALLO BOOTSTRAP] ".$e::class.': '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}

$log = $root.'/storage/logs/laravel.log';
if (is_file($log)) {
    echo "\n=== Últimas 50 líneas storage/logs/laravel.log ===\n";
    $lines = file($log) ?: [];
    echo htmlspecialchars(implode('', array_slice($lines, -50)), ENT_QUOTES, 'UTF-8');
}

echo "\n\n=== Fin. BORRA public/diagnose-admin-usuarios.php ===\n";
echo '</pre>';

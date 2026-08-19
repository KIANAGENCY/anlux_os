<?php

declare(strict_types=1);

/**
 * Diagnóstico del 500 en /ordenes (ejecutar EN EL SERVIDOR).
 * URL: https://soporte.exactolp.mx/diagnose-ordenes.php?key=exacto2026ordenes
 * BORRAR cuando termines.
 */

const DIAG_KEY = 'exacto2026ordenes';

if (($_GET['key'] ?? '') !== DIAG_KEY) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font:13px monospace;white-space:pre-wrap">';

$root = dirname(__DIR__);
echo "=== Diagnóstico /ordenes (500) ===\n\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'Raíz: '.$root."\n\n";

$mustExist = [
    'app/Support/ExactoAuthContext.php',
    'app/View/Composers/NavExactoUserBarComposer.php',
    'app/Providers/AppServiceProvider.php',
    'app/Http/Middleware/ExactoUpdatePresence.php',
    'app/Services/UserPresenceService.php',
    'app/Services/ImpersonationService.php',
    'app/Services/OrdenEditLockService.php',
    'app/Http/Controllers/OrderController.php',
    'app/Services/RegistrarOrdenService.php',
    'app/Services/OrdenListService.php',
    'resources/views/partials/nav-exacto-user-bar.blade.php',
    'resources/views/partials/exacto-ui-modal.blade.php',
    'resources/views/orders/index.blade.php',
    'resources/views/partials/nav-app.blade.php',
    'bootstrap/app.php',
    'routes/web.php',
    'vendor/autoload.php',
];

echo "=== Archivos críticos ===\n";
$missing = [];
foreach ($mustExist as $rel) {
    $path = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $ok = is_file($path);
    echo ($ok ? '[OK] ' : '[FALTA] ').$rel."\n";
    if (! $ok) {
        $missing[] = $rel;
    }
}

if ($missing !== []) {
    echo "\n→ Sube los archivos [FALTA]. En Linux la carpeta debe ser app/View/Composers (V mayúscula).\n";
}

$registrarPath = $root.'/app/Services/RegistrarOrdenService.php';
if (is_file($registrarPath)) {
    $src = file_get_contents($registrarPath) ?: '';
    $classCount = preg_match_all('/\bfinal\s+class\s+RegistrarOrdenService\b/', $src, $m);
    echo $classCount === 1
        ? "[OK] RegistrarOrdenService.php — una sola clase\n"
        : "[ERROR] RegistrarOrdenService.php — declaraciones de clase: {$classCount} (archivo duplicado/corrupto)\n";
    $lint = shell_exec('php -l '.escapeshellarg($registrarPath).' 2>&1');
    if (is_string($lint) && $lint !== '') {
        echo trim($lint)."\n";
    }
    if (is_string($lint) && ! str_contains($lint, 'No syntax errors')) {
        echo "\n=== Últimas 6 líneas de RegistrarOrdenService.php (servidor) ===\n";
        $tail = array_slice(explode("\n", rtrim($src, "\n")), -6);
        foreach ($tail as $line) {
            echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
        }
        echo "\n→ Repara con: repair-registrar-orden.php?key=exacto2026repair\n";
        echo "  O sube de nuevo el archivo por Upload (~81 492 bytes, termina en línea 1993 con })\n";
    }
}

$routesCache = $root.'/bootstrap/cache/routes-v7.php';
if (is_file($routesCache)) {
    echo "\n[AVISO] Existe bootstrap/cache/routes-v7.php — se intentará borrar.\n";
    @unlink($routesCache);
}

$configCache = $root.'/bootstrap/cache/config.php';
if (is_file($configCache)) {
    echo "\n[AVISO] Existe bootstrap/cache/config.php — se intentará borrar.\n";
    @unlink($configCache);
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
} else {
    echo "[AVISO] storage/framework/views no escribible\n";
}

echo "\n=== Base de datos (desde Laravel) ===\n";
if (! is_file($root.'/vendor/autoload.php')) {
    echo "Sin vendor/ — no se puede probar Laravel.\n";
    echo "\n=== Fin. BORRA public/diagnose-ordenes.php ===\n";
    echo '</pre>';
    exit;
}

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo 'APP_ENV: '.config('app.env')."\n";
    echo 'DB: '.config('database.connections.mysql.database')."\n";

    $checks = [
        'login' => ['activo', 'last_seen_at'],
        'impersonation_requests' => null,
        'orden_servicio_edit_locks' => null,
        'orden_servicio_nombre_busqueda' => null,
    ];

    foreach ($checks as $table => $cols) {
        $has = Illuminate\Support\Facades\Schema::hasTable($table);
        echo ($has ? '[OK] ' : '[FALTA] ')."tabla {$table}\n";
        if ($has && is_array($cols)) {
            foreach ($cols as $col) {
                $hc = Illuminate\Support\Facades\Schema::hasColumn($table, $col);
                echo '  '.($hc ? '[OK] ' : '[FALTA] ')."columna {$col}\n";
            }
        }
    }

    echo "\n=== Clase RegistrarOrdenService (OrderController la necesita) ===\n";
    try {
        if (class_exists(App\Services\RegistrarOrdenService::class)) {
            echo "[OK] class_exists RegistrarOrdenService\n";
            app(App\Services\RegistrarOrdenService::class);
            echo "[OK] RegistrarOrdenService instanciado\n";
        } else {
            echo "[FALTA] No se carga App\\Services\\RegistrarOrdenService\n";
        }
    } catch (Throwable $e) {
        echo '[ERROR] '.$e::class.': '.$e->getMessage()."\n";
        echo $e->getFile().':'.$e->getLine()."\n";
    }

    echo "\n=== Clase NavExactoUserBarComposer ===\n";
    if (class_exists(App\View\Composers\NavExactoUserBarComposer::class)) {
        echo "[OK] class_exists NavExactoUserBarComposer\n";
    } else {
        echo "[FALTA] No se carga App\\View\\Composers\\NavExactoUserBarComposer\n";
        echo "  Ruta esperada: app/View/Composers/NavExactoUserBarComposer.php\n";
    }

    echo "\n=== Compilar vista nav (como /ordenes) ===\n";
    try {
        $html = view('partials.nav-exacto-user-bar', [
            'nombreTecnico' => 'Prueba',
            'exactoIsImpersonating' => false,
            'exactoIsAdmin' => false,
            'exactoIsTechnician' => true,
            'exactoUserId' => 1,
            'nombreTecnicoMostrado' => 'Prueba',
            'exactoUiJsV' => 1,
            'exactoNavImpV' => 1,
        ])->render();
        echo '[OK] Vista nav-exacto-user-bar compilada ('.strlen($html).' bytes)'."\n";
    } catch (Throwable $e) {
        echo "[ERROR VISTA] ".$e::class.': '.$e->getMessage()."\n";
        echo $e->getFile().':'.$e->getLine()."\n";
    }

    echo "\n=== Compilar orders.index ===\n";
    try {
        $html = view('orders.index', [
            'nombreTecnico' => 'Prueba',
            'nav_activo' => 'ordenes',
            'pageTitle' => 'Test',
            'pageHeadExtra' => '',
        ])->render();
        echo '[OK] Vista orders.index compilada ('.strlen($html).' bytes)'."\n";
    } catch (Throwable $e) {
        echo "[ERROR VISTA INDEX] ".$e::class.': '.$e->getMessage()."\n";
        echo $e->getFile().':'.$e->getLine()."\n";
    }

} catch (Throwable $e) {
    echo "\n[FALLO BOOTSTRAP] ".$e::class.': '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}

$log = $root.'/storage/logs/laravel.log';
if (is_file($log)) {
    echo "\n=== Últimas 40 líneas storage/logs/laravel.log (SERVIDOR) ===\n";
    $lines = file($log) ?: [];
    echo htmlspecialchars(implode('', array_slice($lines, -40)), ENT_QUOTES, 'UTF-8');
} else {
    echo "\n(No hay laravel.log en el servidor)\n";
}

echo "\n\n=== Fin. BORRA public/diagnose-ordenes.php ===\n";
echo '</pre>';

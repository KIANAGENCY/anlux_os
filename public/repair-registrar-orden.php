<?php

declare(strict_types=1);

/**
 * Repara RegistrarOrdenService.php si quedó basura al final (p. ej. "--" en línea 1994).
 * URL: https://soporte.exactolp.mx/repair-registrar-orden.php?key=exacto2026repair
 * BORRAR cuando termines.
 */

const REPAIR_KEY = 'exacto2026repair';

if (($_GET['key'] ?? '') !== REPAIR_KEY) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font:13px monospace;white-space:pre-wrap">';

$root = dirname(__DIR__);
$path = $root.'/app/Services/RegistrarOrdenService.php';

echo "=== Reparar RegistrarOrdenService.php ===\n\n";

if (! is_file($path)) {
    echo "[FALTA] {$path}\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

$original = file_get_contents($path) ?: '';
$bytesBefore = strlen($original);
$linesBefore = substr_count($original, "\n") + 1;

echo 'Tamaño actual: '.$bytesBefore." bytes, ~{$linesBefore} líneas\n";

$lintBefore = shell_exec('php -l '.escapeshellarg($path).' 2>&1');
echo 'php -l antes: '.trim((string) $lintBefore)."\n\n";

if (is_string($lintBefore) && str_contains($lintBefore, 'No syntax errors')) {
    echo "[OK] El archivo ya no tiene error de sintaxis. No hace falta reparar.\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

echo "=== Últimas 8 líneas del archivo en servidor ===\n";
$tailLines = array_slice(explode("\n", rtrim($original, "\n")), -8);
foreach ($tailLines as $i => $line) {
    echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
}
echo "\n";

$classNeedle = 'final class RegistrarOrdenService';
$classPos = strpos($original, $classNeedle);
if ($classPos === false) {
    echo "[ERROR] No se encontró «{$classNeedle}». Sube el archivo completo por Upload.\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

$openBrace = strpos($original, '{', $classPos);
if ($openBrace === false) {
    echo "[ERROR] No se encontró la llave de apertura de la clase.\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

$brace = 0;
$endPos = null;
$len = strlen($original);
for ($i = $openBrace; $i < $len; $i++) {
    $char = $original[$i];
    if ($char === '{') {
        $brace++;
    } elseif ($char === '}') {
        $brace--;
        if ($brace === 0) {
            $endPos = $i + 1;
            break;
        }
    }
}

if ($endPos === null) {
    echo "[ERROR] No se pudo localizar el cierre de la clase. Sube el archivo limpio por Upload.\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

$clean = substr($original, 0, $endPos)."\n";
$removed = $bytesBefore - strlen($clean);

if ($removed <= 0) {
    echo "[ERROR] No hay basura detectada al final; el error puede estar dentro del archivo.\n";
    echo "Sube de nuevo app/Services/RegistrarOrdenService.php por Upload (81 492 bytes).\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

$backup = $path.'.bak.'.date('YmdHis');
if (! copy($path, $backup)) {
    echo "[ERROR] No se pudo crear backup en {$backup}\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

echo "Backup creado: {$backup}\n";
echo "Bytes eliminados al final: {$removed}\n";

if (file_put_contents($path, $clean) === false) {
    echo "[ERROR] No se pudo escribir el archivo reparado.\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

$lintAfter = shell_exec('php -l '.escapeshellarg($path).' 2>&1');
echo 'php -l después: '.trim((string) $lintAfter)."\n\n";

if (! is_string($lintAfter) || ! str_contains($lintAfter, 'No syntax errors')) {
    echo "[ERROR] Sigue fallando php -l. Restaura el backup y sube el archivo completo por Upload.\n";
    echo "\n=== Fin. BORRA public/repair-registrar-orden.php ===\n</pre>";
    exit;
}

$viewsCache = $root.'/storage/framework/views';
if (is_dir($viewsCache) && is_writable($viewsCache)) {
    $cleared = 0;
    foreach (glob($viewsCache.'/*.php') ?: [] as $f) {
        if (@unlink($f)) {
            $cleared++;
        }
    }
    echo "Vistas compiladas borradas: {$cleared}\n\n";
}

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    app(App\Services\RegistrarOrdenService::class);
    echo "[OK] RegistrarOrdenService instanciado correctamente.\n";
    echo "[OK] Prueba ahora: https://soporte.exactolp.mx/ordenes\n";
} catch (Throwable $e) {
    echo '[ERROR Laravel] '.$e::class.': '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}

echo "\n=== Fin. BORRA public/repair-registrar-orden.php y public/diagnose-ordenes.php ===\n";
echo '</pre>';

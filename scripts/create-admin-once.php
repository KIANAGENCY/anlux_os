<?php

declare(strict_types=1);

/**
 * Crear administrador en tabla `login` (cPanel sin SSH).
 *
 * 1. Copia este archivo a public/create-admin-once.php en el servidor.
 * 2. Edita CREATE_ADMIN_SECRET abajo (texto largo y único).
 * 3. Abre: https://soporte.anlux.mx/create-admin-once.php?key=TU_SECRETO
 *    Opcional: &email=admin@soporte.anlux.mx&password=TuClave12!
 * 4. BORRA public/create-admin-once.php después de usarlo.
 */

const CREATE_ADMIN_SECRET = 'CAMBIAR_por_un_texto_largo_y_secreto';

if (PHP_SAPI === 'cli') {
    fwrite(STDERR, "Usa este script por navegador en el servidor (o define CREATE_ADMIN_SECRET y parámetros).\n");
    exit(1);
}

$key = (string) ($_GET['key'] ?? '');
if ($key === '' || ! hash_equals(CREATE_ADMIN_SECRET, $key)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden');
}

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\AnluxVaultService;
use App\Services\LoginIdSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

header('Content-Type: text/plain; charset=utf-8');

if (! Schema::hasTable('login')) {
    exit("Error: no existe la tabla login.\n");
}

$email = trim((string) ($_GET['email'] ?? 'admin@soporte.anlux.mx'));
$password = (string) ($_GET['password'] ?? 'AnluxAdmin12#');
$nombre = trim((string) ($_GET['nombre'] ?? 'Administrador'));
$celularDigits = preg_replace('/\D+/', '', (string) ($_GET['celular'] ?? '5510000999')) ?? '';

if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    exit("Error: email inválido.\n");
}

if (mb_strlen($password, 'UTF-8') < 8) {
    exit("Error: contraseña muy corta (mín. 8).\n");
}

if (! preg_match('/^\d{7,15}$/', $celularDigits)) {
    exit("Error: celular inválido (7-15 dígitos).\n");
}

/** @var AnluxVaultService $vault */
$vault = app(AnluxVaultService::class);
$correo = mb_strtolower($email, 'UTF-8');
$nombreToken = $vault->tecnicoNombreToken($nombre);
$nombreSeal = $vault->tecnicoNombreSeal($nombre);
$celularStore = $vault->loginCelularHashStore($celularDigits);

$exists = User::query()
    ->where('correo', $correo)
    ->orWhere('nombre_tecnico_token', $nombreToken)
    ->exists();

if ($exists) {
    exit("Error: ya existe un usuario con ese correo o nombre.\n");
}

try {
    $idTecnico = DB::transaction(function () use ($nombreSeal, $nombreToken, $correo, $password, $celularStore, $loginIdSequence): int {
        /** @var LoginIdSequenceService $loginIdSequence */
        $loginIdSequence = app(LoginIdSequenceService::class);
        $next = $loginIdSequence->reserveNextId();
        DB::insert(
            'INSERT INTO login (id_tecnico, nombre_tecnico, nombre_tecnico_token, correo, contrasena, celular, perfil) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$next, $nombreSeal, $nombreToken, $correo, Hash::make($password), $celularStore, 'administrador']
        );

        return $next;
    });
} catch (Throwable $e) {
    exit('Error al insertar: '.$e->getMessage()."\n");
}

echo "OK. Administrador creado.\n";
echo "id_tecnico: {$idTecnico}\n";
echo "correo: {$correo}\n";
echo "contraseña: (la que enviaste en ?password= o AnluxAdmin12# por defecto)\n";
echo "Entra en: ".url('/login')."\n";
echo "\nBORRA public/create-admin-once.php ahora.\n";

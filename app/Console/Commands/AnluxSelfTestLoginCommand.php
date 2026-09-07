<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AnluxVaultService;
use App\Services\LoginIdSequenceService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AnluxSelfTestLoginCommand extends Command
{
    protected $signature = 'anlux:self-test-login
                            {--keep-user : No eliminar el usuario al final (solo depuración)}';

    protected $description = 'Registro de prueba en login, verificación de sesión y eliminación (E2E por consola).';

    public function handle(AnluxVaultService $vault, LoginIdSequenceService $loginIdSequence): int
    {
        if (app()->environment('production') && ! $this->confirm('APP_ENV=production. ¿Continuar?', false)) {
            return self::INVALID;
        }

        if (! Schema::hasTable('login')) {
            $this->error('No existe la tabla login.');

            return self::FAILURE;
        }

        $suffix = (string) time();
        $correo = 'anlux.selftest.'.$suffix.'@example.invalid';
        $nombre = 'Anlux SelfTest '.$suffix;
        $password = 'SelfTest1!';
        $celularDigits = '5512345678';
        $perfil = 'tecnico';

        $nombreToken = $vault->tecnicoNombreToken($nombre);
        $nombreSeal = $vault->tecnicoNombreSeal($nombre);
        $celularStore = $vault->loginCelularHashStore($celularDigits);

        try {
            $idTecnico = DB::transaction(function () use ($nombreSeal, $nombreToken, $correo, $password, $celularStore, $perfil, $loginIdSequence): int {
                $next = $loginIdSequence->reserveNextId();
                DB::insert(
                    'INSERT INTO login (id_tecnico, nombre_tecnico, nombre_tecnico_token, correo, contrasena, celular, perfil) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$next, $nombreSeal, $nombreToken, $correo, Hash::make($password), $celularStore, $perfil]
                );

                return $next;
            });
        } catch (\Throwable $e) {
            $this->error('No se pudo insertar el usuario: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Usuario creado: id_tecnico={$idTecnico}, correo={$correo}");

        Auth::logout();
        if (! Auth::attempt(['email' => $correo, 'password' => $password])) {
            $this->error('Auth::attempt falló (credenciales o proveedor).');
            $this->cleanupUser($idTecnico, $correo);

            return self::FAILURE;
        }
        $this->info('Auth::attempt OK (sesión de consola).');
        Auth::logout();

        $httpOk = $this->probeHttpOrdenes($correo, $password);
        if (! $httpOk) {
            $this->error('La petición HTTP a /ordenes no devolvió 200 (middleware o CSRF).');
            $this->cleanupUser($idTecnico, $correo);

            return self::FAILURE;
        }
        $this->info('GET /ordenes con sesión: 200 OK.');

        if ($this->option('keep-user')) {
            $this->warn('Se mantiene el usuario (--keep-user). Elimínalo manualmente si hace falta.');

            return self::SUCCESS;
        }

        $this->cleanupUser($idTecnico, $correo);
        $this->info('Usuario de prueba eliminado.');

        return self::SUCCESS;
    }

    private function probeHttpOrdenes(string $correo, string $password): bool
    {
        $kernel = app(Kernel::class);
        /** @var Session $session */
        $session = app('session')->driver();
        $session->start();

        $getLogin = Request::create('/login', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
        ]);
        $getLogin->setLaravelSession($session);
        $r1 = $kernel->handle($getLogin);
        if ($r1->getStatusCode() !== 200) {
            $this->line('GET /login → '.$r1->getStatusCode());

            return false;
        }

        $token = $session->token();
        $post = Request::create('/login', 'POST', [
            '_token' => $token,
            'email' => $correo,
            'password' => $password,
        ], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ]);
        $post->setLaravelSession($session);
        $r2 = $kernel->handle($post);
        if (! in_array($r2->getStatusCode(), [302, 303], true)) {
            $this->line('POST /login → '.$r2->getStatusCode());

            return false;
        }

        $getOrdenes = Request::create('/ordenes', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $getOrdenes->setLaravelSession($session);
        $r3 = $kernel->handle($getOrdenes);

        return $r3->getStatusCode() === 200;
    }

    private function cleanupUser(?int $idTecnico, string $correo): void
    {
        try {
            if ($idTecnico !== null && Schema::hasTable('login_remember_tokens')) {
                DB::delete('DELETE FROM login_remember_tokens WHERE id_tecnico = ?', [$idTecnico]);
            }
        } catch (\Throwable) {
            // ignore
        }
        try {
            User::query()->where('correo', $correo)->delete();
        } catch (\Throwable $e) {
            $this->warn('Limpieza parcial: '.$e->getMessage());
        }
    }
}

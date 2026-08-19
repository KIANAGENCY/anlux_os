<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ExactoVaultService;
use App\Services\LoginIdSequenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ExactoCreateAdminCommand extends Command
{
    protected $signature = 'exacto:create-admin
                            {email? : Correo de acceso (login)}
                            {--password= : Si se omite, se pide en consola (oculto)}
                            {--nombre=Administrador : Nombre visible del administrador}
                            {--celular=5510000001 : Celular solo dígitos (7–15), como en registro admin}
                            {--force : En producción, crear sin confirmación interactiva}';

    protected $description = 'Crea un usuario en la tabla login con perfil administrador (primer acceso a /admin sin tener ya un admin).';

    public function handle(ExactoVaultService $vault, LoginIdSequenceService $loginIdSequence): int
    {
        if (app()->environment('production') && ! $this->option('force') && ! $this->confirm('APP_ENV=production. ¿Crear este administrador?', false)) {
            return self::INVALID;
        }

        if (! Schema::hasTable('login')) {
            $this->error('No existe la tabla login.');

            return self::FAILURE;
        }

        $email = trim((string) ($this->argument('email') ?: $this->ask('Correo electrónico')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Correo inválido.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?? '');
        if ($password === '') {
            $password = (string) $this->secret('Contraseña (mín. 8, letras y número o carácter especial)');
        }

        if (! $this->passwordPolicy($password)) {
            $this->error('La contraseña debe tener al menos 8 caracteres, incluir letras y al menos un número o un carácter especial.');

            return self::FAILURE;
        }

        $nombre = trim((string) $this->option('nombre'));
        if ($nombre === '') {
            $this->error('El nombre no puede estar vacío.');

            return self::FAILURE;
        }

        $celularDigits = preg_replace('/\D+/', '', (string) $this->option('celular')) ?? '';
        if (! preg_match('/^\d{7,15}$/', $celularDigits)) {
            $this->error('Celular inválido: usa solo dígitos, entre 7 y 15.');

            return self::FAILURE;
        }

        $nombreToken = $vault->tecnicoNombreToken($nombre);
        $nombreSeal = $vault->tecnicoNombreSeal($nombre);
        $celularStore = $vault->loginCelularHashStore($celularDigits);

        $exists = User::query()
            ->where('correo', mb_strtolower($email, 'UTF-8'))
            ->orWhere('nombre_tecnico_token', $nombreToken)
            ->orWhere('celular', $celularStore)
            ->exists();
        if ($exists) {
            $this->error('Ya existe un usuario con ese correo, nombre equivalente o mismo celular.');

            return self::FAILURE;
        }

        $correo = mb_strtolower($email, 'UTF-8');
        $perfil = 'administrador';

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
            $this->error('No se pudo insertar: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Administrador creado (id_tecnico={$idTecnico}).");
        $this->line('Entra en <fg=cyan>'.url('/login').'</> con ese correo y contraseña; tras el login irás a <fg=cyan>'.url('/admin').'</>.');

        return self::SUCCESS;
    }

    private function passwordPolicy(string $password): bool
    {
        if (mb_strlen($password, 'UTF-8') < 8) {
            return false;
        }
        $hasLetter = preg_match('/\p{L}/u', $password) === 1 || preg_match('/[a-zA-Z]/', $password) === 1;
        $hasDigit = preg_match('/[0-9]/', $password) === 1;
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password) === 1;

        return $hasLetter && ($hasDigit || $hasSpecial);
    }
}

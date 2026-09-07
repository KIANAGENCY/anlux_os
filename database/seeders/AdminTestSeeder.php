<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Services\AnluxVaultService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Usuario administrador para pruebas locales (correo fijo, contraseña conocida).
 * No ejecuta nada en production salvo ANLUX_SEED_ADMIN=true en .env.
 */
class AdminTestSeeder extends Seeder
{
    public const EMAIL = 'admin@anlux.test';

    public const PASSWORD = 'AnluxAdmin12#';

    public function run(): void
    {
        if (app()->environment('production')
            && ! filter_var((string) env('ANLUX_SEED_ADMIN', ''), FILTER_VALIDATE_BOOL)
            && empty($GLOBALS['ANLUX_FORCE_TEST_LOGIN_SEED'] ?? false)) {
            return;
        }

        if (! Schema::hasTable('login')) {
            return;
        }

        $vault = app(AnluxVaultService::class);
        $nombre = 'Administrador pruebas';
        $correo = mb_strtolower(self::EMAIL, 'UTF-8');
        $celularDigits = '5510000999';
        $nombreToken = $vault->tecnicoNombreToken($nombre);
        $nombreSeal = $vault->tecnicoNombreSeal($nombre);
        $celularStore = $vault->loginCelularHashStore($celularDigits);
        $hash = Hash::make(self::PASSWORD);

        $existing = User::query()->where('correo', $correo)->first();
        if ($existing) {
            $existing->forceFill([
                'nombre_tecnico' => $nombreSeal,
                'nombre_tecnico_token' => $nombreToken,
                'contrasena' => $hash,
                'celular' => $celularStore,
                'perfil' => 'administrador',
            ])->save();

            if ($this->command) {
                $this->command->info('Admin de pruebas actualizado: '.self::EMAIL.' / '.self::PASSWORD);
            }

            return;
        }

        DB::transaction(function () use ($nombreSeal, $nombreToken, $correo, $hash, $celularStore): void {
            $max = (int) (DB::scalar('SELECT MAX(id_tecnico) FROM login') ?? 0);
            $next = $max + 1;
            DB::insert(
                'INSERT INTO login (id_tecnico, nombre_tecnico, nombre_tecnico_token, correo, contrasena, celular, perfil) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$next, $nombreSeal, $nombreToken, $correo, $hash, $celularStore, 'administrador']
            );
        });

        if ($this->command) {
            $this->command->info('Admin de pruebas creado: '.self::EMAIL.' / '.self::PASSWORD);
        }
    }
}

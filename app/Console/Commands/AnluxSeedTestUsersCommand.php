<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\AdminTestSeeder;
use Database\Seeders\TecnicoTestSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Inserta usuarios de prueba en login usando siempre la conexión "mysql" del .env
 * (DB_HOST, DB_DATABASE, etc.), aunque el default de la app esté en sqlite.
 */
class AnluxSeedTestUsersCommand extends Command
{
    protected $signature = 'anlux:seed-test-users
                            {--database= : Nombre de la base MySQL (por defecto: anlux, o el valor sano si DB_DATABASE apunta a un .sqlite)}';

    protected $description = 'Crea/actualiza admin@anlux.test y tecnico@anlux.test en la tabla login usando la conexión mysql (Laragon), no SQLite.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->confirm('¿Ejecutar en production?', false)) {
            return self::INVALID;
        }

        $originalDefault = (string) config('database.default');

        if (! config('database.connections.mysql')) {
            $this->error('No hay conexión "mysql" en config/database.php.');

            return self::FAILURE;
        }

        $mysqlDb = (string) config('database.connections.mysql.database');
        if ($mysqlDb === '' || str_contains(strtolower($mysqlDb), '.sqlite')) {
            $mysqlDb = (string) ($this->option('database') ?: 'anlux');
            config(['database.connections.mysql.database' => $mysqlDb]);
            $this->warn('DB_DATABASE apuntaba a SQLite; se usará la base MySQL: '.$mysqlDb);
        }

        config(['database.default' => 'mysql']);
        DB::purge('mysql');
        DB::purge($originalDefault);

        try {
            $conn = DB::connection();
            $driver = $conn->getDriverName();
            $dbName = (string) $conn->getDatabaseName();

            $this->line('Conexión usada: <fg=cyan>'.$driver.'</> / base: <fg=cyan>'.$dbName.'</>');

            if ($driver === 'sqlite') {
                $this->error('La conexión "mysql" del .env sigue apuntando a SQLite. Revisa DB_CONNECTION y DB_DATABASE en .env.');

                return self::FAILURE;
            }

            $GLOBALS['ANLUX_FORCE_TEST_LOGIN_SEED'] = true;
            try {
                $admin = new AdminTestSeeder;
                $admin->setCommand($this);
                $admin->run();

                $tec = new TecnicoTestSeeder;
                $tec->setCommand($this);
                $tec->run();
            } finally {
                unset($GLOBALS['ANLUX_FORCE_TEST_LOGIN_SEED']);
            }

            $this->newLine();
            $this->info('Listo. En phpMyAdmin abre la base <fg=yellow>'.$dbName.'</> y la tabla <fg=yellow>login</>.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Fallo al escribir en MySQL: '.$e->getMessage());
            $this->line('Comprueba que MySQL esté encendido en Laragon y que exista la base `'.config('database.connections.mysql.database').'`.');

            return self::FAILURE;
        } finally {
            config(['database.default' => $originalDefault]);
            DB::purge('mysql');
            DB::purge($originalDefault);
        }
    }
}

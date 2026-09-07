<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vacía órdenes y deja solo el admin (omite tablas que no existan).
 */
class AnluxWipeDominioCommand extends Command
{
    protected $signature = 'anlux:wipe-dominio
                            {--force : Sin confirmación (obligatorio en production)}
                            {--admin=admin@soporte.anlux.mx : Correo del admin a conservar}';

    protected $description = 'Vacía órdenes y usuarios; conserva un admin y su contraseña.';

    /** @var list<string> */
    private const ORDEN_TABLES = [
        'materiales_orden',
        'trabajos_orden',
        'equipos_orden',
        'orden_servicio_t',
        'orden_servicio_tecnico_log',
        'orden_servicio_audit_log',
        'orden_servicio_nombre_busqueda',
        'orden_servicio_c',
        'orden_folio_sequence',
        'order_whatsapp_notifications',
        'orden_servicio_edit_locks',
        'impersonation_requests',
        'failed_jobs',
        'jobs',
        'job_batches',
        'security_activity_log',
    ];

    public function handle(): int
    {
        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->error('Solo MySQL/MariaDB. Conexión: '.$driver);

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('En producción use: php artisan anlux:wipe-dominio --force');

            return self::FAILURE;
        }

        $adminEmail = (string) $this->option('admin');
        if ($adminEmail === '') {
            $this->error('Correo admin vacío.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Se borrarán todas las órdenes y todos los usuarios excepto {$adminEmail}. ¿Continuar?",
            false
        )) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('login')) {
            $this->error('No existe la tabla login.');

            return self::FAILURE;
        }

        $admin = DB::table('login')->where('correo', $adminEmail)->first();
        if ($admin === null) {
            $this->error("No hay usuario con correo: {$adminEmail}");

            return self::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (self::ORDEN_TABLES as $table) {
                $this->deleteTableIfExists($table);
            }

            if (Schema::hasTable('login_remember_tokens')) {
                DB::table('login_remember_tokens')
                    ->where('id_tecnico', '<>', (int) $admin->id_tecnico)
                    ->delete();
            }

            DB::table('login')->where('correo', '<>', $adminEmail)->delete();

            DB::table('login')
                ->where('correo', $adminEmail)
                ->update(['activo' => 1, 'perfil' => 'administrador']);

            if (Schema::hasTable('orden_folio_sequence')) {
                DB::table('orden_folio_sequence')->updateOrInsert(
                    ['anio' => (int) date('Y')],
                    ['next_num' => 1]
                );
            }

            if (Schema::hasTable('anlux_login_sequences')) {
                DB::table('anlux_login_sequences')->updateOrInsert(
                    ['sequence_key' => 'login_id_tecnico'],
                    ['next_id' => 2, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $users = DB::table('login')->count();
        $orders = Schema::hasTable('orden_servicio_c')
            ? DB::table('orden_servicio_c')->count()
            : -1;
        $waChats = Schema::hasTable('wa_conversations')
            ? DB::table('wa_conversations')->count()
            : -1;

        $this->info("Listo. Usuarios en login: {$users}. Órdenes: {$orders}. Chats WA: {$waChats}.");

        return self::SUCCESS;
    }

    private function deleteTableIfExists(string $table): void
    {
        if (! Schema::hasTable($table)) {
            $this->warn("Omite (no existe): {$table}");

            return;
        }

        DB::table($table)->delete();
        try {
            DB::statement('ALTER TABLE `'.$table.'` AUTO_INCREMENT = 1');
        } catch (\Throwable) {
            // Tablas sin AUTO_INCREMENT
        }
        $this->line("Vaciada: {$table}");
    }
}

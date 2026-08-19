<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vacía tablas del dominio de órdenes (Exacto) y reinicia AUTO_INCREMENT vía TRUNCATE.
 */
class ExactoWipeOrdenesDataCommand extends Command
{
    protected $signature = 'exacto:wipe-ordenes-data
                            {--force : Sin confirmación (obligatorio si APP_ENV=production)}
                            {--keep-security-log : No vaciar security_activity_log}
                            {--include-sersop : También vaciar sersop_catalog}';

    protected $description = 'Vacía tablas de órdenes Exacto y reinicia contadores AUTO_INCREMENT (MySQL/MariaDB).';

    /**
     * @return list<string>
     */
    private function ordenRelatedTables(bool $includeSecurityLog, bool $includeSersop): array
    {
        $tables = [
            'materiales_orden',
            'trabajos_orden',
            'equipos_orden',
            'orden_servicio_t',
            'orden_servicio_tecnico_log',
            'orden_servicio_audit_log',
            'orden_servicio_nombre_busqueda',
            'orden_servicio_c',
            'orden_folio_sequence',
        ];

        if ($includeSecurityLog) {
            $tables[] = 'security_activity_log';
        }

        if ($includeSersop) {
            $tables[] = 'sersop_catalog';
        }

        return $tables;
    }

    public function handle(): int
    {
        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->error('Este comando solo funciona con MySQL o MariaDB. Conexión actual: '.$driver);

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('En producción debe indicar --force (tras respaldo de la base de datos).');

            return self::FAILURE;
        }

        $includeSecurity = ! $this->option('keep-security-log');
        $includeSersop = (bool) $this->option('include-sersop');

        if (! $this->option('force') && ! $this->confirm(
            'Se eliminarán todos los registros de órdenes y datos relacionados'.($includeSecurity ? ' y el log de seguridad' : '').($includeSersop ? ', y el catálogo sersop' : '').'. ¿Continuar?',
            false
        )) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $tables = $this->ordenRelatedTables($includeSecurity, $includeSersop);
        $toTruncate = [];
        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                $toTruncate[] = $t;
            } else {
                $this->warn("Tabla no existe, se omite: {$t}");
            }
        }

        if ($toTruncate === []) {
            $this->error('No hay tablas para vaciar.');

            return self::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($toTruncate as $t) {
                DB::statement('TRUNCATE TABLE `'.$t.'`');
                $this->line("TRUNCATE: {$t}");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Hecho: datos eliminados y AUTO_INCREMENT reiniciados en las tablas listadas.');

        return self::SUCCESS;
    }
}

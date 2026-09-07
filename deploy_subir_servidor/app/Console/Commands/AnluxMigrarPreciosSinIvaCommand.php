<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\MaterialesOrdenClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra catálogo SERSOP y órdenes del modelo viejo (trabajos/anticipos con IVA)
 * al modelo unificado: precios y montos SIN IVA; IVA solo en totales.
 */
class AnluxMigrarPreciosSinIvaCommand extends Command
{
    protected $signature = 'anlux:migrar-precios-sin-iva
                            {--dry-run : Solo muestra conteos, no escribe}
                            {--force : Sin confirmación}';

    protected $description = 'Convierte precios SERSOP y montos de órdenes a SIN IVA (÷1.16) y recalcula totales.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $marker = storage_path('app/anlux_precios_sin_iva.migrated');
        if (! $dry && is_file($marker)) {
            $this->error('Ya existe el marcador de migración (storage/app/anlux_precios_sin_iva.migrated). No se vuelve a ejecutar para evitar doble conversión.');

            return self::FAILURE;
        }
        if (! $dry && ! $this->option('force') && ! $this->confirm('¿Migrar catálogo SERSOP y órdenes a precios SIN IVA?', false)) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $factor = 1.16;

        if (Schema::hasTable('sersop_catalog')) {
            $n = (int) DB::scalar('SELECT COUNT(*) FROM sersop_catalog');
            $this->info("Catálogo SERSOP: {$n} filas".($dry ? ' (dry-run)' : ''));
            if (! $dry && $n > 0) {
                DB::update('UPDATE sersop_catalog SET precio = ROUND(precio / ?, 2), updated_at = ?', [$factor, now()->format('Y-m-d H:i:s')]);
            }
        } else {
            $this->warn('Tabla sersop_catalog no existe; se omite.');
        }

        if (! Schema::hasTable('trabajos_orden') || ! Schema::hasTable('materiales_orden') || ! Schema::hasTable('orden_servicio_t')) {
            $this->error('Faltan tablas de órdenes; abortando parte de órdenes.');

            return self::FAILURE;
        }

        $trabajos = (int) DB::scalar('SELECT COUNT(*) FROM trabajos_orden');
        $this->info("trabajos_orden: {$trabajos} filas".($dry ? ' (dry-run)' : ''));
        if (! $dry && $trabajos > 0) {
            DB::update('UPDATE trabajos_orden SET importe = ROUND(importe / ?, 2)', [$factor]);
        }

        $mats = DB::select('SELECT id_material, id_trabajo, vale, codigo, descripcion, cantidad, precio_unitario, importe, anticipo, ticket FROM materiales_orden');
        $nMat = 0;
        $nAnt = 0;
        foreach ($mats as $row) {
            $arr = (array) $row;
            $tipo = MaterialesOrdenClassifier::classify($arr);
            if ($tipo === 'abono' || $tipo === 'anticipo') {
                $nAnt++;
                if (! $dry) {
                    $nuevo = round(((float) ($arr['anticipo'] ?? 0)) / $factor, 2);
                    DB::update('UPDATE materiales_orden SET anticipo = ? WHERE id_material = ?', [$nuevo, (int) $arr['id_material']]);
                }
            } else {
                $nMat++;
                if (! $dry) {
                    $cant = (float) ($arr['cantidad'] ?? 0);
                    $precio = (float) ($arr['precio_unitario'] ?? 0);
                    $importe = round($cant * $precio, 2);
                    DB::update('UPDATE materiales_orden SET importe = ? WHERE id_material = ?', [$importe, (int) $arr['id_material']]);
                }
            }
        }
        $this->info("materiales reales: {$nMat}; anticipos/abonos: {$nAnt}".($dry ? ' (dry-run)' : ''));

        $ordenes = DB::select('SELECT id_orden_c FROM orden_servicio_t');
        $nOrd = 0;
        foreach ($ordenes as $orden) {
            $idOrden = (int) ($orden->id_orden_c ?? 0);
            if ($idOrden <= 0) {
                continue;
            }
            $tRow = DB::selectOne('SELECT id_trabajo FROM orden_servicio_t WHERE id_orden_c = ? LIMIT 1', [$idOrden]);
            $idTrabajo = (int) ($tRow->id_trabajo ?? 0);
            if ($idTrabajo <= 0) {
                continue;
            }

            $subT = (float) (DB::scalar('SELECT COALESCE(SUM(importe),0) FROM trabajos_orden WHERE id_trabajo = ?', [$idTrabajo]) ?: 0);
            $matsRaw = DB::select('SELECT vale, codigo, descripcion, cantidad, precio_unitario, importe, anticipo, ticket FROM materiales_orden WHERE id_trabajo = ?', [$idTrabajo]);
            $subM = 0.0;
            foreach ($matsRaw as $m) {
                $arr = (array) $m;
                if (MaterialesOrdenClassifier::classify($arr) === 'material') {
                    $subM += (float) ($arr['cantidad'] ?? 0) * (float) ($arr['precio_unitario'] ?? 0);
                }
            }
            $subM = round($subM, 2);
            $subNeto = round($subT + $subM, 2);
            $iva = round($subNeto * 0.16, 2);
            $total = round($subNeto + $iva, 2);

            $nOrd++;
            if (! $dry) {
                DB::update(
                    'UPDATE orden_servicio_t SET subtotal_t = ?, subtotal_m = ?, iva = ?, total_pagar = ? WHERE id_orden_c = ?',
                    [$subT, $subM, $iva, $total, $idOrden]
                );
            }
        }
        $this->info("órdenes recalculadas: {$nOrd}".($dry ? ' (dry-run)' : ''));

        if ($dry) {
            $this->warn('Dry-run: no se escribió nada. Ejecuta sin --dry-run para aplicar.');
        } else {
            file_put_contents($marker, now()->toIso8601String()."\n");
            $this->info('Migración a precios SIN IVA completada.');
            $this->line('Marcador: storage/app/anlux_precios_sin_iva.migrated');
            $this->line('Limpia cache PDF si hace falta: storage/app/pdf_cache');
        }

        return self::SUCCESS;
    }
}

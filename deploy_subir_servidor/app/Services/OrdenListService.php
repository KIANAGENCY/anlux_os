<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\AnluxAuthContext;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class OrdenListService
{
    public function __construct(
        private readonly AnluxVaultService $vault,
        private readonly OrdenPolicyService $policy
    ) {}

    public function ensureSearchTable(): void
    {
        if (! Schema::hasTable('orden_servicio_nombre_busqueda')) {
            throw new RuntimeException('Falta la tabla orden_servicio_nombre_busqueda. Ejecuta las migraciones pendientes.');
        }
    }

    private function revealTecnicoNombreListado(?string $stored): string
    {
        $raw = trim((string) $stored);
        if ($raw === '') {
            return '';
        }

        $revealed = $this->vault->tecnicoNombreReveal($raw);
        if ($revealed !== '') {
            return $revealed;
        }

        return str_starts_with($raw, 'v1:') ? '' : $raw;
    }

    private function tecnicoRecibidoParaListado(?string $cabeceraRaw, ?string $detalleRaw): string
    {
        $cabecera = $this->revealTecnicoNombreListado($cabeceraRaw);
        if ($cabecera !== '') {
            return $cabecera;
        }

        if (trim((string) $cabeceraRaw) === '') {
            return $this->revealTecnicoNombreListado($detalleRaw);
        }

        return $cabecera;
    }

    /**
     * Secuencia numerada para columna Involucrados:
     * técnico de recepción + log cronológico + quien entregó.
     * Formato multilínea: "1. Nombre\n2. Nombre"
     */
    private function involucradosDisplayParaListado(string $tecnicoRecibido, string $tecnicosLog, string $entregadoPor): string
    {
        $secuencia = [];
        $push = static function (string $nombre) use (&$secuencia): void {
            $nombre = trim($nombre);
            if ($nombre === '') {
                return;
            }
            foreach ($secuencia as $ya) {
                if (strcasecmp($ya, $nombre) === 0) {
                    return;
                }
            }
            $secuencia[] = $nombre;
        };

        $push($tecnicoRecibido);
        foreach (explode(' · ', $tecnicosLog) as $parte) {
            $push($parte);
        }
        $push($entregadoPor);

        if ($secuencia === []) {
            return '';
        }

        $numerados = [];
        foreach ($secuencia as $idx => $nombre) {
            $numerados[] = ($idx + 1).'. '.$nombre;
        }

        return implode("\n", $numerados);
    }

    /**
     * @param  string  $sort  'fecha' (default) or 'estatus' (flow order, then fecha_entrada desc).
     * @return array{success: bool, data?: array<int, object|array>, pagination?: array<string, mixed>, message?: string}
     */
    public function listForRequest(User $user, string $search, string $startDate, string $endDate, string $estatus, int $page, int $perPage, string $sort = 'fecha'): array
    {
        $this->ensureSearchTable();

        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $lockJoin = '';
        $lockSelect = 'NULL AS edit_lock_user_id, NULL AS edit_lock_nombre';
        if (Schema::hasTable('orden_servicio_edit_locks')) {
            $lockSelect = 'lk.locked_by_user_id AS edit_lock_user_id, lk.locked_by_nombre AS edit_lock_nombre';
            $lockJoin = ' LEFT JOIN orden_servicio_edit_locks lk ON lk.id_orden_c = c.id_orden_c AND lk.expires_at >= NOW()';
        }

        $salidaSelect = '0 AS salida_temporal_activa, NULL AS fecha_salida_temporal';
        try {
            if (Schema::hasColumn('orden_servicio_c', 'salida_temporal_activa')) {
                $salidaSelect = 'COALESCE(c.salida_temporal_activa, 0) AS salida_temporal_activa, c.fecha_salida_temporal';
            }
        } catch (\Throwable) {
            // keep defaults
        }

        $sql = "SELECT c.id_orden_c, c.folio, c.nombre_cliente, c.direccion, c.telefono, c.correo, c.poblacion, c.fecha_entrada, c.fecha_terminada, c.fecha_salida, c.estatus, c.tecnico_recibido, t.tecnico_recibido AS tecnico_recibido_t, t.entregado_por_tecnico, t.total_pagar,
                (CASE WHEN TRIM(COALESCE(c.firma_c_e, '')) <> '' AND TRIM(COALESCE(c.firma_t_r, '')) <> '' THEN 1 ELSE 0 END) AS firmas_recepcion_ok,
                (CASE WHEN TRIM(COALESCE(t.firma_c_r, '')) <> '' AND TRIM(COALESCE(t.firma_t_e, '')) <> '' THEN 1 ELSE 0 END) AS firmas_entrega_ok,
                {$salidaSelect},
                tl.tecnicos_log,
                {$lockSelect}
                FROM orden_servicio_c c
                LEFT JOIN (
                    SELECT t1.id_orden_c, t1.tecnico_recibido, t1.entregado_por_tecnico, t1.total_pagar, t1.firma_c_r, t1.firma_t_e
                    FROM orden_servicio_t t1
                    INNER JOIN (
                        SELECT id_orden_c, MIN(id_trabajo) AS id_trabajo
                        FROM orden_servicio_t
                        GROUP BY id_orden_c
                    ) tx ON tx.id_orden_c = t1.id_orden_c AND tx.id_trabajo = t1.id_trabajo
                ) t ON t.id_orden_c = c.id_orden_c
                LEFT JOIN (
                    SELECT id_orden_c, GROUP_CONCAT(nombre_tecnico ORDER BY fecha ASC SEPARATOR ' · ') AS tecnicos_log
                    FROM orden_servicio_tecnico_log
                    GROUP BY id_orden_c
                ) tl ON tl.id_orden_c = c.id_orden_c
                {$lockJoin}
                WHERE 1";
        $countSql = 'SELECT COUNT(*) FROM orden_servicio_c c WHERE 1';
        $params = [];

        $sw = $this->vault->buildSearchWhere($search);
        $sql .= $sw['sql'];
        $countSql .= $sw['sql'];
        foreach ($sw['params'] as $p) {
            $params[] = $p;
        }

        if ($startDate !== '') {
            $sql .= ' AND c.fecha_entrada >= ?';
            $countSql .= ' AND c.fecha_entrada >= ?';
            $params[] = $startDate.' 00:00:00';
        }
        if ($endDate !== '') {
            $sql .= ' AND c.fecha_entrada <= ?';
            $countSql .= ' AND c.fecha_entrada <= ?';
            $params[] = $endDate.' 23:59:59';
        }
        if ($estatus !== '' && $estatus !== 'todos') {
            $mapped = OrderStatus::map($estatus);
            if (in_array($mapped, ['Recepción', 'En proceso', 'Terminado', 'Entregado'], true)) {
                $sql .= ' AND c.estatus = ?';
                $countSql .= ' AND c.estatus = ?';
                $params[] = $mapped;
            } else {
                $sql .= ' AND LOWER(c.estatus) LIKE ?';
                $countSql .= ' AND LOWER(c.estatus) LIKE ?';
                $params[] = '%'.strtolower($estatus).'%';
            }
        }

        $policy = $this->policy->listRestrictionSql($user);
        $sql .= $policy['sql'];
        $countSql .= $policy['sql'];
        foreach ($policy['params'] as $pp) {
            $params[] = $pp;
        }

        $countRow = DB::selectOne($countSql, $params);
        $total = (int) (array_values((array) $countRow)[0] ?? 0);

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $orderBy = 'c.fecha_entrada DESC';
        if ($sort === 'estatus') {
            $orderBy = "CASE c.estatus
                WHEN 'Recepción' THEN 1
                WHEN 'En proceso' THEN 2
                WHEN 'Terminado' THEN 3
                WHEN 'Entregado' THEN 4
                ELSE 9
            END ASC, c.fecha_entrada DESC";
        }
        $sql .= ' ORDER BY '.$orderBy.' LIMIT '.(int) $perPage.' OFFSET '.(int) $offset;
        $rows = DB::select($sql, $params);

        $lockUserId = AnluxAuthContext::editLockUserId();

        foreach ($rows as &$rowOrden) {
            $r = (array) $rowOrden;
            $lockHolderId = (int) ($r['edit_lock_user_id'] ?? 0);
            $r['edit_lock_active'] = $lockHolderId > 0;
            $r['edit_lock_is_mine'] = $lockHolderId > 0 && $lockHolderId === $lockUserId;
            $r['edit_lock_nombre'] = trim((string) ($r['edit_lock_nombre'] ?? ''));
            if (isset($r['telefono'])) {
                $r['telefono'] = $this->vault->telefonoReveal($r['telefono']);
            }
            if (isset($r['direccion'])) {
                $r['direccion'] = $this->vault->direccionReveal($r['direccion']);
            }
            if (isset($r['correo'])) {
                $r['correo'] = $this->vault->correoReveal($r['correo']);
            }
            if (isset($r['poblacion'])) {
                $r['poblacion'] = $this->vault->poblacionReveal($r['poblacion']);
            }
            if (isset($r['nombre_cliente'])) {
                $r['nombre_cliente'] = $this->vault->nombreClienteReveal($r['nombre_cliente']);
            }
            $cabeceraTecnicoRaw = $r['tecnico_recibido'] ?? null;
            $detalleTecnicoRaw = $r['tecnico_recibido_t'] ?? null;
            $r['tecnico_recibido'] = $this->tecnicoRecibidoParaListado($cabeceraTecnicoRaw, $detalleTecnicoRaw);
            foreach (['tecnico_recibido_t', 'entregado_por_tecnico', 'edit_lock_nombre'] as $tecnicoField) {
                if (isset($r[$tecnicoField])) {
                    $r[$tecnicoField] = $this->revealTecnicoNombreListado($r[$tecnicoField]);
                }
            }
            if (isset($r['tecnicos_log'])) {
                $r['tecnicos_log'] = $this->vault->tecnicosLogConcatReveal($r['tecnicos_log']);
            }
            $r['involucrados_display'] = $this->involucradosDisplayParaListado(
                (string) ($r['tecnico_recibido'] ?? ''),
                (string) ($r['tecnicos_log'] ?? ''),
                (string) ($r['entregado_por_tecnico'] ?? '')
            );
            try {
                $r['estatus_label'] = OrderStatus::displayLabel((string) ($r['estatus'] ?? ''));
            } catch (\Throwable $e) {
                report($e);
                $r['estatus_label'] = trim((string) ($r['estatus'] ?? ''));
            }
            $rowOrden = (object) $r;
        }
        unset($rowOrden);

        return [
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }
}

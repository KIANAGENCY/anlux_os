<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EquipoEntregaResolver
{
    public function __construct(
        private readonly AnluxVaultService $vault
    ) {}

    /**
     * @param  array<int, object|array<string, mixed>>  $equipos
     * @param  array<string, mixed>  $orden
     * @return array<int, array<string, mixed>>
     */
    public function resolveAll(int $idOrden, array $equipos, array $orden): array
    {
        $eventos = $this->eventosPorIndice($idOrden);
        $titular = $this->vault->nombreClienteReveal($orden['nombre_cliente'] ?? null);
        $receptorGlobal = $this->vault->nombreClienteReveal($orden['recibido_cliente'] ?? null);
        $tecnicoGlobal = $this->vault->tecnicoNombreReveal($orden['entregado_por_tecnico'] ?? null);
        $resultado = [];

        foreach (array_values($equipos) as $idx => $equipoRaw) {
            $equipo = (array) $equipoRaw;
            $indice = $idx + 1;
            $evento = $eventos[$indice] ?? [];
            $esEntregado = (int) ($equipo['acciones'] ?? 0) === 2;

            $receptor = $this->vault->nombreClienteReveal($equipo['entrega_recibido_cliente'] ?? null);
            if ($receptor === '' && $esEntregado) {
                $receptor = $this->vault->nombreClienteReveal($evento['receptor'] ?? null);
            }
            if ($receptor === '' && $esEntregado) {
                $receptor = $receptorGlobal !== '' ? $receptorGlobal : $titular;
            }

            $tipo = mb_strtolower(trim((string) ($equipo['entrega_receptor_tipo'] ?? '')), 'UTF-8');
            if (! in_array($tipo, ['cliente', 'tercero'], true)) {
                $tipoEvento = mb_strtolower(trim((string) ($evento['tipo'] ?? '')), 'UTF-8');
                if (in_array($tipoEvento, ['cliente', 'tercero'], true)) {
                    $tipo = $tipoEvento;
                } else {
                    $tipo = $this->normalizarNombre($receptor) !== ''
                        && $this->normalizarNombre($receptor) !== $this->normalizarNombre($titular)
                            ? 'tercero'
                            : 'cliente';
                }
            }

            $tecnico = $this->vault->tecnicoNombreReveal($equipo['entrega_tecnico'] ?? null);
            if ($tecnico === '' && $esEntregado) {
                $tecnico = $tecnicoGlobal;
            }

            $resultado[$indice] = [
                'indice' => $indice,
                'equipo' => (object) $equipo,
                'acciones' => (int) ($equipo['acciones'] ?? 0),
                'receptor' => trim($receptor),
                'receptor_tipo' => $tipo,
                'fecha_entrega' => $esEntregado
                    ? ($equipo['entrega_fecha'] ?? $evento['fecha'] ?? $orden['fecha_salida'] ?? null)
                    : ($equipo['entrega_fecha'] ?? null),
                'tecnico' => trim($tecnico),
                // Solo la firma guardada en equipos_orden; no reutilizar firma_c_r/firma_t_e globales.
                'firma_cliente' => $equipo['entrega_firma_cliente'] ?? null,
                'firma_tecnico' => $equipo['entrega_firma_tecnico'] ?? null,
            ];
        }

        return $resultado;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entregas
     */
    public function resumenReceptores(array $entregas): string
    {
        $unicos = [];
        foreach ($entregas as $entrega) {
            $nombre = trim((string) ($entrega['receptor'] ?? ''));
            if ($nombre !== '') {
                $unicos[$this->normalizarNombre($nombre)] = $nombre;
            }
        }

        return count($unicos) > 1 ? 'VARIOS RECEPTORES' : (array_values($unicos)[0] ?? '');
    }

    /** @return array<int, array{receptor: string, tipo: string, fecha: mixed}> */
    private function eventosPorIndice(int $idOrden): array
    {
        if ($idOrden <= 0 || ! Schema::hasTable('order_whatsapp_notifications')) {
            return [];
        }

        $rows = DB::select(
            'SELECT payload_json, COALESCE(queued_at, created_at, sent_at, delivered_at) AS fecha_evento
             FROM order_whatsapp_notifications
             WHERE id_orden_c = ? AND estatus = ?
             ORDER BY id ASC',
            [$idOrden, 'Entregado']
        );
        $eventos = [];
        foreach ($rows as $row) {
            $payload = json_decode((string) ($row->payload_json ?? ''), true);
            $indice = is_array($payload) ? (int) ($payload['equipo_indice'] ?? 0) : 0;
            if ($indice < 1 || isset($eventos[$indice])) {
                continue;
            }
            $eventos[$indice] = [
                'receptor' => trim((string) ($payload['recibido_cliente'] ?? '')),
                'tipo' => trim((string) ($payload['entrega_quien_recibe'] ?? '')),
                'fecha' => $row->fecha_evento ?? null,
            ];
        }

        return $eventos;
    }

    private function normalizarNombre(string $nombre): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $nombre)), 'UTF-8');
    }
}

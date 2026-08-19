<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Clasifica filas de materiales_orden: material real, anticipo o saldo liquidado.
 */
final class MaterialesOrdenClassifier
{
    /**
     * @param  array<string, mixed>  $row
     * @return 'abono'|'anticipo'|'material'
     */
    public static function classify(array $row): string
    {
        $descripcion = trim((string) ($row['descripcion'] ?? ''));
        $codigo = trim((string) ($row['codigo'] ?? ''));
        $ticket = trim((string) ($row['ticket'] ?? ''));
        $anticipo = (float) ($row['anticipo'] ?? 0);
        $cantCero = (float) ($row['cantidad'] ?? 0) == 0.0;
        $precioCero = (float) ($row['precio_unitario'] ?? 0) == 0.0;
        $importeCero = (float) ($row['importe'] ?? 0) == 0.0;

        $ticketUp = mb_strtoupper($ticket, 'UTF-8');
        $descUp = mb_strtoupper($descripcion, 'UTF-8');
        $codigoUp = mb_strtoupper($codigo, 'UTF-8');

        // "SALDO LIQUIDADO" (actual) y "ABONO SALDO" (legado) identifican el pago al liquidar saldo.
        if (
            in_array($descUp, ['SALDO LIQUIDADO', 'ABONO SALDO'], true)
            || in_array($ticketUp, ['SALDO LIQUIDADO', 'ABONO SALDO', 'ABONO SALDO PENDIENTE', 'PAGO SALDO PENDIENTE'], true)
        ) {
            return 'abono';
        }

        $esMarcador = $codigoUp === 'ANTICIPO' || $descUp === 'ANTICIPO';
        if ($esMarcador && $cantCero && $precioCero && $importeCero) {
            return 'anticipo';
        }

        if ($anticipo > 0.009 && $cantCero && $precioCero && $importeCero) {
            return 'anticipo';
        }

        return 'material';
    }

    /**
     * Normaliza folio/descripcion de anticipos legados (solo monto+ticket).
     *
     * @param  array<string, mixed>  $row
     * @return array{folio: string, descripcion: string, monto: float, ticket: string, id_equipo: int|null}
     */
    public static function toAnticipoPayload(array $row): array
    {
        $folio = trim((string) ($row['vale'] ?? ''));
        $descripcion = trim((string) ($row['descripcion'] ?? ''));
        if (mb_strtoupper($descripcion, 'UTF-8') === 'ANTICIPO') {
            $descripcion = '';
        }
        if ($folio === '') {
            $folio = 'SIN FOLIO';
        }
        if ($descripcion === '') {
            $descripcion = 'ANTICIPO';
        }

        $idEquipo = $row['id_equipo'] ?? null;
        if ($idEquipo !== null && $idEquipo !== '') {
            $idEquipoInt = (int) $idEquipo;
            $idEquipo = $idEquipoInt > 0 ? $idEquipoInt : null;
        } else {
            $idEquipo = null;
        }

        return [
            'folio' => $folio,
            'descripcion' => $descripcion,
            'monto' => (float) ($row['anticipo'] ?? 0),
            'ticket' => (string) ($row['ticket'] ?? ''),
            'id_equipo' => $idEquipo,
        ];
    }
}

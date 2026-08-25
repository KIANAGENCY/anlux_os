<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\ExactoAuthContext;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class OrdenStatusService
{
    public function __construct(
        private readonly ExactoVaultService $vault,
        private readonly OrdenPolicyService $policy,
        private readonly OrdenAuditService $audit,
        private readonly OrderEmailService $orderEmail,
        private readonly OrderWhatsappService $orderWhatsapp
    ) {}

    private function firmaGuardadaTieneTrazos(?string $firma): bool
    {
        $firma = trim((string) $firma);
        if ($firma === '') {
            return false;
        }

        $absolutePath = $this->firmaPathToAbsolute($firma);
        if ($absolutePath === null) {
            return true;
        }

        $binary = @file_get_contents($absolutePath);
        if ($binary === false || $binary === '') {
            return false;
        }

        return $this->firmaImagenTieneTrazos($binary);
    }

    private function firmaPathToAbsolute(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || str_starts_with($path, 'data:image')) {
            return null;
        }

        $normalized = str_replace('\\', '/', $path);
        if (preg_match('#^https?://[^/]+/(.*)$#i', $normalized, $matches)) {
            $normalized = $matches[1];
        }
        $normalized = ltrim($normalized, '/');

        $absolutePath = public_path(str_replace('/', DIRECTORY_SEPARATOR, $normalized));
        $real = realpath($absolutePath);
        if ($real !== false && is_file($real)) {
            return $real;
        }

        return is_file($absolutePath) ? $absolutePath : null;
    }

    private function firmaImagenTieneTrazos(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }
        if (! function_exists('imagecreatefromstring')) {
            return true;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return true;
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);
            if ($width < 8 || $height < 8) {
                return false;
            }
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $rgba = imagecolorat($image, $x, $y);
                    $alpha = ($rgba >> 24) & 0x7F;
                    $red = ($rgba >> 16) & 0xFF;
                    $green = ($rgba >> 8) & 0xFF;
                    $blue = $rgba & 0xFF;

                    if ($alpha < 120 && ($red < 245 || $green < 245 || $blue < 245)) {
                        return true;
                    }
                }
            }
        } finally {
            imagedestroy($image);
        }

        return false;
    }

    public function tieneFirmasEntrega(int $idOrdenC): bool
    {
        $row = DB::selectOne(
            'SELECT TRIM(COALESCE(firma_c_r, \'\')) AS fc, TRIM(COALESCE(firma_t_e, \'\')) AS ft
             FROM orden_servicio_t WHERE id_orden_c = ? ORDER BY id_trabajo ASC LIMIT 1',
            [$idOrdenC]
        );
        if (! $row) {
            return false;
        }
        $r = (array) $row;

        return $this->firmaGuardadaTieneTrazos($this->vault->firmaRutaReveal($r['fc'] ?? ''))
            && $this->firmaGuardadaTieneTrazos($this->vault->firmaRutaReveal($r['ft'] ?? ''));
    }

    public function tieneFirmasRecepcionEquipo(int $idOrdenC): bool
    {
        $row = DB::selectOne(
            'SELECT TRIM(COALESCE(firma_c_e, \'\')) AS fc, TRIM(COALESCE(firma_t_r, \'\')) AS ft
             FROM orden_servicio_c WHERE id_orden_c = ?',
            [$idOrdenC]
        );
        if (! $row) {
            return false;
        }
        $r = (array) $row;

        return $this->firmaGuardadaTieneTrazos($this->vault->firmaRutaReveal($r['fc'] ?? ''))
            && $this->firmaGuardadaTieneTrazos($this->vault->firmaRutaReveal($r['ft'] ?? ''));
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function updateStatus(User $user, int $id, string $estatusInput): array
    {
        if ($id <= 0 || trim($estatusInput) === '') {
            return ['success' => false, 'message' => 'Parámetros inválidos'];
        }
        if (! $this->policy->userCanAccessOrder($user, $id)) {
            return ['success' => false, 'message' => 'No autorizado para modificar esta orden.'];
        }

        $nuevoEstatus = OrderStatus::map($estatusInput);
        $now = now()->format('Y-m-d H:i:s');
        $nombreRecepcion = ExactoAuthContext::nombreTecnicoParaRegistro($user);
        $nombreInvolucrado = ExactoAuthContext::nombreTecnicoSesionActual($user);

        $filaActual = DB::selectOne('SELECT folio, estatus FROM orden_servicio_c WHERE id_orden_c = ?', [$id]);
        if (! $filaActual) {
            return ['success' => false, 'message' => 'Orden no encontrada.'];
        }
        $estatusActual = (string) ($filaActual->estatus ?? '');
        if (OrderStatus::isEntregado($estatusActual) && $nuevoEstatus !== 'Entregado') {
            return ['success' => false, 'message' => 'Esta orden ya está en estatus Entregado. No se puede cambiar a Recepción, En proceso ni Terminado.'];
        }
        if ($nuevoEstatus === 'Entregado' && ! $this->tieneFirmasEntrega($id)) {
            return ['success' => false, 'message' => 'No se puede marcar como Entregado: deben estar registradas las firmas de Cliente y Técnico. Complétalas en la orden de servicio antes de cambiar el estatus.'];
        }
        if (
            in_array($nuevoEstatus, ['Terminado', 'Entregado'], true)
            && $this->tieneSalidaTemporalActiva($id)
        ) {
            return [
                'success' => false,
                'message' => 'Esta orden tiene una salida temporal activa. Registra el regreso del equipo al taller antes de pasarla a Terminado o Entregado.',
            ];
        }

        $estatusActualCanon = OrderStatus::map($estatusActual);
        $cambiaEstatus = ($estatusActualCanon !== $nuevoEstatus);
        if (
            $cambiaEstatus
            && in_array($nuevoEstatus, ['En proceso', 'Terminado'], true)
            && ! $this->tieneFirmasRecepcionEquipo($id)
        ) {
            return ['success' => false, 'message' => 'No se puede poner en En proceso ni en Terminado sin las firmas de Cliente y Técnico. Abre la orden de servicio, completa esa sección y guarda antes de cambiar el estatus aquí.'];
        }

        DB::beginTransaction();
        try {
            $sql = 'UPDATE orden_servicio_c SET estatus = ?';
            $params = [$nuevoEstatus];
            if ($nuevoEstatus === 'En proceso') {
                $sql .= ', tecnico_recibido = IFNULL(NULLIF(TRIM(tecnico_recibido), \'\'), ?)';
                $params[] = $nombreRecepcion;
            }
            if ($nuevoEstatus === 'Terminado') {
                // Confirmar Terminado vuelve a fechar la orden, incluso si ya estaba terminada.
                $sql .= ' , fecha_terminada = ?';
                $params[] = $now;
            }
            if ($nuevoEstatus === 'Entregado') {
                $sql .= ' , fecha_salida = IFNULL(fecha_salida, ?)';
                $params[] = $now;
            }
            $sql .= ' WHERE id_orden_c = ?';
            $params[] = $id;
            DB::update($sql, $params);

            if ($nuevoEstatus === 'En proceso') {
                DB::update(
                    'UPDATE orden_servicio_t SET tecnico_recibido = IFNULL(NULLIF(TRIM(tecnico_recibido), \'\'), ?) WHERE id_orden_c = ?',
                    [$nombreRecepcion, $id]
                );
            }
            if ($nuevoEstatus === 'Entregado') {
                DB::update(
                    'UPDATE orden_servicio_t SET entregado_por_tecnico = ? WHERE id_orden_c = ?',
                    [$nombreInvolucrado, $id]
                );
            }

            if ($cambiaEstatus && trim($nombreInvolucrado) !== '') {
                $ultimo = DB::scalar(
                    'SELECT nombre_tecnico FROM orden_servicio_tecnico_log WHERE id_orden_c = ? ORDER BY fecha DESC LIMIT 1',
                    [$id]
                );
                if ($ultimo === null || strcasecmp(trim((string) $ultimo), trim($nombreInvolucrado)) !== 0) {
                    DB::insert(
                        'INSERT INTO orden_servicio_tecnico_log (id_orden_c, estatus, nombre_tecnico, fecha) VALUES (?, ?, ?, ?)',
                        [$id, $nuevoEstatus, $nombreInvolucrado, $now]
                    );
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        }

        $this->audit->log($id, (string) ($filaActual->folio ?? null), 'estatus_actualizado', [
            'estatus_anterior' => $estatusActualCanon,
            'estatus_nuevo' => $nuevoEstatus,
            'tecnico' => $nombreInvolucrado,
        ]);

        // Terminado es solo uso interno: no envía WhatsApp/correo. Solo Recepción y Entregado.
        if ($cambiaEstatus && in_array($nuevoEstatus, ['Recepción', 'Entregado'], true)) {
            $this->orderEmail->sendForStatus($id, $nuevoEstatus);
            if (config('exacto.whatsapp_notifications_enabled', false)
                && filter_var(config('services.whatsapp.enabled', false), FILTER_VALIDATE_BOOL)) {
                $this->orderWhatsapp->queueForStatusWithResult(
                    $id,
                    $nuevoEstatus,
                    null,
                    true,
                    false
                );
            }
        }

        return ['success' => true, 'message' => 'Estatus actualizado correctamente'];
    }

    private function tieneSalidaTemporalActiva(int $idOrdenC): bool
    {
        if ($idOrdenC <= 0) {
            return false;
        }
        try {
            if (! Schema::hasColumn('orden_servicio_c', 'salida_temporal_activa')) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        return (int) (DB::scalar(
            'SELECT salida_temporal_activa FROM orden_servicio_c WHERE id_orden_c = ?',
            [$idOrdenC]
        ) ?? 0) === 1;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\OrderPdfController;
use App\Jobs\SendOrderStatusEmailJob;
use App\Mail\OrderStatusMail;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class OrderEmailService
{
    public function __construct(
        private readonly ExactoVaultService $vault,
        private readonly EmailSmtpProbeService $smtpProbe
    ) {}

    public function sendForStatus(int $idOrdenC, string $estatus): void
    {
        $estatusCanon = OrderStatus::map($estatus);
        if (! in_array($estatusCanon, ['Recepción', 'Terminado', 'Entregado'], true)) {
            return;
        }

        SendOrderStatusEmailJob::dispatch($idOrdenC, $estatus)->onConnection('database');
    }

    /**
     * @param  array{status: string, email: string, message: string}|null  $probeResult
     * @param  array<string, mixed>|null  $orderPayload
     * @return array{sent: bool, status: 'skipped'|'queued'|'queued_confirmed'|'rejected', message: string, email: string, probe: array{status: string, email: string, message: string}|null}
     */
    public function queueForStatusWithResult(int $idOrdenC, string $estatus, ?array $probeResult = null, ?array $orderPayload = null): array
    {
        $estatusCanon = OrderStatus::map($estatus);
        if (! in_array($estatusCanon, ['Recepción', 'Terminado', 'Entregado'], true)) {
            return [
                'sent' => false,
                'status' => 'skipped',
                'message' => 'Este estatus no envía correo automático.',
                'email' => '',
                'probe' => $probeResult,
            ];
        }

        $orden = is_array($orderPayload) && $orderPayload !== []
            ? $orderPayload
            : $this->buildOrderPayload($idOrdenC, $estatusCanon);
        $correo = $this->normalizeCorreo(trim((string) ($orden['correo'] ?? '')));
        if ($correo === '') {
            return [
                'sent' => false,
                'status' => 'skipped',
                'message' => '',
                'email' => '',
                'probe' => $probeResult,
            ];
        }

        if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return [
                'sent' => false,
                'status' => 'rejected',
                'message' => 'Correo no enviado. El correo registrado no tiene un formato válido.',
                'email' => $correo,
                'probe' => $probeResult,
            ];
        }

        $probeResult ??= $this->probeRecipient($correo);
        if (in_array($probeResult['status'] ?? '', ['invalid', 'rejected'], true)) {
            return [
                'sent' => false,
                'status' => 'rejected',
                'message' => 'Correo no enviado. El servidor destino rechazó la dirección de correo.',
                'email' => $correo,
                'probe' => $probeResult,
            ];
        }

        try {
            SendOrderStatusEmailJob::dispatch($idOrdenC, $estatusCanon, $probeResult, $orden)
                ->onConnection('database');
        } catch (\Throwable $e) {
            Log::channel('exacto_ops')->warning('order_email_dispatch_failed', [
                'id_orden_c' => $idOrdenC,
                'estatus' => $estatusCanon,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'status' => 'rejected',
                'message' => 'Correo no encolado. Revisa la tabla jobs o el cron del servidor.',
                'email' => $correo,
                'probe' => $probeResult,
            ];
        }

        if (($probeResult['status'] ?? '') === 'verified') {
            return [
                'sent' => false,
                'status' => 'queued_confirmed',
                'message' => 'Correo en cola para '.$correo.'. El buzón fue confirmado antes del envío.',
                'email' => $correo,
                'probe' => $probeResult,
            ];
        }

        return [
            'sent' => false,
            'status' => 'queued',
            'message' => 'Envío programado.',
            'email' => $correo,
            'probe' => $probeResult,
        ];
    }

    /**
     * @return array{status: string, email: string, message: string}
     */
    public function probeRecipient(?string $email): array
    {
        return $this->smtpProbe->probe($this->normalizeCorreo($email ?? ''));
    }

    /**
     * @param  array{status: string, email: string, message: string}|null  $probeResult
     * @param  array<string, mixed>|null  $orderPayload
     * @return array{sent: bool, status: 'skipped'|'confirmed'|'accepted'|'rejected'|'failed', message: string, email: string, probe: array{status: string, email: string, message: string}|null}
     */
    public function sendForStatusWithResult(int $idOrdenC, string $estatus, ?array $probeResult = null, ?array $orderPayload = null): array
    {
        $estatusCanon = OrderStatus::map($estatus);
        if (! in_array($estatusCanon, ['Recepción', 'Terminado', 'Entregado'], true)) {
            return [
                'sent' => false,
                'status' => 'skipped',
                'message' => 'Este estatus no envía correo automático.',
                'email' => '',
                'probe' => $probeResult,
            ];
        }

        try {
            $orden = is_array($orderPayload) && $orderPayload !== []
                ? $orderPayload
                : $this->buildOrderPayload($idOrdenC, $estatusCanon);
            $equipoIndice = (int) ($orden['equipo_indice'] ?? 0);
            if ($equipoIndice > 0 && isset($orden['equipos']) && is_array($orden['equipos']) && count($orden['equipos']) > 1) {
                $idx = $equipoIndice - 1;
                if (isset($orden['equipos'][$idx]) && is_array($orden['equipos'][$idx])) {
                    $orden['equipos'] = [$orden['equipos'][$idx]];
                } else {
                    $orden['equipos'] = array_values(array_slice($orden['equipos'], 0, 1));
                }
            }
            $correo = $this->normalizeCorreo(trim((string) ($orden['correo'] ?? '')));
            if ($correo === '') {
                return [
                    'sent' => false,
                    'status' => 'skipped',
                    'message' => '',
                    'email' => '',
                    'probe' => $probeResult,
                ];
            }

            if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                return [
                    'sent' => false,
                    'status' => 'rejected',
                    'message' => 'Correo no enviado. El correo registrado no tiene un formato válido.',
                    'email' => $correo,
                    'probe' => $probeResult,
                ];
            }
            $probeResult ??= $this->probeRecipient($correo);
            if (in_array($probeResult['status'] ?? '', ['invalid', 'rejected'], true)) {
                return [
                    'sent' => false,
                    'status' => 'rejected',
                    'message' => 'Correo no enviado. El servidor destino rechazó la dirección de correo.',
                    'email' => $correo,
                    'probe' => $probeResult,
                ];
            }

            [$pdfContent, $pdfFilename] = $this->pdfAttachment(
                $idOrdenC,
                (string) ($orden['folio'] ?? ''),
                (int) ($orden['equipo_indice'] ?? 0)
            );

            Mail::to($correo)->send(new OrderStatusMail($orden, $estatusCanon, $pdfContent, $pdfFilename));

            $message = 'Correo enviado a '.$correo.'.';
            $status = 'accepted';
            if (($probeResult['status'] ?? '') === 'unverifiable') {
                $message = 'Correo enviado a '.$correo.'. Aceptado para envío, sin confirmación previa del buzón.';
            } elseif (($probeResult['status'] ?? '') === 'verified') {
                $status = 'confirmed';
                $message = 'Correo confirmado y enviado a '.$correo.'. El servidor destino confirmó la dirección de correo.';
            }

            return [
                'sent' => true,
                'status' => $status,
                'message' => $message,
                'email' => $correo,
                'probe' => $probeResult,
            ];
        } catch (\Throwable $e) {
            Log::channel('exacto_ops')->warning('order_email_failed', [
                'id_orden_c' => $idOrdenC,
                'estatus' => $estatusCanon,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'status' => 'failed',
                'message' => 'Correo no enviado. Ocurrió un error al intentar enviar la notificación.',
                'email' => $probeResult['email'] ?? '',
                'probe' => $probeResult,
            ];
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function pdfAttachment(int $idOrdenC, string $folio, int $equipoIndice = 0): array
    {
        try {
            $content = app(OrderPdfController::class)->renderOrderPdfBinary(
                $idOrdenC,
                true,
                $equipoIndice > 0 ? $equipoIndice : null
            );
            if ($content === '') {
                return [null, null];
            }

            $safeFolio = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($folio));
            $filename = ($safeFolio !== '' ? $safeFolio : 'orden_'.$idOrdenC);
            if ($equipoIndice > 0) {
                $filename .= '_eq'.$equipoIndice;
            }
            $filename .= '.pdf';

            return [$content, $filename];
        } catch (\Throwable $e) {
            Log::channel('exacto_ops')->warning('order_email_pdf_failed', [
                'id_orden_c' => $idOrdenC,
                'equipo_indice' => $equipoIndice,
                'error' => $e->getMessage(),
            ]);

            return [null, null];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOrderPayload(int $idOrdenC, string $estatus): array
    {
        $cabecera = DB::selectOne('SELECT * FROM orden_servicio_c WHERE id_orden_c = ?', [$idOrdenC]);
        if (! $cabecera) {
            throw new \RuntimeException('Orden no encontrada para envío de correo.');
        }

        $cab = (array) $cabecera;
        $trabajo = DB::selectOne(
            'SELECT total_pagar FROM orden_servicio_t WHERE id_orden_c = ? ORDER BY id_trabajo ASC LIMIT 1',
            [$idOrdenC]
        );

        $equipos = [];
        $equiposDb = DB::select(
            'SELECT marca, modelo, serie, clave, tipo_servicio, descripcion_falla FROM equipos_orden WHERE id_orden_c = ? ORDER BY id_equipo ASC',
            [$idOrdenC]
        );
        foreach ($equiposDb as $equipo) {
            $equipos[] = [
                'marca' => $this->repairVisibleText($equipo->marca ?? null),
                'modelo' => $this->repairVisibleText($equipo->modelo ?? null),
                'serie' => trim((string) ($equipo->serie ?? '')),
                'clave' => trim((string) ($equipo->clave ?? '')),
                'tipo_servicio' => $this->repairVisibleText($equipo->tipo_servicio ?? null),
                'descripcion_falla' => $this->repairVisibleText($equipo->descripcion_falla ?? null),
            ];
        }

        return [
            'id_orden_c' => $idOrdenC,
            'folio' => trim((string) ($cab['folio'] ?? '')),
            'estatus' => $estatus,
            'saludo' => 'Hola, qué tal',
            'nombre_cliente' => $this->repairVisibleText($this->vault->nombreClienteReveal($cab['nombre_cliente'] ?? null)),
            'telefono' => $this->vault->telefonoReveal($cab['telefono'] ?? null),
            'correo' => $this->normalizeCorreo($this->vault->correoReveal($cab['correo'] ?? null)),
            'poblacion' => $this->repairVisibleText($this->vault->poblacionReveal($cab['poblacion'] ?? null)),
            'fecha_entrada' => $cab['fecha_entrada'] ?? null,
            'fecha_terminada' => $cab['fecha_terminada'] ?? null,
            'fecha_salida' => $cab['fecha_salida'] ?? null,
            'total_pagar' => $trabajo ? (float) ($trabajo->total_pagar ?? 0) : 0.0,
            'equipos' => $equipos,
        ];
    }

    private function normalizeCorreo(string $correo): string
    {
        return mb_strtolower(trim($correo), 'UTF-8');
    }

    private function repairVisibleText(mixed $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        return strtr($text, [
            'Ã¡' => 'á',
            'Ã©' => 'é',
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ãº' => 'ú',
            'Ã' => 'Á',
            'Ã‰' => 'É',
            'Ã' => 'Í',
            'Ã“' => 'Ó',
            'Ãš' => 'Ú',
            'Ã±' => 'ñ',
            'Ã‘' => 'Ñ',
            'Ã¼' => 'ü',
            'Ãœ' => 'Ü',
            'Â¿' => '¿',
            'Â¡' => '¡',
            'Â«' => '«',
            'Â»' => '»',
            'â' => "'",
            'â' => "'",
            'â' => '"',
            'â' => '"',
            'â' => '-',
            'â' => '-',
            'â¦' => '...',
            'Â ' => ' ',
            'Â' => '',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AnluxAuthContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class OrdenAuditService
{
    public function __construct(
        private readonly OrdenPolicyService $policy
    ) {}

    public function log(int $idOrdenC, ?string $folio, string $accion, array $detalles = []): void
    {
        $accion = trim($accion);
        if ($accion === '') {
            return;
        }
        // Folios de secuencia pueden auditarse sin orden (id 0).
        if ($idOrdenC <= 0 && ! str_starts_with($accion, 'folio_')) {
            return;
        }
        try {
            $this->policy->ensureAuditTable();
            $usuario = AnluxAuthContext::nombreTecnicoSesionActual();
            if ($usuario === '') {
                $usuario = trim((string) session('nombre_tecnico', ''));
            }
            $httpRequest = app(Request::class);
            $ip = $httpRequest->ip();
            $userAgent = (string) $httpRequest->userAgent();
            if (strlen($userAgent) > 255) {
                $userAgent = substr($userAgent, 0, 255);
            }
            $payload = $detalles !== [] ? json_encode($detalles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            DB::insert(
                'INSERT INTO orden_servicio_audit_log (id_orden_c, folio, accion, usuario, detalles, ip, user_agent, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $idOrdenC,
                    $folio,
                    $accion,
                    $usuario,
                    $payload,
                    $ip,
                    $userAgent,
                    now()->format('Y-m-d H:i:s'),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('orden_audit: '.$e->getMessage());
        }
    }
}

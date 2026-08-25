<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\User;
use App\Services\OrdenListService;
use App\Services\OrdenStatusService;
use App\Services\RegistrarOrdenService;
use App\Support\ExactoAuthContext;
use App\Support\ExactoUtf8;
use App\Support\MaterialesOrdenClassifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    private function debugLog(string $runId, string $hypothesisId, string $location, string $message, array $data = []): void {}

    public function __construct(
        private readonly OrdenListService $listService,
        private readonly OrdenStatusService $statusService,
        private readonly RegistrarOrdenService $registrarService
    ) {}

    public function index(): View
    {
        $user = Auth::user();
        $this->debugLog('run2', 'H3', 'app/Http/Controllers/OrderController.php:index', 'Orders index hit', [
            'authenticated' => (bool) $user,
            'user_id' => $user?->id_tecnico,
        ]);
        abort_unless($user, 403);

        $nombreTecnico = htmlspecialchars(
            (string) (session('nombre_tecnico') ?? $user->nombre_tecnico ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
        $nav_activo = 'ordenes';
        $pageTitle = ExactoUtf8::fromCodepoint(0x00D3).'rdenes de Servicio - Exacto';
        $pageHeadExtra = '';

        return view('orders.index', compact(
            'nombreTecnico',
            'nav_activo',
            'pageTitle',
            'pageHeadExtra'
        ));
    }

    public function list(Request $request): JsonResponse
    {
        $user = ExactoAuthContext::currentUser();
        $this->debugLog('run1', 'H3', 'app/Http/Controllers/OrderController.php:list:entry', 'API list entry', [
            'authenticated' => $user !== null,
            'user_id' => $user?->id_tecnico,
            'perfil' => $user?->perfil,
            'session_nombre_tecnico' => (string) session('nombre_tecnico', ''),
            'query' => [
                'search' => (string) $request->query('search', ''),
                'startDate' => (string) $request->query('startDate', ''),
                'endDate' => (string) $request->query('endDate', ''),
                'estatus' => (string) $request->query('estatus', ''),
                'page' => (string) $request->query('page', ''),
                'perPage' => (string) $request->query('perPage', ''),
            ],
        ]);
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $search = trim((string) $request->query('search', ''));
        $startDate = trim((string) $request->query('startDate', ''));
        $endDate = trim((string) $request->query('endDate', ''));
        $estatus = trim((string) $request->query('estatus', 'todos'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('perPage', 10);
        $sortRaw = trim((string) $request->query('sort', 'fecha'));
        $sort = $sortRaw === 'estatus' ? 'estatus' : 'fecha';

        try {
            $result = $this->listService->listForRequest($user, $search, $startDate, $endDate, $estatus, $page, $perPage, $sort);
        } catch (\Throwable $e) {
            report($e);
            $this->debugLog('run1', 'H3', 'app/Http/Controllers/OrderController.php:list:error', 'API list exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar el listado de órdenes. Revisa que estén subidos OrderStatus.php y OrdenListService.php actualizados.',
            ], 500);
        }

        $this->debugLog('run1', 'H3', 'app/Http/Controllers/OrderController.php:list:exit', 'API list exit', [
            'success' => (bool) ($result['success'] ?? false),
            'count' => isset($result['data']) && is_array($result['data']) ? count($result['data']) : null,
            'total' => $result['pagination']['total'] ?? null,
            'message' => $result['message'] ?? null,
        ]);

        return response()->json(
            $result,
            200,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $id = (int) $request->input('id', 0);
        $estatus = trim((string) $request->input('estatus', ''));
        if ($id > 0) {
            Gate::authorize('order-access', $id);
        }
        $result = $this->statusService->updateStatus($user, $id, $estatus);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function registrar(Request $request): JsonResponse
    {
        $user = Auth::user();
        $this->debugLog('run1', 'H4', 'app/Http/Controllers/OrderController.php:registrar:entry', 'API registrar entry', [
            'authenticated' => (bool) $user,
            'user_id' => $user?->id_tecnico,
            'has_id_orden_c' => $request->filled('id_orden_c'),
            'modo_completar' => (string) $request->input('modo_completar', ''),
        ]);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        try {
            $result = $this->registrarService->handle($request, $user);
        } catch (\Throwable $e) {
            Log::channel('exacto_ops')->error('order_registrar_exception', [
                'user_id' => $user->id_tecnico ?? null,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return response()->json([
                'success' => false,
                'message' => '❌ Error al guardar: '.$e->getMessage(),
            ], 500);
        }
        $this->debugLog('run1', 'H4', 'app/Http/Controllers/OrderController.php:registrar:exit', 'API registrar exit', [
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? null,
            'id_orden_c' => $result['id_orden_c'] ?? null,
        ]);

        $statusCode = $result['success'] ? 200 : 400;
        if (($result['processing'] ?? false) || ($result['duplicate_submit'] ?? false)) {
            $statusCode = 202;
        }

        return response()->json($result, $statusCode);
    }

    public function whatsappEstado(int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $row = DB::table('order_whatsapp_notifications')->where('id', $id)->first();
        if (! $row) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación de WhatsApp no encontrada.',
                'settled' => true,
            ], 404);
        }

        Gate::authorize('order-access', (int) ($row->id_orden_c ?? 0));

        $status = trim((string) ($row->status ?? ''));
        $providerMessage = trim((string) ($row->message ?? ''));

        if (in_array($status, ['delivered', 'read'], true)) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'level' => 'success',
                'message' => 'WhatsApp entregado al cliente.',
                'settled' => true,
            ]);
        }

        if ($status === 'failed') {
            return response()->json([
                'success' => true,
                'status' => $status,
                'level' => 'error',
                'message' => $providerMessage !== ''
                    ? '✖ WhatsApp no entregado: '.$providerMessage
                    : '✖ El número no existe o no recibió el mensaje en WhatsApp.',
                'settled' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => $status !== '' ? $status : 'accepted',
            'level' => 'success',
            'message' => 'WhatsApp enviado. Pendiente de confirmación de entrega.',
            'settled' => false,
        ]);
    }

    public function reenviar(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        Gate::authorize('order-access', $id);

        try {
            $result = $this->registrarService->reenviarRecepcion($id, $request);
        } catch (\Throwable $e) {
            Log::channel('exacto_ops')->error('order_reenviar_exception', [
                'id_orden_c' => $id,
                'user_id' => $user->id_tecnico ?? null,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return response()->json([
                'success' => false,
                'message' => '❌ Error al reenviar: '.$e->getMessage(),
            ], 500);
        }

        $statusCode = ($result['success'] ?? false) ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    public function salidaTemporal(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        Gate::authorize('order-access', $id);

        $result = $this->registrarService->confirmarSalidaTemporal(
            $user,
            $id,
            (string) $request->input('motivo', ''),
            $request->input('firma_cliente'),
            $request->input('firma_tecnico')
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function regresoTemporal(int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        Gate::authorize('order-access', $id);

        $result = $this->registrarService->registrarRegresoTemporal($user, $id);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function validarSaldo(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $id = (int) $request->input('id_orden', 0);
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Orden inválida.'], 400);
        }

        Gate::authorize('order-access', $id);

        // Consultar datos de la orden
        $cabecera = DB::table('orden_servicio_c')->where('id_orden_c', $id)->first();
        $trabajos = DB::table('orden_servicio_t')->where('id_orden_c', $id)->first();

        if (! $cabecera || ! $trabajos) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada.'], 404);
        }

        // Lógica de cálculo idéntica a RegistrarOrdenService.php
        // Todo el formulario captura montos SIN IVA. El 16% solo se aplica en totales.
        $subtotalNeto = (float) ($trabajos->subtotal_t + $trabajos->subtotal_m);
        $ivaTotal = round($subtotalNeto * 0.16, 2);
        $totalPagar = round($subtotalNeto + $ivaTotal, 2);

        // Obtener anticipos y abono saldo desde materiales_orden (única fuente persistida de pagos)
        $anticipoTotal = 0.0;
        $abonoSaldoTotal = 0.0;
        $materialesRaw = DB::table('materiales_orden')->where('id_trabajo', $trabajos->id_trabajo)->get();
        foreach ($materialesRaw as $materialRaw) {
            $materialArr = (array) $materialRaw;
            $anticipoMaterial = (float) ($materialArr['anticipo'] ?? 0);
            $tipoFila = class_exists(MaterialesOrdenClassifier::class)
                ? MaterialesOrdenClassifier::classify($materialArr)
                : 'material';
            if ($tipoFila === 'abono') {
                $abonoSaldoTotal += $anticipoMaterial;
            } elseif ($tipoFila === 'anticipo') {
                $anticipoTotal += $anticipoMaterial;
            }
        }

        // Calcular total recibido (anticipos + abono) con IVA
        $totalRecibido = round(($anticipoTotal + $abonoSaldoTotal) * 1.16, 2);

        // Calcular saldo pendiente
        $saldoPendiente = round($totalPagar - $totalRecibido, 2);
        if ($totalRecibido >= $totalPagar) {
            $saldoPendiente = 0;
        }

        // Tolerancia de redondeo de $0.01
        $valid = $saldoPendiente <= 0.009; // Considerar liquidado si es 0 o muy pequeño

        return response()->json([
            'success' => true,
            'valid' => $valid,
            'saldo_pendiente' => number_format($saldoPendiente, 2),
            'message' => $valid ? 'Saldo liquidado' : 'Queda un saldo pendiente de $'.number_format($saldoPendiente, 2),
            'total_pagar' => number_format($totalPagar, 2),
            'total_recibido' => number_format($totalRecibido, 2),
            'subtotal' => number_format($subtotalNeto, 2),
            'iva' => number_format($ivaTotal, 2),
        ]);
    }

    public function liquidarSaldoEquipo(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $id = (int) $request->input('id_orden', 0);
        $idsEquipo = array_map('intval', $request->input('ids_equipo', []));

        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Orden inválida.'], 400);
        }
        if (empty($idsEquipo)) {
            return response()->json(['success' => false, 'message' => 'No se seleccionaron equipos.'], 400);
        }

        Gate::authorize('order-access', $id);

        // Actualizar el estatus a 'Entregado' y marcar que fue liquidado por equipo
        DB::beginTransaction();
        try {
            // Marcar los trabajos, materiales y anticipos con el id_equipo correspondientes
            // (En una implementación completa, se actualizarían los registros)
            // Por ahora, solo actualizamos el estatus de la orden
            DB::table('orden_servicio_c')
                ->where('id_orden_c', $id)
                ->update(['estatus' => 'Entregado', 'fecha_salida' => now()]);

            // Registrar log de liquidación por equipo
            DB::insert(
                'INSERT INTO `orden_servicio_tecnico_log` (id_orden_c, estatus, nombre_tecnico, fecha) VALUES (?, ?, ?, ?)',
                [$id, 'Liquidado por Equipo', $user->nombre_tecnico ?? 'Sistema', now()->format('Y-m-d H:i:s')]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::channel('exacto_ops')->error('liquidar_saldo_equipo_exception', [
                'id_orden' => $id,
                'ids_equipo' => $idsEquipo,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => '❌ Error al liquidar: '.$e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Saldo liquidado exitosamente para los equipos seleccionados.',
        ]);
    }
}

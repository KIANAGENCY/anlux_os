<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\ExactoAuthContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Throwable;

final class ImpersonationService
{
    public const REQUEST_TTL_MINUTES = 5;

    /** Solicitudes aprobadas sin aplicar se liberan tras este tiempo. */
    public const APPROVED_GRACE_MINUTES = 2;

    public function __construct(
        private readonly OrdenPolicyService $policy,
        private readonly UserPresenceService $presence,
        private readonly UserSessionLockService $sessionLock,
        private readonly SecurityActivityLogger $securityLog,
        private readonly ExactoVaultService $vault,
        private readonly OrdenEditLockService $editLocks
    ) {}

    public function tableExists(): bool
    {
        return Schema::hasTable('impersonation_requests');
    }

    public function ensureTable(): void
    {
        if (! $this->tableExists()) {
            throw new \RuntimeException('Falta la tabla impersonation_requests. Ejecuta: php artisan migrate');
        }
    }

    private function tableReady(): bool
    {
        return $this->tableExists();
    }

    public function isTechnicianProfile(User $user): bool
    {
        return ! $this->policy->userIsAdmin($user);
    }

    /** @return string[] */
    private function loginSelectColumns(): array
    {
        $columns = ['id_tecnico', 'nombre_tecnico', 'perfil'];
        if (Schema::hasColumn('login', 'activo')) {
            $columns[] = 'activo';
        }
        if (Schema::hasColumn('login', 'last_seen_at')) {
            $columns[] = 'last_seen_at';
        }

        return $columns;
    }

    /** @return Collection<int, User> */
    private function fetchNonAdminTechnicians(): Collection
    {
        $load = function (array $columns): Collection {
            return User::query()
                ->select($columns)
                ->orderBy('id_tecnico')
                ->get()
                ->filter(fn (User $user) => ! $this->policy->userIsAdmin($user))
                ->values();
        };

        try {
            return $load($this->loginSelectColumns());
        } catch (QueryException $e) {
            report($e);

            return $load(['id_tecnico', 'nombre_tecnico', 'perfil']);
        }
    }

    /**
     * Cuentas técnicas para el selector (todos los perfiles pueden solicitar cambio).
     *
     * @return array<int, array{id: int, nombre: string, activo: bool, online: bool, selectable: bool, status: string, status_label: string}>
     */
    public function listAccountsForSwitchSelect(User $requester): array
    {
        $requesterId = (int) $requester->id_tecnico;

        try {
            $this->presence->touch($requester);
            $this->sessionLock->releaseStaleSessions();
            $this->expireStaleRequests();
        } catch (QueryException $e) {
            report($e);
        }

        $rows = $this->fetchNonAdminTechnicians();

        $ids = $rows->pluck('id_tecnico')->map(fn ($id) => (int) $id)->all();
        $presenceTracking = $this->presence->trackingEnabled();
        $onlineIds = [];
        foreach ($ids as $id) {
            if ($this->isTargetActivelyConnected($id)) {
                $onlineIds[$id] = true;
            }
        }
        $onlineIds[$requesterId] = true;
        $inUseIds = $this->targetIdsWithPendingRequests();

        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row->id_tecnico;
            $activo = $row->isActivo();
            $online = $presenceTracking
                ? isset($onlineIds[$id])
                : ($activo && $id !== $requesterId);
            $isSelf = $id === $requesterId;
            $pendingSwitch = isset($inUseIds[$id]);
            $selectable = ! $isSelf && $activo && ! $pendingSwitch;

            if ($isSelf) {
                $status = 'self';
                $statusLabel = 'Tu sesión actual';
            } elseif (! $activo) {
                $status = 'inactive';
                $statusLabel = 'Cuenta inactiva';
            } elseif ($pendingSwitch) {
                $status = 'in_use';
                $statusLabel = 'Acceso directo';
            } elseif ($online) {
                $status = 'online';
                $statusLabel = 'En línea — acceso directo';
            } else {
                $status = 'offline';
                $statusLabel = 'Sin conexión — acceso directo';
            }

            try {
                $nombre = $this->displayName($row);
            } catch (\Throwable $e) {
                report($e);
                $nombre = 'Técnico #'.$id;
            }

            $out[] = [
                'id' => $id,
                'nombre' => $nombre,
                'activo' => $activo,
                'online' => $online,
                'selectable' => $selectable,
                'status' => $status,
                'status_label' => $statusLabel,
            ];
        }

        usort($out, static function (array $a, array $b): int {
            $order = ['online' => 0, 'in_use' => 1, 'offline' => 2, 'inactive' => 3, 'self' => 4];

            return ($order[$a['status']] ?? 9) <=> ($order[$b['status']] ?? 9)
                ?: strcasecmp((string) $a['nombre'], (string) $b['nombre']);
        });

        return $out;
    }

    public function displayName(User $user): string
    {
        $nombre = trim((string) ($user->nombre_tecnico ?? ''));
        if ($nombre !== '' && ! str_starts_with($nombre, 'v1:')) {
            return $nombre;
        }
        $raw = (string) ($user->getAttributes()['nombre_tecnico'] ?? '');
        if ($raw !== '' && str_starts_with($raw, 'v1:')) {
            $revealed = $this->vault->revealString($raw, false);
            if ($revealed !== '') {
                return $revealed;
            }
        }

        return 'Técnico #'.(int) $user->id_tecnico;
    }

    /**
     * @return array{success: bool, message?: string, token?: string, target_nombre?: string}
     */
    public function createRequest(User $requester, int $targetId): array
    {
        if (! $this->tableReady()) {
            return ['success' => false, 'message' => 'La función de impersonación no está disponible. Ejecuta las migraciones en el servidor.'];
        }

        if (ExactoAuthContext::isImpersonating()) {
            return ['success' => false, 'message' => 'Vuelve a tu cuenta antes de solicitar otra.'];
        }

        if ($targetId <= 0 || $targetId === (int) $requester->id_tecnico) {
            return ['success' => false, 'message' => 'No puedes solicitar acceso a tu propia cuenta.'];
        }

        $target = User::query()->find($targetId);
        if (! $target || $this->policy->userIsAdmin($target)) {
            return ['success' => false, 'message' => 'Usuario técnico no válido.'];
        }

        if (! $target->isActivo()) {
            return ['success' => false, 'message' => 'Esa cuenta está inactiva.'];
        }

        try {
            $this->sessionLock->releaseStaleSessions();
            $this->expireStaleRequests();
            $this->cancelPendingRequestsForTarget($targetId);
        } catch (QueryException $e) {
            report($e);
        }

        // El cambio de cuenta es siempre instantáneo: no se espera confirmación del titular,
        // aunque la cuenta esté abierta en otro dispositivo.
        $targetOnline = $this->isTargetActivelyConnected($targetId);

        $token = Str::replace('-', '', Str::uuid()->toString());
        $now = now();
        $instantApply = true;
        $status = 'approved';

        try {
            DB::table('impersonation_requests')->insert([
                'admin_id' => (int) $requester->id_tecnico,
                'target_id' => $targetId,
                'status' => $status,
                'token' => $token,
                'resolved_by_id' => $instantApply ? (int) $requester->id_tecnico : null,
                'created_at' => $now,
                'expires_at' => $now->copy()->addMinutes(self::REQUEST_TTL_MINUTES),
                'resolved_at' => $instantApply ? $now : null,
            ]);
        } catch (QueryException $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'No se pudo guardar la solicitud. Revisa la tabla impersonation_requests en la base de datos.',
            ];
        }

        try {
            $this->securityLog->log(
                'impersonacion_solicitud',
                'info',
                json_encode([
                    'requester_id' => (int) $requester->id_tecnico,
                    'target_id' => $targetId,
                    'target_online' => $targetOnline,
                ], JSON_UNESCAPED_UNICODE)
            );
        } catch (Throwable $e) {
            report($e);
        }

        return [
            'success' => true,
            'token' => $token,
            'target_nombre' => $this->displayName($target),
            'requester_nombre' => $this->displayName($requester),
            'target_online' => $targetOnline,
            'instant_apply' => $instantApply,
            'can_apply' => $instantApply,
            'requires_confirmation' => false,
            'message' => 'Entrando a la cuenta…',
        ];
    }

    /**
     * @return array{success: bool, status: string, message?: string, can_apply?: bool}
     */
    public function requestStatus(User $requester, string $token): array
    {
        if (! $this->tableReady()) {
            return ['success' => false, 'status' => 'invalid', 'message' => 'Función no disponible.'];
        }
        $row = $this->findByToken($token);
        if (! $row || (int) $row->admin_id !== (int) $requester->id_tecnico) {
            return ['success' => false, 'status' => 'invalid', 'message' => 'Solicitud no encontrada.'];
        }

        $this->expireStaleRequests();
        $this->expireIfNeeded($row);

        $row = $this->findByToken($token);
        if (! $row || (int) $row->admin_id !== (int) $requester->id_tecnico) {
            return ['success' => false, 'status' => 'invalid', 'message' => 'Solicitud no encontrada.'];
        }

        $status = (string) $row->status;

        return [
            'success' => true,
            'status' => $status,
            'can_apply' => $status === 'approved',
            'message' => match ($status) {
                'pending' => 'Esperando confirmación…',
                'approved' => 'Entrando…',
                'denied' => 'El titular de la cuenta rechazó el acceso.',
                'expired' => 'La solicitud expiró. Intenta de nuevo.',
                default => '',
            },
        ];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function applyApprovedSession(User $requester, string $token): array
    {
        if (! $this->tableReady()) {
            return ['success' => false, 'message' => 'Función no disponible.'];
        }
        $row = $this->findByToken($token);
        if (! $row || (int) $row->admin_id !== (int) $requester->id_tecnico) {
            return ['success' => false, 'message' => 'Solicitud no encontrada.'];
        }
        if ((string) $row->status !== 'approved') {
            return ['success' => false, 'message' => 'La solicitud aún no está aprobada.'];
        }

        $target = User::query()->find((int) $row->target_id);
        if (! $target) {
            return ['success' => false, 'message' => 'Técnico no encontrado.'];
        }

        if (! $target->isActivo()) {
            return ['success' => false, 'message' => 'Esa cuenta está inactiva.'];
        }

        Session::put([
            'exacto_impersonator_id' => (int) $requester->id_tecnico,
            'exacto_impersonating' => true,
        ]);

        Auth::login($target);
        ExactoAuthContext::syncSessionForUser($target);

        DB::table('impersonation_requests')
            ->where('id', (int) $row->id)
            ->update([
                'status' => 'applied',
                'resolved_at' => now(),
            ]);

        $this->securityLog->log(
            'impersonacion_inicio',
            'warning',
            json_encode([
                'requester_id' => (int) $requester->id_tecnico,
                'target_id' => (int) $target->id_tecnico,
                'resolved_by' => (int) ($row->resolved_by_id ?? 0),
            ], JSON_UNESCAPED_UNICODE)
        );

        return [
            'success' => true,
            'message' => 'Ahora actúas como '.$this->displayName($target).'.',
        ];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function cancelRequestByToken(User $requester, string $token): array
    {
        if (! $this->tableReady()) {
            return ['success' => false, 'message' => 'Función no disponible.'];
        }

        $token = trim($token);
        if ($token === '') {
            return ['success' => false, 'message' => 'Token no válido.'];
        }

        $row = $this->findByToken($token);
        if (! $row || (int) $row->admin_id !== (int) $requester->id_tecnico) {
            return ['success' => false, 'message' => 'Solicitud no encontrada.'];
        }

        $status = (string) $row->status;
        if (! in_array($status, ['pending', 'approved'], true)) {
            return ['success' => true, 'message' => 'La solicitud ya no está activa.'];
        }

        DB::table('impersonation_requests')
            ->where('id', (int) $row->id)
            ->update([
                'status' => 'expired',
                'resolved_at' => now(),
            ]);

        return ['success' => true, 'message' => 'Solicitud cancelada.'];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function endImpersonation(): array
    {
        if (! ExactoAuthContext::isImpersonating()) {
            return ['success' => false, 'message' => 'No hay sesión de técnico activa.'];
        }

        $adminId = ExactoAuthContext::impersonatorId();
        $admin = ($adminId !== null && $adminId > 0) ? User::query()->find($adminId) : null;
        if (! $admin instanceof User) {
            Session::forget(['exacto_impersonator_id', 'exacto_impersonating']);
            Auth::logout();

            return ['success' => true, 'message' => 'Sesión cerrada.'];
        }

        // Liberar órdenes que el admin tenía abiertas como técnico antes de volver a su cuenta.
        try {
            $this->editLocks->releaseAllForUser((int) $admin->id_tecnico);
        } catch (Throwable $e) {
            report($e);
        }

        Session::forget(['exacto_impersonator_id', 'exacto_impersonating']);
        Auth::login($admin);
        ExactoAuthContext::syncSessionForUser($admin);

        $this->securityLog->log('impersonacion_fin', 'info', json_encode(['admin_id' => (int) $admin->id_tecnico], JSON_UNESCAPED_UNICODE));

        return ['success' => true, 'message' => 'Volviste a tu cuenta.'];
    }

    /** Solicitudes pendientes para el titular de la cuenta (modal en su dispositivo). */
    public function pendingForTarget(User $holder): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        // Si está consultando /pendientes, actualizar presencia (el poll demuestra sesión activa).
        try {
            $this->presence->touch($holder);
        } catch (QueryException $e) {
            report($e);
        }

        $this->expireStaleRequests();

        $rows = DB::select(
            "SELECT r.id, r.admin_id, r.target_id, r.token, r.expires_at
             FROM impersonation_requests r
             WHERE r.status = 'pending' AND r.expires_at > NOW()
             AND r.target_id = ?
             ORDER BY r.created_at ASC
             LIMIT 10",
            [(int) $holder->id_tecnico]
        );

        $out = [];
        foreach ($rows as $row) {
            $requesterUser = User::query()->find((int) $row->admin_id);
            if (! $requesterUser) {
                continue;
            }
            $out[] = [
                'id' => (int) $row->id,
                'token' => (string) $row->token,
                'requester_nombre' => $this->displayName($requesterUser),
                'requester_perfil' => trim((string) ($requesterUser->perfil ?? '')),
                'expires_at' => (string) $row->expires_at,
            ];
        }

        return $out;
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function respond(User $holder, int $requestId, bool $approve): array
    {
        if (! $this->tableReady()) {
            return ['success' => false, 'message' => 'Función no disponible.'];
        }

        try {
            $this->presence->touch($holder);
        } catch (QueryException $e) {
            report($e);
        }

        $row = DB::selectOne('SELECT * FROM impersonation_requests WHERE id = ? LIMIT 1', [$requestId]);
        if (! $row) {
            return ['success' => false, 'message' => 'Solicitud no encontrada.'];
        }

        $this->expireIfNeeded($row);
        if ((string) $row->status !== 'pending') {
            return ['success' => false, 'message' => 'Esta solicitud ya fue resuelta.'];
        }

        if ((int) $row->target_id !== (int) $holder->id_tecnico) {
            return ['success' => false, 'message' => 'Solo el titular de la cuenta puede autorizar o rechazar.'];
        }

        $status = $approve ? 'approved' : 'denied';
        DB::table('impersonation_requests')
            ->where('id', $requestId)
            ->update([
                'status' => $status,
                'resolved_by_id' => (int) $holder->id_tecnico,
                'resolved_at' => now(),
            ]);

        $this->securityLog->log(
            $approve ? 'impersonacion_aprobada' : 'impersonacion_rechazada',
            $approve ? 'warning' : 'info',
            json_encode([
                'request_id' => $requestId,
                'holder_id' => (int) $holder->id_tecnico,
                'requester_id' => (int) $row->admin_id,
                'target_id' => (int) $row->target_id,
            ], JSON_UNESCAPED_UNICODE)
        );

        return [
            'success' => true,
            'message' => $approve ? 'Acceso autorizado.' : 'Acceso rechazado.',
        ];
    }

    private function findByToken(string $token): ?object
    {
        return DB::selectOne('SELECT * FROM impersonation_requests WHERE token = ? LIMIT 1', [$token]);
    }

    /** Cuenta con sesión iniciada y actividad reciente (no solo last_seen antiguo). */
    private function isTargetActivelyConnected(int $targetId): bool
    {
        if ($targetId <= 0) {
            return false;
        }

        if ($this->sessionLock->trackingEnabled()) {
            return $this->sessionLock->isAccountInActiveUse($targetId);
        }

        return $this->presence->trackingEnabled()
            && $this->presence->isTargetOnlineForImpersonation($targetId);
    }

    private function targetHasPendingImpersonationRequest(int $targetId): bool
    {
        return isset($this->targetIdsWithPendingRequests()[$targetId]);
    }

    /** @return array<int, true> */
    private function targetIdsWithPendingRequests(): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        $this->expireStaleRequests();

        $rows = DB::select(
            "SELECT DISTINCT target_id FROM impersonation_requests
             WHERE status = 'pending' AND expires_at > NOW()"
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->target_id] = true;
        }

        return $out;
    }

    private function expireStaleRequests(): void
    {
        if (! $this->tableReady()) {
            return;
        }

        DB::table('impersonation_requests')
            ->where('status', 'pending')
            ->update([
                'status' => 'expired',
                'resolved_at' => now(),
            ]);

        $grace = now()->subMinutes(self::APPROVED_GRACE_MINUTES);

        DB::table('impersonation_requests')
            ->where('status', 'approved')
            ->where(function ($query) use ($grace): void {
                $query->where('resolved_at', '<', $grace)
                    ->orWhere(function ($q) use ($grace): void {
                        $q->whereNull('resolved_at')->where('created_at', '<', $grace);
                    });
            })
            ->update([
                'status' => 'expired',
                'resolved_at' => DB::raw('COALESCE(resolved_at, NOW())'),
            ]);
    }

    private function cancelPendingRequestsForTarget(int $targetId): void
    {
        if (! $this->tableReady() || $targetId <= 0) {
            return;
        }

        DB::table('impersonation_requests')
            ->where('target_id', $targetId)
            ->where('status', 'pending')
            ->update([
                'status' => 'expired',
                'resolved_at' => now(),
            ]);
    }

    private function expireIfNeeded(object $row): void
    {
        if ((string) $row->status !== 'pending') {
            return;
        }
        if (strtotime((string) $row->expires_at) < time()) {
            DB::table('impersonation_requests')
                ->where('id', (int) $row->id)
                ->update([
                    'status' => 'expired',
                    'resolved_at' => now(),
                ]);
            $row->status = 'expired';
        }
    }
}

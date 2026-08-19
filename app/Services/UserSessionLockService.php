<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una sola sesión activa por cuenta (login). Impersonación bloqueada si la cuenta está en uso.
 */
final class UserSessionLockService
{
    public function __construct(
        private readonly UserPresenceService $presence
    ) {}

    public function trackingEnabled(): bool
    {
        // Sesión única desactivada por configuración: se permite la misma cuenta en
        // varios dispositivos. No se reclama ni valida sesión activa.
        if (! filter_var(config('exacto.session_single_device', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        return Schema::hasTable('login')
            && Schema::hasColumn('login', 'active_session_id')
            && Schema::hasColumn('login', 'active_session_at');
    }

    /**
     * Cuenta con sesión propia activa en otro dispositivo (last_seen reciente + otra sesión).
     */
    public function isAccountInActiveUse(int $userId, ?string $exceptSessionId = null): bool
    {
        if ($userId <= 0 || ! $this->trackingEnabled()) {
            return $this->presence->trackingEnabled() && $this->presence->isUserOnline($userId);
        }

        $row = DB::selectOne(
            'SELECT active_session_id, active_session_at FROM login WHERE id_tecnico = ? LIMIT 1',
            [$userId]
        );

        if (! $row) {
            return false;
        }

        $activeId = trim((string) ($row->active_session_id ?? ''));
        if ($activeId === '') {
            return $this->presence->trackingEnabled() && $this->presence->isUserOnline($userId);
        }

        if ($exceptSessionId !== null && $activeId === $exceptSessionId) {
            return false;
        }

        if (! $this->presence->isUserOnline($userId)) {
            return false;
        }

        return true;
    }

    /** Asigna active_session_id si el técnico está logueado pero aún no tiene sesión registrada (p. ej. cookie recordarme). */
    public function ensureSessionClaimed(User $user, string $sessionId): void
    {
        if (! $this->trackingEnabled()) {
            return;
        }

        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            return;
        }

        $userId = (int) $user->id_tecnico;
        $row = DB::selectOne(
            'SELECT active_session_id FROM login WHERE id_tecnico = ? LIMIT 1',
            [$userId]
        );
        if (! $row) {
            return;
        }

        $activeId = trim((string) ($row->active_session_id ?? ''));
        if ($activeId !== '' && $activeId !== $sessionId) {
            return;
        }

        if ($activeId === '') {
            DB::table('login')
                ->where('id_tecnico', $userId)
                ->update([
                    'active_session_id' => $sessionId,
                    'active_session_at' => now(),
                ]);
        }
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function claimSession(User $user, string $sessionId): array
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            return ['success' => false, 'message' => 'Sesión no válida.'];
        }

        $userId = (int) $user->id_tecnico;

        if (! $this->trackingEnabled()) {
            return ['success' => true];
        }

        $this->releaseStaleSessions();

        if ($this->isAccountInActiveUse($userId, $sessionId)) {
            return [
                'success' => false,
                'message' => 'Esta cuenta ya está en uso en otro dispositivo. Cierra sesión allí o espera unos minutos.',
            ];
        }

        DB::table('login')
            ->where('id_tecnico', $userId)
            ->update([
                'active_session_id' => $sessionId,
                'active_session_at' => now(),
            ]);

        return ['success' => true];
    }

    public function refreshSession(User $user, string $sessionId): void
    {
        if (! $this->trackingEnabled()) {
            return;
        }

        $userId = (int) $user->id_tecnico;
        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            return;
        }

        DB::table('login')
            ->where('id_tecnico', $userId)
            ->where('active_session_id', $sessionId)
            ->update(['active_session_at' => now()]);
    }

    /**
     * @return array{valid: bool, message?: string}
     */
    public function validateCurrentSession(User $user, string $sessionId): array
    {
        if (! $this->trackingEnabled()) {
            return ['valid' => true];
        }

        $userId = (int) $user->id_tecnico;
        $sessionId = trim($sessionId);

        $row = DB::selectOne(
            'SELECT active_session_id FROM login WHERE id_tecnico = ? LIMIT 1',
            [$userId]
        );

        if (! $row) {
            return ['valid' => true];
        }

        $activeId = trim((string) ($row->active_session_id ?? ''));
        if ($activeId === '' || $activeId === $sessionId) {
            return ['valid' => true];
        }

        if ($this->presence->isUserOnline($userId)) {
            return [
                'valid' => false,
                'message' => 'Tu cuenta se abrió en otro dispositivo. Vuelve a iniciar sesión.',
            ];
        }

        return ['valid' => true];
    }

    public function releaseSession(int $userId, ?string $sessionId = null): void
    {
        if (! $this->trackingEnabled() || $userId <= 0) {
            return;
        }

        $query = DB::table('login')->where('id_tecnico', $userId);

        if ($sessionId !== null && trim($sessionId) !== '') {
            $query->where('active_session_id', trim($sessionId));
        }

        $query->update([
            'active_session_id' => null,
            'active_session_at' => null,
        ]);
    }

    /**
     * Libera sesiones huérfanas cuando el usuario ya no tiene actividad reciente.
     */
    public function releaseStaleSessions(): void
    {
        if (! $this->trackingEnabled() || ! $this->presence->trackingEnabled()) {
            return;
        }

        $minutes = $this->presence->onlineMinutes();

        DB::update(
            "UPDATE login SET active_session_id = NULL, active_session_at = NULL
             WHERE active_session_id IS NOT NULL AND TRIM(active_session_id) <> ''
             AND (last_seen_at IS NULL OR last_seen_at < DATE_SUB(NOW(), INTERVAL ? MINUTE))",
            [$minutes]
        );
    }
}

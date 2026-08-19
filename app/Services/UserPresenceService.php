<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UserPresenceService
{
    public function onlineMinutes(): int
    {
        return max(1, (int) config('exacto.presence_online_minutes', 10));
    }

    /** Tiempo máximo sin peticiones antes de forzar cierre de sesión. */
    public function sessionIdleMinutes(): int
    {
        return max(1, (int) config('exacto.session_idle_minutes', $this->onlineMinutes()));
    }

    public function trackingEnabled(): bool
    {
        return Schema::hasTable('login') && Schema::hasColumn('login', 'last_seen_at');
    }

    /** SQL seguro si la columna `activo` aún no existe (sin migración). */
    private function activoEnabledSql(): string
    {
        return Schema::hasColumn('login', 'activo') ? 'COALESCE(activo, 1) = 1' : '1 = 1';
    }

    public function touch(User $user): void
    {
        if (! $this->trackingEnabled()) {
            return;
        }

        DB::table('login')
            ->where('id_tecnico', (int) $user->id_tecnico)
            ->update(['last_seen_at' => now()]);
    }

    /**
     * True si la última actividad registrada supera el tiempo de inactividad permitido.
     */
    public function isSessionIdleExpired(User $user): bool
    {
        if (! $this->trackingEnabled()) {
            return false;
        }

        $userId = (int) $user->id_tecnico;
        if ($userId <= 0) {
            return false;
        }

        $row = DB::selectOne(
            'SELECT last_seen_at FROM login WHERE id_tecnico = ? LIMIT 1',
            [$userId]
        );
        if (! $row || $row->last_seen_at === null || trim((string) $row->last_seen_at) === '') {
            return false;
        }

        $lastSeenTs = strtotime((string) $row->last_seen_at);
        if ($lastSeenTs === false) {
            return false;
        }

        return (time() - $lastSeenTs) > ($this->sessionIdleMinutes() * 60);
    }

    public function isUserOnline(int $userId): bool
    {
        return isset($this->onlineUserIds([$userId])[$userId]);
    }

    /**
     * @param  int[]  $userIds
     * @return array<int, true>
     */
    public function onlineUserIds(array $userIds): array
    {
        if (! $this->trackingEnabled()) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds), fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $activoSql = $this->activoEnabledSql();
        $params = array_merge($ids, [$this->onlineMinutes()]);

        $rows = DB::select(
            "SELECT id_tecnico FROM login
             WHERE id_tecnico IN ({$placeholders})
             AND {$activoSql}
             AND last_seen_at IS NOT NULL
             AND last_seen_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            $params
        );

        $online = [];
        foreach ($rows as $row) {
            $online[(int) $row->id_tecnico] = true;
        }

        return $online;
    }

    /** @return int[] */
    public function onlineTechnicianIdsExcluding(int ...$excludeIds): array
    {
        if (! $this->trackingEnabled()) {
            return [];
        }

        $exclude = array_values(array_filter(array_map('intval', $excludeIds), fn (int $id) => $id > 0));
        $placeholders = $exclude !== [] ? implode(',', array_fill(0, count($exclude), '?')) : '';
        $excludeSql = $exclude !== [] ? " AND id_tecnico NOT IN ({$placeholders})" : '';

        $params = $exclude;
        $activoSql = $this->activoEnabledSql();
        $sql = "SELECT id_tecnico FROM login
            WHERE {$activoSql}
            AND LOWER(TRIM(COALESCE(perfil, ''))) NOT IN ('administrador', 'admin')
            AND last_seen_at IS NOT NULL
            AND last_seen_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            {$excludeSql}";

        array_unshift($params, $this->onlineMinutes());

        $rows = DB::select($sql, $params);

        return array_map(fn ($r) => (int) $r->id_tecnico, $rows);
    }

    public function isTargetOnlineForImpersonation(int $targetId): bool
    {
        return $this->isUserOnline($targetId);
    }

    /** Marca al usuario como desconectado (p. ej. al cerrar sesión). */
    public function markOffline(int $userId): void
    {
        if (! $this->trackingEnabled() || $userId <= 0) {
            return;
        }

        DB::table('login')
            ->where('id_tecnico', $userId)
            ->update(['last_seen_at' => null]);
    }
}

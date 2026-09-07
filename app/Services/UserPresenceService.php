<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\SafeSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UserPresenceService
{
    public function onlineMinutes(): int
    {
        return max(1, (int) config('anlux.presence_online_minutes', 10));
    }

    /** Tiempo máximo sin peticiones antes de forzar cierre de sesión. */
    public function sessionIdleMinutes(): int
    {
        return max(1, (int) config('anlux.session_idle_minutes', $this->onlineMinutes()));
    }

    public function trackingEnabled(): bool
    {
        return Schema::hasTable('login') && SafeSchema::hasColumn('login', 'last_seen_at');
    }

    /** SQL seguro si la columna `activo` aún no existe (sin migración). */
    private function activoEnabledSql(): string
    {
        return SafeSchema::hasColumn('login', 'activo') ? 'COALESCE(activo, 1) = 1' : '1 = 1';
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

        $query = DB::table('login')
            ->select('id_tecnico')
            ->whereIn('id_tecnico', $ids)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', now()->subMinutes($this->onlineMinutes()));

        if (SafeSchema::hasColumn('login', 'activo')) {
            $query->where(function ($active): void {
                $active->where('activo', 1)->orWhereNull('activo');
            });
        }

        $rows = $query->get();

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
        $query = DB::table('login')
            ->select('id_tecnico')
            ->whereNotIn(DB::raw("LOWER(TRIM(COALESCE(perfil, '')))"), ['administrador', 'admin'])
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', now()->subMinutes($this->onlineMinutes()));

        if (SafeSchema::hasColumn('login', 'activo')) {
            $query->where(function ($active): void {
                $active->where('activo', 1)->orWhereNull('activo');
            });
        }
        if ($exclude !== []) {
            $query->whereNotIn('id_tecnico', $exclude);
        }

        $rows = $query->get();

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

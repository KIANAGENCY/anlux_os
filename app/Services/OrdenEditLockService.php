<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\AnluxAuthContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class OrdenEditLockService
{
    public const LEASE_SECONDS = 180;

    public function __construct(
        private readonly AnluxVaultService $vault
    ) {}

    public function tableExists(): bool
    {
        return Schema::hasTable('orden_servicio_edit_locks');
    }

    public function ensureTable(): void
    {
        if (! $this->tableExists()) {
            throw new \RuntimeException('Falta la tabla orden_servicio_edit_locks. Ejecuta: php artisan migrate');
        }
    }

    public function purgeExpired(): void
    {
        if (! Schema::hasTable('orden_servicio_edit_locks')) {
            return;
        }
        DB::table('orden_servicio_edit_locks')->where('expires_at', '<', now())->delete();
    }

    public function displayNameForUser(User $user): string
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

        return 'Usuario #'.(int) $user->id_tecnico;
    }

    /**
     * @return array{acquired: bool, holder_id?: int, holder_nombre?: string}
     */
    public function acquire(int $orderId, User $operator): array
    {
        $this->ensureTable();
        $this->purgeExpired();

        $holderId = AnluxAuthContext::editLockUserId();
        if ($holderId <= 0) {
            $holderId = (int) $operator->id_tecnico;
        }

        $existing = DB::selectOne(
            'SELECT locked_by_user_id, locked_by_nombre, expires_at FROM orden_servicio_edit_locks WHERE id_orden_c = ? LIMIT 1',
            [$orderId]
        );

        if ($existing) {
            $expires = strtotime((string) $existing->expires_at);
            if ($expires !== false && $expires >= time()) {
                if ((int) $existing->locked_by_user_id === $holderId) {
                    $this->renew($orderId, $holderId, (string) $existing->locked_by_nombre);

                    return ['acquired' => true];
                }

                return [
                    'acquired' => false,
                    'holder_id' => (int) $existing->locked_by_user_id,
                    'holder_nombre' => (string) $existing->locked_by_nombre,
                ];
            }
            DB::table('orden_servicio_edit_locks')->where('id_orden_c', $orderId)->delete();
        }

        $nombre = $this->displayNameForUser($operator);
        if (AnluxAuthContext::isImpersonating()) {
            $admin = AnluxAuthContext::operatorUser();
            if ($admin) {
                $nombre = $this->displayNameForUser($admin).' (como '.$nombre.')';
            }
        }

        $now = now();
        DB::table('orden_servicio_edit_locks')->insert([
            'id_orden_c' => $orderId,
            'locked_by_user_id' => $holderId,
            'locked_by_nombre' => mb_substr($nombre, 0, 255, 'UTF-8'),
            'locked_at' => $now,
            'expires_at' => $now->copy()->addSeconds(self::LEASE_SECONDS),
        ]);

        return ['acquired' => true];
    }

    public function renew(int $orderId, ?int $holderId = null, ?string $nombre = null): bool
    {
        $this->ensureTable();
        $holderId = $holderId ?? AnluxAuthContext::editLockUserId();
        if ($holderId <= 0) {
            return false;
        }

        $row = DB::selectOne(
            'SELECT locked_by_user_id, locked_by_nombre FROM orden_servicio_edit_locks WHERE id_orden_c = ? LIMIT 1',
            [$orderId]
        );
        if (! $row || (int) $row->locked_by_user_id !== $holderId) {
            return false;
        }

        $nombre = $nombre ?? (string) $row->locked_by_nombre;
        DB::table('orden_servicio_edit_locks')
            ->where('id_orden_c', $orderId)
            ->update([
                'expires_at' => now()->addSeconds(self::LEASE_SECONDS),
                'locked_by_nombre' => mb_substr($nombre, 0, 255, 'UTF-8'),
            ]);

        return true;
    }

    public function release(int $orderId, ?int $holderId = null): void
    {
        if (! Schema::hasTable('orden_servicio_edit_locks')) {
            return;
        }
        $holderId = $holderId ?? AnluxAuthContext::editLockUserId();
        DB::table('orden_servicio_edit_locks')
            ->where('id_orden_c', $orderId)
            ->where('locked_by_user_id', $holderId)
            ->delete();
    }

    public function assertHolder(int $orderId, ?int $holderId = null, ?User $operator = null): bool
    {
        $this->purgeExpired();
        $holderId = $holderId ?? AnluxAuthContext::editLockUserId();
        if ($holderId <= 0 || ! Schema::hasTable('orden_servicio_edit_locks')) {
            return false;
        }

        $row = DB::selectOne(
            'SELECT locked_by_user_id, expires_at FROM orden_servicio_edit_locks WHERE id_orden_c = ? LIMIT 1',
            [$orderId]
        );
        if (! $row) {
            // Lease expiró: intentar recuperar el bloqueo para el mismo operador.
            $operator ??= AnluxAuthContext::currentUser();
            if (! $operator instanceof User) {
                return false;
            }
            $acq = $this->acquire($orderId, $operator);

            return (bool) ($acq['acquired'] ?? false);
        }

        $expires = strtotime((string) $row->expires_at);

        return $expires !== false && $expires >= time() && (int) $row->locked_by_user_id === $holderId;
    }

    /** Libera todos los bloqueos activos del operador (p. ej. al salir de impersonación). */
    public function releaseAllForUser(int $holderId): int
    {
        if ($holderId <= 0 || ! Schema::hasTable('orden_servicio_edit_locks')) {
            return 0;
        }

        return (int) DB::table('orden_servicio_edit_locks')
            ->where('locked_by_user_id', $holderId)
            ->delete();
    }

    /** @return object|null */
    public function activeLockForOrder(int $orderId): ?object
    {
        $this->purgeExpired();
        if (! Schema::hasTable('orden_servicio_edit_locks')) {
            return null;
        }

        return DB::selectOne(
            'SELECT id_orden_c, locked_by_user_id, locked_by_nombre, locked_at, expires_at
             FROM orden_servicio_edit_locks
             WHERE id_orden_c = ? AND expires_at >= ?
             LIMIT 1',
            [$orderId, now()->format('Y-m-d H:i:s')]
        );
    }
}

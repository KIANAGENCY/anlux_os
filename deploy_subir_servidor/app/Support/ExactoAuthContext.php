<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Services\ExactoVaultService;
use Illuminate\Support\Facades\Auth;

final class ExactoAuthContext
{
    public static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public static function isImpersonating(): bool
    {
        return (bool) session('exacto_impersonating', false);
    }

    public static function impersonatorId(): ?int
    {
        $id = session('exacto_impersonator_id');

        return $id !== null ? (int) $id : null;
    }

    /** ID del operador real (admin) para locks y auditoría. */
    public static function operatorUserId(): int
    {
        $impersonator = self::impersonatorId();
        if ($impersonator !== null && $impersonator > 0) {
            return $impersonator;
        }

        $user = self::currentUser();

        return $user !== null ? (int) $user->id_tecnico : 0;
    }

    /**
     * Quién tiene el formulario abierto para bloqueo de edición.
     * Usa el operador real (admin) si hay impersonación, para no chocar con el técnico titular.
     */
    public static function editLockUserId(): int
    {
        return self::operatorUserId();
    }

    public static function operatorUser(): ?User
    {
        $id = self::operatorUserId();
        if ($id <= 0) {
            return null;
        }

        return User::query()->find($id);
    }

    public static function syncSessionForUser(User $user): void
    {
        session([
            'id_usuario' => (int) $user->id_tecnico,
            'nombre_tecnico' => self::revealNombreTecnico($user),
            'perfil_usuario' => (string) ($user->perfil ?? ''),
        ]);
    }

    /** Nombre legible del técnico para registrar en órdenes y cambios de estatus (cuenta de sesión actual). */
    public static function nombreTecnicoParaRegistro(?User $user = null): string
    {
        return self::nombreTecnicoSesionActual($user);
    }

    /** Cuenta logueada en pantalla (para Involucrados, entrega y log de estatus). */
    public static function nombreTecnicoSesionActual(?User $user = null): string
    {
        $user ??= self::currentUser();
        if ($user === null) {
            return '';
        }

        $sessionUserId = (int) session('id_usuario', 0);
        $userId = (int) $user->id_tecnico;
        if ($sessionUserId === $userId && $sessionUserId > 0) {
            $desdeSesion = trim((string) session('nombre_tecnico', ''));
            if ($desdeSesion !== '') {
                $revealed = app(ExactoVaultService::class)->tecnicoNombreReveal($desdeSesion);

                return $revealed !== '' ? $revealed : $desdeSesion;
            }
        }

        return self::revealNombreTecnico($user);
    }

    /** Quien abre/recibe la orden (columna Técnico): la cuenta conectada en sesión. */
    public static function tecnicoRecepcionParaOrden(?User $user = null): string
    {
        return self::nombreTecnicoSesionActual($user);
    }

    public static function userIsAdmin(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        $perfil = mb_strtolower(trim((string) ($user->perfil ?? '')), 'UTF-8');

        return $perfil === 'administrador' || $perfil === 'admin';
    }

    private static function revealNombreTecnico(User $user): string
    {
        $raw = trim((string) ($user->getAttributes()['nombre_tecnico'] ?? $user->nombre_tecnico ?? ''));
        if ($raw === '') {
            return '';
        }

        $vault = app(ExactoVaultService::class);
        $revealed = $vault->tecnicoNombreReveal($raw);

        return $revealed !== '' ? $revealed : $raw;
    }
}

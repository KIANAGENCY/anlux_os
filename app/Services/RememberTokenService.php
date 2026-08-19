<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\ExactoAuthContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

final class RememberTokenService
{
    private function cookieName(): string
    {
        return (string) config('exacto.remember_cookie', 'recuerdame');
    }

    private function tokenPart(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public function issue(User $user): void
    {
        $selector = $this->tokenPart(12);
        $validator = $this->tokenPart(32);
        $expiresAt = now()->addDays((int) config('exacto.remember_days', 30))->format('Y-m-d H:i:s');
        try {
            DB::insert(
                'INSERT INTO login_remember_tokens (selector, token_hash, id_tecnico, expires_at) VALUES (?, ?, ?, ?)',
                [$selector, hash('sha256', $validator), $user->id_tecnico, $expiresAt]
            );
        } catch (\Throwable) {
            Cookie::queue(Cookie::forget($this->cookieName()));

            return;
        }
        $value = $selector.':'.$validator;
        $secure = app(Request::class)->secure();
        Cookie::queue($this->cookieName(), $value, (int) config('exacto.remember_days', 30) * 24 * 60, '/', null, $secure, true, false, 'Lax');
    }

    public function forget(?string $cookieValue = null): void
    {
        $cookieValue = $cookieValue ?? (string) app(Request::class)->cookie($this->cookieName(), '');
        $parts = explode(':', $cookieValue, 2);
        if (count($parts) === 2 && $parts[0] !== '') {
            try {
                DB::delete('DELETE FROM login_remember_tokens WHERE selector = ?', [$parts[0]]);
            } catch (\Throwable) {
                // ignore
            }
        }
        $secure = app(Request::class)->secure();
        // Misma ruta / secure / SameSite que issue(), para que el navegador sí borre la cookie.
        Cookie::queue(Cookie::make($this->cookieName(), '', -2628000, '/', null, $secure, true, false, 'Lax'));
    }

    /**
     * Intenta autenticar con cookie legacy si no hay sesión Laravel.
     */
    public function attemptFromCookie(): bool
    {
        if (Auth::check()) {
            return true;
        }
        $cookieValue = (string) app(Request::class)->cookie($this->cookieName(), '');
        $parts = explode(':', $cookieValue, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return false;
        }
        [$selector, $validator] = $parts;
        try {
            $row = DB::selectOne(
                'SELECT rt.selector, rt.token_hash, rt.id_tecnico, l.nombre_tecnico, l.perfil, l.correo, l.contrasena
                 FROM login_remember_tokens rt
                 INNER JOIN login l ON l.id_tecnico = rt.id_tecnico
                 WHERE rt.selector = ? AND rt.expires_at > NOW()
                 LIMIT 1',
                [$selector]
            );
        } catch (\Throwable) {
            $this->forget($cookieValue);

            return false;
        }
        if (! $row || ! hash_equals((string) $row->token_hash, hash('sha256', $validator))) {
            $this->forget($cookieValue);

            return false;
        }
        $user = User::query()->find((int) $row->id_tecnico);
        if (! $user) {
            $this->forget($cookieValue);

            return false;
        }
        Auth::login($user);
        Session::regenerate();
        ExactoAuthContext::syncSessionForUser($user);
        app(UserSessionLockService::class)->ensureSessionClaimed($user, Session::getId());
        app(UserPresenceService::class)->touch($user);
        DB::delete('DELETE FROM login_remember_tokens WHERE selector = ?', [$selector]);
        $this->issue($user);

        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\RememberTokenService;
use App\Services\UserPresenceService;
use App\Services\UserSessionLockService;
use App\Support\ExactoAuthContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ExactoUpdatePresence
{
    public function __construct(
        private readonly UserPresenceService $presence,
        private readonly UserSessionLockService $sessionLock,
        private readonly RememberTokenService $rememberTokens
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $this->handlePresence($request, $next);
        } catch (Throwable $e) {
            Log::warning('ExactoUpdatePresence: '.$e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $next($request);
        }
    }

    private function handlePresence(Request $request, Closure $next): Response
    {
        $user = ExactoAuthContext::currentUser();
        if ($user !== null && ! ExactoAuthContext::isImpersonating()) {
            $sessionId = $request->session()->getId();

            if ($this->presence->isSessionIdleExpired($user)) {
                return $this->logoutForInactivity($request, $user, $sessionId);
            }

            $check = $this->sessionLock->validateCurrentSession($user, $sessionId);
            if (! $check['valid']) {
                $userId = (int) $user->id_tecnico;
                $this->sessionLock->releaseSession($userId, $sessionId);
                $this->presence->markOffline($userId);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $check['message'] ?? 'Sesión cerrada en este dispositivo.',
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => $check['message'] ?? 'Tu cuenta se abrió en otro dispositivo.',
                ]);
            }

            $this->sessionLock->ensureSessionClaimed($user, $sessionId);
            $this->sessionLock->refreshSession($user, $sessionId);
            $this->presence->touch($user);
        } elseif ($user !== null && ExactoAuthContext::isImpersonating()) {
            $admin = ExactoAuthContext::operatorUser();
            if ($admin !== null) {
                $this->presence->touch($admin);
            }
        }

        return $next($request);
    }

    private function logoutForInactivity(Request $request, User $user, string $sessionId): Response
    {
        $userId = (int) $user->id_tecnico;
        $minutes = $this->presence->sessionIdleMinutes();
        $message = "Por seguridad, tu sesión se cerró tras {$minutes} minutos sin actividad. Vuelve a iniciar sesión.";

        $this->rememberTokens->forget();
        $this->sessionLock->releaseSession($userId, $sessionId);
        $this->presence->markOffline($userId);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'session_expired' => true,
            ], 401);
        }

        return redirect()->route('login')->withErrors([
            'email' => $message,
        ]);
    }
}

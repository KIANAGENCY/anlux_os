<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\RememberTokenService;
use App\Services\UserPresenceService;
use App\Services\UserSessionLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        UserSessionLockService $sessionLock,
        UserPresenceService $presence
    ): RedirectResponse {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        if ($user !== null) {
            $claim = $sessionLock->claimSession($user, $request->session()->getId());
            if (! $claim['success']) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => $claim['message'] ?? 'Esta cuenta ya está en uso en otro dispositivo.',
                ]);
            }
            $presence->touch($user);
        }

        $perfil = mb_strtolower(trim((string) (auth()->user()?->perfil ?? '')), 'UTF-8');
        if ($perfil === 'administrador' || $perfil === 'admin') {
            // No usar intended(): si había url.intended (p. ej. /ordenes), Laravel ignoraba /admin.
            return Redirect::route('admin.index');
        }

        return redirect()->intended(route('orden_servicio.create'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(
        Request $request,
        RememberTokenService $rememberTokens,
        UserSessionLockService $sessionLock,
        UserPresenceService $presence
    ): RedirectResponse {
        $userId = (int) ($request->user()?->id_tecnico ?? 0);
        $sessionId = $request->session()->getId();
        $impersonatorId = (int) ($request->session()->get('anlux_impersonator_id') ?? 0);

        $rememberTokens->forget();

        if ($userId > 0) {
            $sessionLock->releaseSession($userId, $sessionId);
            $presence->markOffline($userId);
        }
        if ($impersonatorId > 0 && $impersonatorId !== $userId) {
            $sessionLock->releaseSession($impersonatorId, $sessionId);
            $presence->markOffline($impersonatorId);
        }

        $request->session()->forget(['anlux_impersonating', 'anlux_impersonator_id']);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente.');
    }
}

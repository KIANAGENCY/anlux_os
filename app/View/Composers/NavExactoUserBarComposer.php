<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\User;
use App\Services\ExactoVaultService;
use App\Services\ImpersonationService;
use App\Services\OrdenPolicyService;
use Illuminate\View\View;

final class NavExactoUserBarComposer
{
    public function __construct(
        private readonly OrdenPolicyService $policy,
        private readonly ExactoVaultService $vault,
        private readonly ImpersonationService $impersonation,
    ) {}

    public function compose(View $view): void
    {
        try {
            $authUser = auth()->user();
            $user = $authUser instanceof User ? $authUser : null;

            $isImpersonating = (bool) session('exacto_impersonating', false);
            $isAdmin = $user !== null && ! $isImpersonating && $this->policy->userIsAdmin($user);
            $isTechnician = $user !== null && ! $this->policy->userIsAdmin($user);
            $canSwitchAccount = $user !== null && ! $isImpersonating;
            $userId = $user !== null ? (int) $user->id_tecnico : 0;

            $nombreMostrado = $this->resolveDisplayName($view, $user, $userId);

            $switchAccounts = [];
            if ($canSwitchAccount && $user !== null) {
                try {
                    $switchAccounts = $this->impersonation->listAccountsForSwitchSelect($user);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $uiJsPath = public_path('legacy/assets/js/exacto_ui.js');
            $navImpPath = public_path('legacy/assets/js/nav_impersonacion.js');

            $view->with([
                'exactoIsImpersonating' => $isImpersonating,
                'exactoIsAdmin' => $isAdmin,
                'exactoIsTechnician' => $isTechnician,
                'exactoCanSwitchAccount' => $canSwitchAccount,
                'exactoSwitchAccounts' => $switchAccounts,
                'exactoUserId' => $userId,
                'nombreTecnicoMostrado' => $nombreMostrado,
                'exactoUiJsV' => is_file($uiJsPath) ? (int) filemtime($uiJsPath) : 1,
                'exactoNavImpV' => is_file($navImpPath) ? (int) filemtime($navImpPath) : 1,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $view->with([
                'exactoIsImpersonating' => false,
                'exactoIsAdmin' => false,
                'exactoIsTechnician' => auth()->check(),
                'exactoCanSwitchAccount' => auth()->check(),
                'exactoSwitchAccounts' => [],
                'exactoUserId' => (int) (auth()->user()?->id_tecnico ?? 0),
                'nombreTecnicoMostrado' => trim((string) session('nombre_tecnico', '')),
                'exactoUiJsV' => 1,
                'exactoNavImpV' => 1,
            ]);
        }
    }

    private function resolveDisplayName(View $view, ?User $user, int $userId): string
    {
        $fromParent = trim(html_entity_decode(
            (string) ($view->getData()['nombreTecnico'] ?? ''),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ));

        $nombre = $fromParent;
        if ($nombre === '') {
            $nombre = trim((string) (session('nombre_tecnico') ?? ($user?->nombre_tecnico ?? '')));
        }

        if ($nombre !== '' && str_starts_with($nombre, 'v1:')) {
            $revealed = $this->vault->revealString($nombre, false);
            if ($revealed !== '') {
                $nombre = $revealed;
            } elseif ($user !== null) {
                $fallback = trim((string) $user->nombre_tecnico);
                if ($fallback !== '') {
                    $nombre = $fallback;
                }
            }
        }

        if ($nombre !== '' && str_starts_with($nombre, 'v1:')) {
            return $userId > 0 ? ('Técnico #'.$userId) : 'Técnico';
        }

        return $nombre;
    }
}

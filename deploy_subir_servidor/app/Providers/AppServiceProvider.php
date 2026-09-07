<?php

namespace App\Providers;

use App\Auth\LegacyEloquentUserProvider;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\View\Composers\NavAnluxUserBarComposer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (class_exists(\App\Support\EquiposOrdenEntregaSchema::class)) {
                \App\Support\EquiposOrdenEntregaSchema::ensure();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::provider('legacy_eloquent', function ($app, array $config) {
            return new LegacyEloquentUserProvider(
                $app->make('hash'),
                $config['model']
            );
        });

        Gate::define('order-access', [OrderPolicy::class, 'access']);
        Gate::define('anlux-admin', static function (Authenticatable $user): bool {
            if (! $user instanceof User) {
                return false;
            }
            $perfil = mb_strtolower(trim((string) ($user->perfil ?? '')), 'UTF-8');

            return $perfil === 'administrador' || $perfil === 'admin';
        });

        if (class_exists(NavAnluxUserBarComposer::class)) {
            View::composer('partials.nav-anlux-user-bar', NavAnluxUserBarComposer::class);
        } else {
            View::composer('partials.nav-anlux-user-bar', static function ($view): void {
                $authUser = Auth::user();
                $user = $authUser instanceof User ? $authUser : null;
                $perfil = mb_strtolower(trim((string) ($user?->perfil ?? session('perfil_usuario', ''))), 'UTF-8');
                $isAdmin = in_array($perfil, ['administrador', 'admin'], true);
                $nombre = trim(html_entity_decode(
                    (string) ($view->getData()['nombreTecnico'] ?? session('nombre_tecnico', '')),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ));

                $view->with([
                    'anluxIsImpersonating' => (bool) session('anlux_impersonating', false),
                    'anluxIsAdmin' => $isAdmin && ! session('anlux_impersonating', false),
                    'anluxIsTechnician' => $user !== null && ! $isAdmin,
                    'anluxCanSwitchAccount' => Auth::check() && ! session('anlux_impersonating', false),
                    'anluxUserId' => $user !== null ? (int) $user->id_tecnico : 0,
                    'nombreTecnicoMostrado' => $nombre,
                    'anluxUiJsV' => 1,
                    'anluxNavImpV' => 1,
                ]);
            });
        }
    }
}

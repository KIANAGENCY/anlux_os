<?php

namespace App\Providers;

use App\Auth\LegacyEloquentUserProvider;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\Services\BrandingService;
use App\Services\IntegrationSettingsService;
use App\Support\EquiposOrdenEntregaSchema;
use App\View\Composers\NavAnluxUserBarComposer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->runningOnVercel()) {
            $storage = '/tmp/storage';
            foreach ([
                $storage.'/app/public',
                $storage.'/framework/cache/data',
                $storage.'/framework/sessions',
                $storage.'/framework/views',
                $storage.'/logs',
            ] as $dir) {
                if (! is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
            }
            $this->app->useStoragePath($storage);
        }
    }

    private function runningOnVercel(): bool
    {
        return (string) (($_ENV['VERCEL'] ?? getenv('VERCEL')) ?: '') !== ''
            || (string) (($_ENV['VERCEL_ENV'] ?? getenv('VERCEL_ENV')) ?: '') !== '';
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            app(IntegrationSettingsService::class)->apply();
        } catch (\Throwable $e) {
            report($e);
        }

        // Browser assets must match the host the user actually opened (avoids CSP
        // blocking absolute APP_URL assets when visiting via localhost/another vhost).
        if (! $this->app->runningInConsole()) {
            $this->app->booted(function (): void {
                try {
                    $req = request();
                    if ($req && $req->getHttpHost()) {
                        URL::forceRootUrl(rtrim($req->root(), '/'));
                    }
                } catch (\Throwable) {
                    // ignore
                }
            });

            // Root-relative Vite URLs (include subdirectory base path when present).
            Vite::createAssetPathsUsing(static function (string $path, ?bool $secure = null): string {
                $base = '';
                try {
                    $base = rtrim((string) request()->getBasePath(), '/');
                } catch (\Throwable) {
                    $base = '';
                }

                return $base.'/'.ltrim($path, '/');
            });
        }

        try {
            if (class_exists(EquiposOrdenEntregaSchema::class)) {
                EquiposOrdenEntregaSchema::ensure();
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

        $navComposerViews = [
            'partials.nav-app',
            'partials.nav-admin',
            'partials.nav-anlux-user-bar',
        ];

        if (class_exists(NavAnluxUserBarComposer::class)) {
            View::composer($navComposerViews, NavAnluxUserBarComposer::class);
        } else {
            View::composer($navComposerViews, static function ($view): void {
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
                    'anluxSwitchAccounts' => [],
                    'anluxUserId' => $user !== null ? (int) $user->id_tecnico : 0,
                    'nombreTecnicoMostrado' => $nombre,
                ]);
            });
        }

        View::composer('*', static function ($view): void {
            try {
                $branding = app(BrandingService::class);
                $view->with([
                    'anluxBranding' => $branding->get(),
                    'anluxBrandCss' => $branding->cssVariables(),
                    'anluxLogoUrl' => $branding->logoUrl(),
                ]);
            } catch (\Throwable) {
                // Branding must never prevent the application from rendering.
            }
        });
    }
}
